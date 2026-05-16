#!/usr/bin/env bash
set -euo pipefail

DEPS=(
  "pandoc"
  "git"
  "rclone"
)

echo "--- Checking Dependencies ---"
for DEP in "${DEPS[@]}"; do
  if command -v "$DEP" >/dev/null 2>&1; then
    echo "[OK] $DEP is installed"
  else
    echo "[FAIL] $DEP is not found"
    exit 1
  fi
done
echo "All dependencies are installed."
