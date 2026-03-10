#!/bin/bash
cd /home/hakeem/symfony/PFE/mobile
echo "=== TypeScript Type-Check ==="
echo "CWD: $(pwd)"
echo ""
./node_modules/.bin/tsc --noEmit 2>&1
EXIT_CODE=$?
echo ""
echo "=== Exit code: $EXIT_CODE ==="
