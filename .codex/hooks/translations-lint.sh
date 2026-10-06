#!/usr/bin/env bash
# HRM — tərcümə kataloqlarının bütövlüyünü yoxlayır (az/en/ru uyğunluğu, namespace).
# Əmr (translations:lint) bütün kataloqları skan edir və sürətlidir (~0.4s).
# Tetiklənir: lang faylları, .blade.php, və app/Modules/ altındakı .php faylları
# (yəni yeni __('...') açarının əlavə oluna biləcəyi yerlər).
# Xəta olsa exit 2 ilə nəticəni Claude-a qaytarır.
set -uo pipefail

input="$(cat)"

file="$(printf '%s' "$input" | python3 -c 'import sys,json
try:
    d=json.load(sys.stdin); print(d.get("tool_input",{}).get("file_path",""))
except Exception:
    print("")' 2>/dev/null || true)"

[ -n "$file" ] || exit 0

# Yalnız tərcüməyə aid ola biləcək fayllarda işə düş
case "$file" in
  */lang/*|*.blade.php|*/app/Modules/*.php) ;;
  *) exit 0 ;;
esac

root="${CLAUDE_PROJECT_DIR:-$(pwd)}"
cd "$root" 2>/dev/null || exit 0

out="$(php artisan translations:lint 2>&1)"
status=$?

if [ "$status" -ne 0 ]; then
  {
    echo "⚠️ translations:lint xəta tapdı (toxunulan fayl: $file)."
    echo "Yeni açar bütün dillərə (az/en/ru) və doğru namespace-ə əlavə olunmalıdır:"
    echo "$out"
  } >&2
  exit 2
fi

exit 0
