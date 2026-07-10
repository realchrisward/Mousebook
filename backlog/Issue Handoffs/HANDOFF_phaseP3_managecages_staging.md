# HANDOFF — Phase P3: manage_cages session-keyed staging (#8 V1) + subset multi-select (#21)

**Repo:** `github.com/realchrisward/Mousebook` · **Branch:** `phase3_revisions`
**Baseline HEAD:** `c1475cf` ("phasep2 whitescreen bug patch")
**Deliverable:** `phaseP3_managecages_staging.patch` (2 files, +336 / −253) — `git apply --check` passes on a fresh `c1475cf` clone.
**Validation:** PHP 8.3.6 (mysqli+session) + MySQL 8.0.46. Both files `php -l` clean pre- and post-apply. 21/21 behavioral checks pass (helper units + live-SQL rewrites + injection payloads).

---

## Re-baseline note

The branch advanced two commits past the P2 handoff baseline `a675333`:
`c1475cf` (whitescreen followup — cage_location reopen + cagerole empty-result guard) → `65c7f43` (P2 SQLi hardening) → `a675333`. **P2 is fully committed**, and the **2b temp_cage injection fix shipped inside P2** (`(int)` casts + `mb_int_values_list`). P3 therefore reduced to the two remaining pieces below, plus the two P2-residual escaping items you asked to fold in.

Also: the SCOPE note that `index.php` L598 joins `temp_cage1` is **stale** — at HEAD `temp_cage` appears in **no file but `manage_cages.php`** (index.php was rewritten in P1). This made Option A a single-file refactor.

---

## What P3 delivers

### `includes/filters.php` (+123) — session staging library
New helpers inside the existing `mb_filters_loaded` guard, backing `$_SESSION['mb_stage'][$dbname][1..4]` (arrays of positive ints):
`mb_stage_slot`, `mb_stage_normalize_ints`, `mb_stage_add`, `mb_stage_remove`, `mb_stage_clear`, `mb_stage_clear_all`, `mb_stage_union`, `mb_stage_in_csv`. Every value is int-coerced by construction, so the `IN (...)` / `NOT IN (...)` fragments they feed are injection-safe without escaping. `mb_stage_in_csv([])` returns `''`, and **callers treat `''` as "omit the predicate"** (MySQL rejects empty `IN ()`).

### `php/manage_cages.php` (+213 / −253) — Option A + #21 + P2 residuals
- **#8 V1 (Option A):** the shared global `temp_cage1..4` (MyISAM) tables are no longer touched. Add/remove/batch/clear ops mutate the session slots; the 4 staged-cage displays render from `WHERE animalautono IN (slot)`; the source-cage and animal-selection lists exclude the staged union via `NOT IN (union)`; the default-location loop and the 8 commit statements (`UPDATE … WHERE animalautono IN (slot)` + `INSERT … SELECT … WHERE animalautono IN (slot)`) read the session slots. Staging **lives in the PHP session and dies with it** — an uncommitted set does not survive logout (the intended isolation win; confirmed acceptable).
- **#21 (subset multi-select):** `animals_selection` is now a `multiple` listbox posting `animals_selection[]`; the existing per-cage **add** buttons operate on whatever subset is selected (1..N). The `onchange` auto-submit was removed from that listbox (pick subset → click add). **"Add all filtered" is retained** (batchlist hidden field → `mb_stage_add`). Remove/clear buttons unchanged in placement.
- **P2 residuals (folded in per your request):** the commit block's raw-interpolated `cage name / category / line / contents` are now `real_escape_string`'d and `cageno` is `(int)`-cast; the source-cage filter `$sourcecage_selection` is escaped before interpolation — matching P2's inline-escape style.

### Bug found & fixed during review (was in the in-progress tree)
`$excl_clause` (the staged-union `NOT IN` exclusion) was consumed by the **source-cage query** but only assigned **inside the `if ($cage1_in !== '')` block** and again *after* the query in the animal-selection block. With cage1 empty but cage2/3/4 staged, `$excl_clause` was **undefined** at the source-cage query → a PHP 8 undefined-variable warning **and** the source lists silently failed to exclude staged animals. Fixed by hoisting the compute to run **once, unconditionally, before the first consumer** and removing the two misplaced copies. Harness scenario **B2** reproduces this exact case (cage1 empty / cage2 populated) and now passes.

---

## Behavioral validation (21/21)

- **Helpers:** dedup/int-only normalization; injection strings (`"(1),(2)); DROP …"`, `"5; DELETE …"`) collapse to their digits; add/merge/remove/union/clear correct.
- **SQL vs MySQL 8** (seeded; **temp_cage tables deliberately absent** to prove independence): staged display `IN(...)` returns the right rows; **B2** bug-fix scenario excludes staged animals from both source lists with cage1 empty; commit `UPDATE … WHERE IN` + comment `INSERT … SELECT` move the staged animals only, leave others untouched, and store a quote-bearing cage name (`NEW'-1`) as a literal; a `DROP TABLE` payload routed through the add path collapses to ints and `table_animals` survives.

---

## Deploy ordering

1. `includes/filters.php` **first** (manage_cages calls the new helpers).
2. Then `php/manage_cages.php`.

**No schema migration.** Because staging moved into the session, in-flight staged sets from the *old* DB-backed tables are dropped at deploy — land this when no transfer is mid-flight, or accept that any half-staged sets need re-staging once.

---

## Schema housekeeping advisory (per your instruction)

The legacy `temp_cage1..4` tables are **intentionally left in the schema, now unused** (a `DROP` migration is deliberately *not* part of this patch). Recommend a **low-priority orphaned-table sweep** in a later session: enumerate tables no code references (start with `temp_cage1..4`), and for each decide **prune vs. keep-as-seed** — some may be worth retaining as scaffolding for future features rather than dropped outright. This is a schema-review task, not a code change, and should sit below the feature queue.

---

## Still separate / carry-forward

- **P4 — #20 Quick Sac** (next in queue): can reuse #21's multi-select selection UX on `animals_selection`.
- The two source queries (source-cage list, animal-selection list) remain **unguarded** by `instanceof mysqli_result` — pre-existing (#22-class), not a P3 regression; candidate for the P6 read-guard audit.
- Other P2-family raw interpolations outside manage_cages, `autoclipsheet.php` L11 parse error, and the committed-in-repo handoff/patch files (`HANDOFF_phaseP2_…md` etc.) housekeeping — all still P6.

---

## Harness gotchas (unchanged, for next session)

- `mysqld` does not survive across tool calls — start server + run dependent queries in **one** bash invocation; datadir persists. Poll with `mysqladmin ping`.
- Never `sed -i` server-side (resets inode group ownership → silent white screens); use `cat tmpfile > file` or `git apply` on a clone.
- Shell is `dash` — no `source`; use `bash -c '. script; …'`.
- Seeding needs `SET SESSION sql_mode=''` and explicit `cageno` (NOT NULL, no default) on `table_cages`.
