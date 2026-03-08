#!/usr/bin/env bash
set -euo pipefail

# ------------------------------------------------------------
# run_all.sh – 1 つのコマンドで一連の処理を実行
#   1. Markdown → DOCX 変換
#   2. Markdown → PDF 変換
#   3. 生成された DOCX を Git にコミット & push
#   4. DOCX を Google Drive にアップロード
# ------------------------------------------------------------

# スクリプトの場所
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
# プロジェクトルート
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# 1. DOCX 変換
echo "=== Step 1: Convert Markdown → DOCX ==="
"$SCRIPT_DIR/convert_to_docx.sh"

# 2. PDF 変換 (必要なら実行)
echo "=== Step 2: Convert Markdown → PDF ==="
"$SCRIPT_DIR/convert_to_pdf.sh"

# 3. Git コミット & push (DOCX のみ)
echo "=== Step 3: Git commit & push DOCX files ==="
"$SCRIPT_DIR/git_push_docx.sh"

# 4. Google Drive へアップロード
echo "=== Step 4: Upload DOCX files to Google Drive ==="
"$SCRIPT_DIR/upload_to_gdrive.sh"

echo "All steps completed successfully."
