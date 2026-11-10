#!/usr/bin/env bash
#
# PostToolUse (Edit|Write) hook: records the PHP files this session edits, so the
# Stop hook reviews only those and leaves other uncommitted files alone.

command -v jq >/dev/null 2>&1 || exit 0

# Opt-out per feature in config.local.json (see config.example.json).
feature="php-review"
config="$(dirname "$0")/config.local.json"
if [ -f "$config" ]; then
	enabled=$(jq -r --arg k "$feature" 'if has($k) then .[$k] else true end' "$config" 2>/dev/null)
	[ "$enabled" = "false" ] && exit 0
fi

input=$(cat)

file=$(printf '%s' "$input" | jq -r '.tool_input.file_path // empty' 2>/dev/null)
[ -n "$file" ] && [ -f "$file" ] && [ -n "$CLAUDE_PROJECT_DIR" ] || exit 0
case "$file" in
	*.php) ;;
	*) exit 0 ;;
esac

# Resolve symlinks, so `modules/<m>/…` and `modules.local/<m>/…` are one file.
project=$(cd "$CLAUDE_PROJECT_DIR" 2>/dev/null && pwd -P) || exit 0
real_dir=$(cd "$(dirname "$file")" 2>/dev/null && pwd -P) || exit 0
case "$real_dir/" in
	"$project"/*) ;;
	*) exit 0 ;;
esac
rel="${real_dir#"$project"}/$(basename "$file")"
rel="${rel#/}"

# Tests have their own conventions, and a path with whitespace breaks the PHPCS call.
case "/$rel" in
	*/tests/*|*/test/*|*/vendor/*|*/node_modules/*|*[[:space:]]*) exit 0 ;;
esac

session=$(printf '%s' "$input" | jq -r '.session_id // "nosession"' 2>/dev/null)
[ -n "$session" ] || session="nosession"

md5_of() {
	if command -v md5 >/dev/null 2>&1; then md5 -q "$@"; else md5sum "$@" | cut -d' ' -f1; fi
}

dir="${TMPDIR:-/tmp}/pos-php-review/${session}"
marker="${dir}/touched-$(printf '%s' "$rel" | md5_of)"
mkdir -p "$dir" 2>/dev/null
printf '%s' "$rel" > "$marker" 2>/dev/null
# The content as this session left it; the Stop hook ignores edits made elsewhere.
md5_of "$project/$rel" > "${marker}.written" 2>/dev/null

exit 0
