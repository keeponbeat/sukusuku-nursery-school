#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")/../scripts" && pwd)"
DOC_DIR="$(cd "$(dirname "$0")/../docs/interviews" && pwd)"
TEST_FILE="$DOC_DIR/test_conversion_check.md"
TEST_DOCX="$DOC_DIR/test_conversion_check.docx"
TEST_PDF="$DOC_DIR/test_conversion_check.pdf"

echo "--- Testing Conversion ---"

# 1. Create dummy md
echo "# Test Title" > "$TEST_FILE"
echo "This is a test content." >> "$TEST_FILE"

# 2. Run conversion scripts
echo "Running convert_to_docx.sh..."
"$SCRIPT_DIR/convert_to_docx.sh"

echo "Running convert_to_pdf.sh..."
"$SCRIPT_DIR/convert_to_pdf.sh"

# 3. Check for outputs
if [[ -f "$TEST_DOCX" ]]; then
  echo "[OK] DOCX generated successfully"
else
  echo "[FAIL] DOCX not found"
  exit 1
fi

if [[ -f "$TEST_PDF" ]]; then
  echo "[OK] PDF generated successfully"
else
  echo "[FAIL] PDF not found"
  exit 1
fi

# 4. Cleanup
rm "$TEST_FILE" "$TEST_DOCX" "$TEST_PDF"
echo "Conversion test passed."
