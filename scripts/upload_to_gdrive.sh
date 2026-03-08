#!/usr/bin/env bash
set -euo pipefail

# Root of the repository (scripts/ の上の階層)
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DOCX_ROOT="$REPO_ROOT/docs"

# Google Drive remote name (rclone config で設定した名前)
GDRIVE_REMOTE="google-drive-suku2"
# 保存先フォルダ (Antigravity フォルダがルートに設定されているため、その直下のフォルダ名を指定)
GDRIVE_PATH="瀬谷すくすく保育園_ウェブサイトリニューアル_260308"

# rclone で .docx ファイルだけをコピー
rclone copy "$DOCX_ROOT" "${GDRIVE_REMOTE}:${GDRIVE_PATH}" \
  --include "*.docx" \
  --progress

echo "Upload to Google Drive completed."
