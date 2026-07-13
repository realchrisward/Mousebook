#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# regen_baseline.sh — rewrite .github/ci/schema-baseline.tsv from the schema
# as it currently stands.
#
# Run this ONLY when an engine or charset change is intentional (e.g. the
# MyISAM -> InnoDB migration), and commit the regenerated baseline in the SAME
# pull request as the schema change. The diff on this file is the reviewable
# record of "we meant to do that".
#
# Never run it just to make CI green. A red ratchet with no intended schema
# change means something edited an engine or charset by accident — which is the
# entire point of the check.
#
# Usage (needs a reachable server):
#     DB_HOST=127.0.0.1 DB_PORT=3306 DB_USER=root DB_PASS=secret \
#         bash .github/ci/regen_baseline.sh
# ---------------------------------------------------------------------------
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

rm -f .github/ci/schema-baseline.tsv
echo "Removed old baseline; check_schema.sh will write a fresh one."
echo

DB_LABEL="${DB_LABEL:-baseline regen}" bash .github/ci/check_schema.sh

echo
echo "New baseline:"
cat .github/ci/schema-baseline.tsv
echo
echo "Review the diff (git diff .github/ci/schema-baseline.tsv) and commit it"
echo "alongside the schema change that caused it."
