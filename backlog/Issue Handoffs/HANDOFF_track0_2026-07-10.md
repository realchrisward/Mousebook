# HANDOFF — Track 0 (Phase 3 closeout) combined patch — 2026-07-10

**Branch:** `phase3_revisions`  **Base HEAD:** `524f439` ("new scope")
**Deliverable:** one `git apply`-compatible patch (`track0_phase3_closeout.patch`)
covering T0.2–T0.7, plus a standalone live-DB migration and the T0.3 audit.

Re-baselined from a fresh clone. `524f439` adds only `backlog/SCOPE_2026-07-10.md`
over the scope's pinned base `3cf276e` — no code drift; the tree equals the pinned
base. Validation harness: PHP 8.3.6 CLI (mysqli+session) and MySQL 8.0.46 (matches
production).

## What's in the patch, by scope item

**T0.2 — Phase F credential cleanup.** The leftover `$xpassword` was *dead code*,
not a live leak: all 15 ingest-pattern pages connect with the session-derived
`$accesspw`, and `$xpassword` had zero references after `mb_session_bootstrap()`
in every one. Eliminated the `$xpassword` PHP variable (every assignment/ingest,
including the isolated `if (isset($_POST['xpassword']))` wrappers and the
`button_disco` clears) across all 15 pages: add_animals, cage_location,
cagecard_printer, cagerole, litterlogger, manage_alleles, manage_animals,
manage_cages, manage_lines, manage_roles, manage_strains, query_animals,
query_genotodo, query_viewer, record_dead_pups. The `<input name="xpassword">`
login fields (the connect box, `value=""`) are untouched — that is the login
mechanism. `manage_roles.php`'s `xpassword` is that same login box (not a
password-set field), so it was swept too; the userbook password-*set* pages
(manage_users/manage_databases/change_password) use other field names and were
left alone.

**T0.3 — result read-guard audit (full classify-all-91).** All 91 real
`fetch_*` sites classified; 35 were already guarded, 56 hardened/written-guarded
in Track 0, **0 remain unguarded**. Loop derefs became
`while (($r instanceof mysqli_result) && ($row = mysqli_fetch_array($r)))`;
single-row derefs became the `instanceof` ternary; auth.php's chained
`get_result()->fetch_assoc()` was split and guarded. Full classification in
`backlog/AUDIT_T0.3_fetch_guards.md`.

**T0.4 — temp_cage orphan sweep.** Removed the dead `temp_cage1..4` tables and
the `clear_cages1234` / `get_cage1..4` procedures from
`mousebook_install_schema.sql` (survivors `get_activecages`, `get_cagecounts`
retained). Refreshed the now-stale note in `includes/filters.php`. For colony
databases created before this sweep, `migration_drop_temp_cages.sql`
(idempotent) drops the orphaned objects.

**T0.5 — housekeeping.** Removed the two dangling root patches
(`phaseG_issue19 (2).patch`, `phaseG_vendor_phpmailer (2).patch`; code already in
tree). Relocated `DEPLOY_phaseG (2).md` → `backlog/DEPLOY_phaseG.md` and
`HANDOFF_phaseG_2026-07-10.md` → `backlog/Issue Handoffs/`. Resolved the P2
handoff pair by keeping the 91-line superset (the `(1)` copy) under the canonical
name and dropping the 88-line one. Dropped the `(1)` suffix from the P4 handoff.

**T0.6 — denied-notice UX + username escaping.** Added a single central denial
banner in `mb_render_nav()` (fixed, top-centre; unset after render), so every
guarded page now surfaces a blocked-mutation notice instead of a silent no-op;
removed the now-redundant inline blocks from manage_users/manage_databases.
Wrapped all 16 raw `echo $xusername` sites in `htmlspecialchars()`.

**T0.7 — autoclipsheet.php (functional clip sheet).** Rebuilt the
non-functional FPDF prototype into a self-contained, session-authenticated
report that queries the colony DB for current litters (living animals in `L*`/`F*`
cages), groups pups by sex, derives each line's genotyping assays
(`key_allelebyline` → `key_allelegroupbygenotypingrxn`), and streams a portrait
clip-sheet PDF (per-litter section with DOB / clip-due=DOB+14 / wean=DOB+21 /
mating cage / assays / parents, and a pup table with blank sample-tube and
genotype columns). Auth/connection failures emit a one-line PDF notice (never
HTML-before-PDF). Added a "Clip Sheet" launch button on `index.php` next to
Litter Log (`target="_blank"`), and refreshed the stale "orphan" comment in
`includes/nav.php`.

**T0.1 — issues #19/#20/#21.** Code confirmed present in-tree (Phase G user/db
pages; group euth/comment in manage_animals; session-keyed multi-select staging
in manage_cages/filters). GitHub close is yours.

## Validation performed

- `php -l` clean on every changed PHP file (20 files).
- Schema loads cleanly into MySQL 8.0.46; post-load: 0 `temp_cage` tables, dead
  procs gone, `get_activecages`/`get_cagecounts` retained.
- autoclipsheet's two queries run against seeded sample data return the correct
  living-litter rows (dead pups and non-litter cages excluded; F-before-M then
  numeric idno ordering) and the correct per-line assays.
- The exact query+build+render path produces a valid multi-object PDF
  (`%PDF-1.3` … `%%EOF`).
- `migration_drop_temp_cages.sql` drops the objects and re-runs clean (idempotent).
- **`git apply` verified on a fresh `phase3_revisions` clone** at `524f439`: the
  patch applies cleanly; post-apply tree confirmed (dangling patches removed,
  docs relocated/deduped, schema swept, `$xpassword` gone, all spot-lint clean).

Note: the delivered patch is a full `git diff` (it carries the byte content of
the two deleted root `.patch` files, ~340 KB, which is why it is large). A
compact `--irreversible-delete` variant was rejected by `git apply`
("removal patch leaves file contents"), so the full form is the one to use.

## Deploy sequence

1. On a clone of `phase3_revisions`, `git apply track0_phase3_closeout.patch`,
   review, commit.
2. Fresh installs: use the updated `mousebook_install_schema.sql` (temp_cage
   objects already absent). No new config keys or dependencies for Track 0;
   `autoclipsheet.php` uses the already-vendored `php/fpdf.php`.
3. Existing colony (animalbook) DBs — optional cleanup: run
   `migration_drop_temp_cages.sql` once per colony (idempotent, safe to skip).
4. Close #19/#20/#21 on GitHub.

## Not touched / follow-ons

- `autoclipsheet.php` current-litter definition mirrors index.php's wean query
  (`LEFT(currentcage,1) IN ('L','F')`). If founders (`F*`) should be excluded
  from clip sheets, that's a one-line filter change — flag if wanted.
- P6 carry-over from the prior scope (pre-existing parse error in
  `autoclipsheet.php` L11) is moot — the file was fully rewritten here.

## Closed open items (recorded 2026-07-11, M1-A session)

- **`manage_cages.php:573` unguarded `mysqli_fetch_array()` — CLOSED under T0.3.**
  The pre-Track-0 open item (flagged in `HANDOFF_smtp_fix_2026-07-10.md` and the
  scope's "confirm before next session" list) is already resolved: the T0.3 sweep
  guarded all 12 fetch sites in `manage_cages.php` (7 pre-guarded + 5 hardened, 0
  unguarded — see `AUDIT_T0.3_fetch_guards.md`). Re-verified at HEAD `231e7d1`:
  the former L573 fetch now sits inside an `if ($results instanceof mysqli_result)`
  guard (block at L571), and every other fetch in the file is guarded by an
  `instanceof` wrapper or a truthy `$checkRes`/`$fr`/`$lr` short-circuit. No further
  action needed; do not re-open.
