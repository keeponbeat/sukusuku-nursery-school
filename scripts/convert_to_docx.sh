#!/usr/bin/env bash
set -euo pipefail

# ---- 変換対象のファイル/ディレクトリ取得 ----
# 1. docs 直下の 02, 03, 05, 07 で始まる md ファイル
# 2. docs/interviews 配下の md ファイル
BASE_DIR="$(cd "$(dirname "$0")/../docs" && pwd)"
MD_FILES=(
  "$BASE_DIR"/02*.md
  "$BASE_DIR"/03*.md
  "$BASE_DIR"/05*.md
  "$BASE_DIR"/07*.md
  "$BASE_DIR"/interviews/*.md
)

echo "Processing files..."
for MD in "${MD_FILES[@]}"; do
  # ファイルが存在するか確認 (ワイルドカードが展開されなかった場合を考慮)
  if [[ -f "$MD" ]]; then
    DOCX="${MD%.md}.docx"
    echo "  → $MD → $DOCX"
    pandoc -s "$MD" -o "$DOCX"
  fi
done

echo "All conversions finished."
