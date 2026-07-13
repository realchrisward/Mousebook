#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# lint.sh — every .php file in the repo must parse under the production PHP.
#
# Run in CI by .github/workflows/ci.yml (job: lint).
# Run locally with:   bash .github/ci/lint.sh
#
# Exit 0 = every file parses. Exit 1 = at least one file has a syntax error;
# the offending files are listed with the parser's own message.
# ---------------------------------------------------------------------------
set -uo pipefail

cd "$(git rev-parse --show-toplevel 2>/dev/null || echo .)"

fails=0
checked=0

while IFS= read -r f; do
    checked=$((checked + 1))
    if ! out="$(php -l "$f" 2>&1)"; then
        # ::error:: makes GitHub annotate the file inline in the PR diff view.
        echo "::error file=${f}::${out}"
        echo "FAIL  ${f}"
        echo "      ${out}"
        fails=$((fails + 1))
    fi
done < <(find . -name '*.php' -not -path './.git/*' | sort)

echo
echo "--------------------------------------------------"
echo "PHP lint: ${checked} file(s) checked, ${fails} failure(s)"
echo "--------------------------------------------------"

if [ "$fails" -ne 0 ]; then
    echo
    echo "A file above does not parse. This is always a real bug — a page with a"
    echo "syntax error is a white screen in production. Fix the syntax and push."
    exit 1
fi

echo "OK — all files parse."
exit 0
