#!/usr/bin/env bash
# HRM — Edit/Write/MultiEdit-dən sonra dəyişən PHP faylını Pint ilə avtomatik formatla.
# Claude Code PostToolUse hook: stdin-də tool çağırışının JSON-u gəlir.
set -euo pipefail

input="$(cat)"

# Dəyişən faylın yolunu çıxar (Edit/Write/MultiEdit hamısında tool_input.file_path olur)
file="$(printf '%s' "$input" | python3 -c 'import sys,json
try:
    d=json.load(sys.stdin); print(d.get("tool_input",{}).get("file_path",""))
except Exception:
    print("")' 2>/dev/null || true)"

# Yalnız PHP faylları
[ -n "$file" ] || exit 0
case "$file" in
  *.php) ;;
  *) exit 0 ;;
esac
[ -f "$file" ] || exit 0

# Layihə kökünə keç (Claude Code bu dəyişəni verir)
root="${CLAUDE_PROJECT_DIR:-$(pwd)}"
cd "$root" 2>/dev/null || exit 0

# Pint varsa, yalnız həmin faylı formatla (səssiz)
if [ -x vendor/bin/pint ]; then
  vendor/bin/pint "$file" >/dev/null 2>&1 || true
fi

exit 0
