#!/usr/bin/env bash
set -euo pipefail

TEST_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "=== Starting Integration Tests ==="

# Make sure all tests are executable
chmod +x "$TEST_DIR"/*.sh

# Run tests
"$TEST_DIR/check_dependencies.sh"
"$TEST_DIR/check_scripts.sh"
"$TEST_DIR/test_conversion.sh"

echo "=== All Tests Passed! ==="
