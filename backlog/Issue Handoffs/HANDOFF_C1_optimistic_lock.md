# Handoff — C-1 (#27a): Optimistic locking on animal edits

**Base:** `milestone2` @ `01a206a` ("patch B3")
**Branch produced:** `c1_optimistic_lock`
**Touches:** `php/manage_animals.php`, `CONCURRENCY.md` (2 files, +173/−30)
**Schema change:** none. **Migration:** none.

Closes the classic lost-update race documented in `CONCURRENCY.md §3` — the highest-severity
silent risk in the app. Built entirely on the `expect` seam B-3 shipped in `mb_write()`; this
task is the "wire the form into it" work that the B-3 handoff flagged as remaining.

---

## Design decisions (confirmed with Chris)

1. **No new column.** The optimistic-lock token is the **rendered values themselves**. Each
   editable row emits the as-rendered value of every write-set column as a hidden `orig_*`
   input; `confirm_changes` posts them back as `mb_write()`'s `expect`. Nothing to migrate,
   nothing to keep in sync.
2. **Full-row = the write-set** (`line, idno, sex, eartag, dob, dow, dod`, plus each genotype
   allele). Any of them moving under the editor is a conflict, **even fields the user didn't
   touch**. Rationale: a form that shows all fields implies the editor accepted the values they
   left alone, so a concurrent change is best settled by a row-level refresh. Read-only /
   cross-page columns (`currentcage, location, role, sourcecage, parents`) are **excluded** — a
   legitimate cage move elsewhere must not false-conflict an animal edit.
3. **Conflict UX:** whole-submit, all-or-nothing rollback; then re-render the affected animals
   with the **current DB values** and a notice naming every field that moved
   (`animal 500: eartag (you had "L", now "Z")`). The user sees the newer value and must
   deliberately re-apply any change they still want. Nothing is clobbered without the editor
   seeing the current value first.
4. **Genotype rows are locked too** (`orig_geno<i>-<animal>` → `expect`). Comments are
   append-only, so they have no conflict concept and are left as plain inserts.

---

## What changed in `php/manage_animals.php`

1. **Extracted the table renderer** (the whole `get_tempanimals` body) into
   `mb_build_animal_table($host,$accessun,$accesspw,$dbname,$sql_where_text)` returning
   `[$testtable,$genepost]`. The normal path calls it with the filter WHERE; the conflict path
   calls it with an explicit `animalautono IN (...)` list. This is what lets the conflict path
   re-render fresh values. Pure extraction — the render body is byte-for-byte the original,
   moved into a function.
2. **Original-value hidden inputs.** Per animal row: `orig_line/idno/sex/eartag/dob/dow/dod`
   (`htmlspecialchars(...,ENT_QUOTES)`). Per genotype select: `orig_geno<i>-<animal>` carrying
   the current allele.
3. **`expect` wiring in `confirm_changes`.** Builds `$expect` from the `orig_*` POSTs with the
   **same** empty-date→`null` normalisation as `$vals` (critical: otherwise an unknown NULL
   date reads back as `''` and would conflict on every save). Passes `['expect'=>$expect]` to
   the animal `mb_write()` and `['expect'=>['allele'=>...]]` to each genotype `mb_write()`.
4. **Conflict handling.** A `conflict` status increments a counter and forces the existing
   whole-submit rollback (all-or-nothing preserved). After rollback, on the now-clean
   connection, a scan re-reads the managed rows and enumerates **every** moved write-set column
   (not just the first one `mb_write()` short-circuits on), builds the `-CONFLICT` notice, and
   re-renders the editable table with fresh values via `mb_build_animal_table()`.

`CONCURRENCY.md §3` flipped from "Document / M2-A top priority" to **FIXED in C-1**, with the
behavior + rationale written up; triage table and checklist updated.

---

## Validation (all against PHP 8.3.6 + MariaDB 10.11.14, matching targets)

- **Lint:** `php -l` clean.
- **Behavioral seam (15/15)** via `mb_write()` with the page's exact `expect` construction:
  full-row conflict (Bob's untouched ear tag moved → his sex edit refused, nothing clobbered);
  **no** false-conflict on unchanged NULL dates; unchanged-repeat; genotype conflict;
  injection-shaped `orig_*` value treated as data (mismatch → conflict, no SQL run).
- **All-or-nothing (6/6):** an updated animal and a conflicting animal in one submit → **neither**
  persists; connection clean and reusable after rollback (for the re-render).
- **Field-name cross-check:** render-emitted `orig_*` names match confirm-read names exactly,
  genotypes included.
- **Full HTTP end-to-end** (real page, seeded write-tier session): render emits the `orig_*`
  fields with correct values → concurrent editor changes `eartag L→Z` → `confirm` (stale
  `orig_eartag=L`) returns **`-CONFLICT ... animal 500: eartag (you had "L", now "Z")`**, DB
  `sex` **stays F** (the user's change was rolled back, nothing clobbered), and the re-render
  shows `orig_eartag500="Z"` (fresh). 0 fatal errors.
- **Patch discipline:** `git apply --check` **and** a real apply + `php -l` + re-run of the
  15-assertion harness on a **pristine clone of `01a206a`** — all clean.

---

## Deployment

**PHP-only. No schema change, no migration, no data backfill.** The usual "migration SQL
precedes patched PHP" ordering does **not** apply here — just deploy the patched
`php/manage_animals.php` (and the doc). Works on every existing install immediately; the token
is computed from live row values at render time.

---

## Residuals / carried forward (not fixed here — no scope creep)

- **Pre-existing PHP 8 notices, NOT introduced by C-1:** `Undefined array key` at the
  genotype-select build (original lines ~154/155 and ~181; the `$aglist[$ag]['M'/'F'/'all']`
  unconditional access and the `.=` on an undefined key). These lines exist identically in the
  pristine tree; C-1 moved them verbatim. Visible only with `debug_mode=True`. Candidate for the
  existing #14 notice-hygiene class; left alone here.
- **`mb_write()` reports only the first differing column** per record. The page's post-rollback
  scan compensates for the *notice*; the fresh re-render shows all current values. A careful
  re-apply against the refreshed table won't trip a second conflict. If desired later,
  `mb_write()` could collect all conflicting columns, but that's a chokepoint change, not C-1.
- **F-2 CI "raw write" grep** must stay **off** until the write-sweep completes (per B-3
  handoff) — unchanged by C-1.
- **Track D audit hook** in `mb_write()` remains a deliberate no-op until Track D; C-1 changes
  nothing there — animal + genotype writes already route through the chokepoint, so they'll be
  audited for free when Track D lands.
- **Still open in Track C:** §1/§2 (autonumber TOCTOU + `idno` duplicate — atomic claim),
  §5 (per-user print queue). Untouched.
