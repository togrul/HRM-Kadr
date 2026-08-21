#!/usr/bin/env bash
# HRM — Blade view (.blade.php) redaktə olunanda blaze-safe-lint yoxlaması.
# Əmr tək faylı dəstəkləmir, bütün view-ları skan edir; ona görə yalnız
# .blade.php faylına toxunulanda işə düşür. Xəta olsa, exit 2 ilə nəticəni
# Claude-a geri verir (PostToolUse-də stderr Claude-a ötürülür) ki, düzəltsin.
set -uo pipefail

input="$(cat)"

file="$(printf '%s' "$input" | python3 -c 'import sys,json
try:
    d=json.load(sys.stdin); print(d.get("tool_input",{}).get("file_path",""))
except Exception:
    print("")' 2>/dev/null || true)"

# Yalnız Blade view-ları
[ -n "$file" ] || exit 0
case "$file" in
  *.blade.php) ;;
  *) exit 0 ;;
esac

root="${CLAUDE_PROJECT_DIR:-$(pwd)}"
cd "$root" 2>/dev/null || exit 0

# Tam skan. (--strict əlavə etsən, warning-lər də uğursuzluq sayılacaq.)
out="$(php artisan views:blaze-safe-lint 2>&1)"
status=$?

if [ "$status" -ne 0 ]; then
  {
    echo "⚠️ blaze-safe-lint xəta tapdı (sən toxunan fayl: $file). Zəhmət olmasa düzəlt:"
    echo "$out"
  } >&2
  exit 2
fi

exit 0
