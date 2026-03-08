#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")/../scripts" && pwd)"
SCRIPTS=(
  "convert_to_docx.sh"
  "convert_to_pdf.sh"
  "git_push_docx.sh"
  "upload_to_gdrive.sh"
  "run_all.sh"
)

echo "--- Checking Scripts ---"
for SCRIPT in "${SCRIPTS[@]}"; do
  if [[ -x "$SCRIPT_DIR/$SCRIPT" ]]; then
    echo "[OK] $SCRIPT is executable"
  else
    echo "[FAIL] $SCRIPT is missing or not executable"
    exit 1
  fi
done
echo "All scripts are ready."
