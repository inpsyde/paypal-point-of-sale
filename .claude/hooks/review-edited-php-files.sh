#!/usr/bin/env bash
#
# Stop hook: asks the agent to review each PHP file it edited this session, with
# the PHPCS report for those files.
#
# A file is nudged once per content hash, recorded before the agent acts, so the
# Stop loop ends as soon as the edits stop changing content. A file is skipped when
# its content is no longer what this session wrote, or when it has no uncommitted
# change: then the agent would review code it did not write.

command -v jq >/dev/null 2>&1 || exit 0

# Opt-out per feature in config.local.json (see config.example.json).
feature="php-review"
config="$(dirname "$0")/config.local.json"
if [ -f "$config" ]; then
	enabled=$(jq -r --arg k "$feature" 'if has($k) then .[$k] else true end' "$config" 2>/dev/null)
	[ "$enabled" = "false" ] && exit 0
fi

input=$(cat)

[ "$(printf '%s' "$input" | jq -r '.permission_mode // empty' 2>/dev/null)" = "plan" ] && exit 0

session=$(printf '%s' "$input" | jq -r '.session_id // "nosession"' 2>/dev/null)
[ -n "$session" ] || session="nosession"

dir="${TMPDIR:-/tmp}/pos-php-review/${session}"
[ -d "$dir" ] && [ -n "$CLAUDE_PROJECT_DIR" ] || exit 0
cd "$CLAUDE_PROJECT_DIR" 2>/dev/null || exit 0

content_hash() {
	if command -v md5 >/dev/null 2>&1; then
		md5 -q "$1"
	else
		md5sum "$1" | cut -d' ' -f1
	fi
}

list=""
files=()
for marker in "$dir"/touched-*; do
	[ -e "$marker" ] || continue
	# The glob also matches the sidecars; skip them.
	case "$marker" in *.reviewed|*.written) continue ;; esac
	file=$(cat "$marker" 2>/dev/null)
	[ -n "$file" ] && [ -f "$file" ] || continue

	cur=$(content_hash "$file")
	[ "$(cat "${marker}.written" 2>/dev/null)" = "$cur" ] || continue
	if git ls-files --error-unmatch -- "$file" >/dev/null 2>&1 && git diff --quiet HEAD -- "$file" 2>/dev/null; then
		continue
	fi
	seen="${marker}.reviewed"
	if [ -f "$seen" ] && [ "$(cat "$seen" 2>/dev/null)" = "$cur" ]; then
		continue
	fi
	printf '%s' "$cur" > "$seen" 2>/dev/null
	list="${list}"$'\n'"- ${file}"
	files+=("$file")
done

[ -n "$list" ] || exit 0

reason="Before finishing this turn, review your PHP changes. Dispatch the \`review-php-code-cleanliness\` agent once for each file below (in parallel is fine), each with the prompt \`review CHANGES in <file>\`, then apply any fixes it reports. If you are running /commit-loop, skip the review and only mention PHPCS issues in the staged unit. Files needing review:${list}"

# Without a running DDEV the output has no report lines, so the PHPCS part is skipped.
if command -v ddev >/dev/null 2>&1; then
	report=$(ddev exec vendor/bin/phpcs -q --report=emacs "${files[@]}" 2>/dev/null \
		| grep -E '^[^:]+:[0-9]+:[0-9]+: (error|warning) - ' \
		| sed 's|^/var/www/html/||')
	if [ -n "$report" ]; then
		reason="${reason}"$'\n\n'"PHPCS reports the issues below. Fix the ones in code you changed this session; leave pre-existing issues alone:"$'\n'"${report}"
	fi
fi

jq -n --arg r "$reason" '{decision: "block", reason: $r}'
