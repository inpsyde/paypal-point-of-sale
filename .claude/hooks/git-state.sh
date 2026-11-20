#!/usr/bin/env bash
#
# Stop (`save`) and UserPromptSubmit (`check`) hook: tells the agent about edits,
# staging, and commits made outside the session between two turns. State is kept
# per session, so parallel sessions do not overwrite each other.

command -v jq >/dev/null 2>&1 || exit 0

# Opt-out per feature in config.local.json (see config.example.json).
feature="stale-check"
config="$(dirname "$0")/config.local.json"
if [ -f "$config" ]; then
	enabled=$(jq -r --arg k "$feature" 'if has($k) then .[$k] else true end' "$config" 2>/dev/null)
	[ "$enabled" = "false" ] && exit 0
fi

mode="$1"
input=$(cat)

session=$(printf '%s' "$input" | jq -r '.session_id // empty' 2>/dev/null)
[ -n "$session" ] || exit 0

[ -n "$CLAUDE_PROJECT_DIR" ] && cd "$CLAUDE_PROJECT_DIR" 2>/dev/null
git rev-parse --git-dir >/dev/null 2>&1 || exit 0

dir="${TMPDIR:-/tmp}/pos-git-state"
state="${dir}/${session}"

# `git ls-files --stage` lists every index entry with its blob hash, so its hash
# changes with any staging change. Unlike `git write-tree`, it writes nothing
# and also works during a merge conflict.
head=$(git rev-parse -q --verify HEAD 2>/dev/null)
branch=$(git symbolic-ref --short -q HEAD 2>/dev/null)
index=$(git ls-files --stage 2>/dev/null | git hash-object --stdin 2>/dev/null)

# One "<content hash> <path>" line per file that differs from HEAD (staged,
# unstaged, or untracked), sorted. A deleted file gets "-" as its hash. All files
# are hashed in one git call, so the cost stays flat with many untracked files.
export LC_ALL=C
paths=$({ git diff --name-only HEAD 2>/dev/null; git ls-files --others --exclude-standard 2>/dev/null; } | sort -u)
existing=$(printf '%s\n' "$paths" | while IFS= read -r path; do [ -f "$path" ] && printf '%s\n' "$path"; done)
files=$(
	{
		[ -n "$existing" ] && paste -d' ' \
			<(printf '%s\n' "$existing" | git hash-object --stdin-paths 2>/dev/null) \
			<(printf '%s\n' "$existing")
		comm -23 <(printf '%s\n' "$paths" | sed '/^$/d') <(printf '%s\n' "$existing" | sed '/^$/d') | sed 's/^/- /'
	} | sort
)

save_state() {
	mkdir -p "$dir" 2>/dev/null
	printf '%s\n%s\n%s\n%s\n' "$head" "$branch" "$index" "$files" > "$state" 2>/dev/null
}

if [ "$mode" = "save" ]; then
	save_state
	exit 0
fi

[ "$mode" = "check" ] || exit 0

# First prompt of the session: nothing to compare yet.
if [ ! -f "$state" ]; then
	save_state
	exit 0
fi

{
	read -r old_head
	read -r old_branch
	read -r old_index
} < "$state"
old_files=$(tail -n +4 "$state")

notes=""

current_hash() {
	if [ -f "$1" ]; then git hash-object -- "$1" 2>/dev/null; else printf -- '-'; fi
}

# A file changed when its content differs from the saved hash, or when it was
# clean at the end of the last turn and is dirty now. A file that only left the
# list because it was committed keeps its content and is not reported.
new_paths=$(printf '%s\n' "$files" | sed '/^$/d' | cut -d' ' -f2- | sort)
changed=$(
	{
		comm -13 <(printf '%s\n' "$old_files" | sed '/^$/d' | sort) <(printf '%s\n' "$files" | sed '/^$/d') | cut -d' ' -f2-
		comm -23 <(printf '%s\n' "$old_files" | sed '/^$/d' | sort) <(printf '%s\n' "$files" | sed '/^$/d') \
			| while IFS=' ' read -r hash path; do
				printf '%s\n' "$new_paths" | grep -qxF -- "$path" && continue
				[ "$(current_hash "$path")" = "$hash" ] || printf '%s\n' "$path"
			done
	} | sort -u
)
if [ -n "$changed" ]; then
	total=$(printf '%s\n' "$changed" | wc -l | tr -d ' ')
	shown=$(printf '%s\n' "$changed" | head -10 | sed 's/^/  - /')
	more=""
	[ "$total" -gt 10 ] && more=$'\n'"  - +$(( total - 10 )) more"
	notes="${notes}"$'\n'"- Files changed:"$'\n'"${shown}${more}"
fi

if [ "$branch" != "$old_branch" ]; then
	notes="${notes}"$'\n'"- Branch switched from \`${old_branch:-detached HEAD}\` to \`${branch:-detached HEAD}\`."
elif [ "$head" != "$old_head" ]; then
	if [ -n "$old_head" ] && git merge-base --is-ancestor "$old_head" "$head" 2>/dev/null; then
		count=$(git rev-list --count "${old_head}..${head}" 2>/dev/null)
		notes="${notes}"$'\n'"- ${count} new commit(s) on \`${branch}\`: ${old_head:0:7} → ${head:0:7}."
	else
		notes="${notes}"$'\n'"- HEAD was rewritten (amend, rebase, or reset): ${old_head:0:7} → ${head:0:7}."
	fi
fi

if [ "$index" != "$old_index" ]; then
	staged=$(git diff --cached --name-only 2>/dev/null)
	if [ -n "$staged" ]; then
		total=$(printf '%s\n' "$staged" | wc -l | tr -d ' ')
		shown=$(printf '%s\n' "$staged" | head -10 | sed 's/^/  - /')
		more=""
		[ "$total" -gt 10 ] && more=$'\n'"  - +$(( total - 10 )) more"
		notes="${notes}"$'\n'"- The staged changes differ. Now staged:"$'\n'"${shown}${more}"
	else
		notes="${notes}"$'\n'"- The staged changes differ. Nothing is staged now."
	fi
fi

save_state

[ -n "$notes" ] || exit 0

context="Files changed outside this session since your last turn:${notes}"

jq -n --arg c "$context" '{hookSpecificOutput: {hookEventName: "UserPromptSubmit", additionalContext: $c}}'
