#!/usr/bin/env bash
set -euo pipefail

# Repository root (assumes this script lives in scripts/)
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$REPO_ROOT"

# Stage generated files and infrastructure (scripts, tests)
git add docs/**/*.docx docs/**/*.pdf scripts/ test/

# If there are changes, commit and push
if git diff --cached --quiet; then
  echo "No changes to commit."
  exit 0
fi

git commit -m "Add scripts, tests, and generated DOCX/PDF files"

git push origin "$(git rev-parse --abbrev-ref HEAD)"

echo "Git commit and push completed."
