# HANDOFF — Phase P4: issue #20 Group Euth / Group Comment (manage_animals)

**Repo:** `github.com/realchrisward/Mousebook` · **Branch:** `phase3_revisions`
**Baseline HEAD:** `c1475cf` — **P3 not yet committed; P4 is independent of it** (P4 touches only `manage_animals.php`; P3 touches `manage_cages.php` + `filters.php` — no overlap, either can land first).
**Deliverable:** `phaseP4_group_euth_comment.patch` (1 file, +52 / −9) — `git apply --check` passes on a fresh `c1475cf` clone.
**Validation:** `php -l` clean; JS `node --check` clean; 15/15 jsdom behavioral checks; 9/9 MySQL escaping checks.

---

## What it does

### 1. Group Euth / Group Comment (feature #20)
A **client-side** layer on the existing manage_animals editable table — no commit-path *logic* change. Two buttons appear above **Confirm Changes** whenever a managed set is loaded (embedded in `$testtable`, empty until `get_tempanimals` runs):

- **Group Euth** — reveals a `type=date` control that **auto-applies as the date is updated**: each change fans the value into every managed row's `dod<animalautono>` input.
- **Group Comment** — reveals a text box plus an **"Apply to all"** button that fans the text into every row's `newcomments<animalautono>` input.

Values land in the normal per-row inputs; the user reviews/edits and commits via the existing `confirm_changes` path. The group controls carry **no `name`**, so they are never posted. Target confirmed: the commit writes `newcomments<man>` (into `data_comments`), not the read-only `bulk comments` history column.

### 2. Commit-path SQL hardening (P2 fold-in — per your request)
The `confirm_changes` builder previously interpolated form fields raw into its UPDATE/INSERT. Now, matching the P2/P3 inline style:
- `line`, `idno`, `sex`, `eartag`, `dob`, `dow`, `dod`, `newcomments`, and the genotype `allele` / `allelegroup` are `real_escape_string`'d.
- `animalautono` (`$man`, sourced from the `mankey` client field) is `(int)`-cast everywhere it enters SQL.

`$conn` is live in this block (opened unconditionally at L69; the `get_tempanimals` block that closes it is skipped on a confirm request), so the escaping has a valid connection.

---

## Overwrite policy for Group Euth / Group Comment

Per-field **dirty-tracking**, plus a **load-lock** on DODs (your two refinements):

- Each per-row `dod` / `newcomments` input gets `oninput="mbMarkDirty(this)"`, setting `data-dirty="1"` when the **user manually edits** it. Group-applies skip dirty fields. Programmatic `.value =` does not fire `oninput`, so group-applies never mark a field dirty — repeated group changes keep updating still-pristine rows while hand-edited rows stay put.
- **DOD load-lock:** a row whose `dod` was **already populated when the record was retrieved** is rendered with `data-locked="1"` (server-side, based on the loaded DB value). Group Euth skips both `data-dirty` and `data-locked` fields, so a pre-existing DOD is **never** overwritten by a group euth. (The field remains manually editable and still commits; the lock only blocks the group action. It is a permanent per-load guard — even if the user clears the field, group euth will not refill that specific row.)

---

## Implementation (all in `php/manage_animals.php`)

1. Row `dod` input: added `oninput="mbMarkDirty(this)"` **and** a conditional ` data-locked="1"` when the loaded `$arraydod[$ck]` is non-empty.
2. Row `newcomments` input: added `oninput="mbMarkDirty(this)"`.
3. `$testtable`: inserted the Group Euth / Group Comment buttons + hidden control spans between `mankey` and Confirm Changes.
4. Inline `<script>`: added `mbManagedKeys`, `mbMarkDirty`, `mbToggleGroupEuth`, `mbToggleGroupComment`, `mbGroupEuthApply` (skips dirty + locked), `mbGroupCommentApply` (skips dirty). Iterates the `mankey` autono list.
5. Commit builder: `real_escape_string` on the string/date fields + `(int)` on `animalautono`, across the main UPDATE, the `data_comments` INSERT, and the `table_genotypes` UPDATE.

---

## Deploy ordering

Single file, no dependencies, no migration — deploy `php/manage_animals.php` on its own. Independent of the pending P3 patch.

---

## Validation detail

- **jsdom (15/15)** — real shipped JS driven against a 4-row DOM (101–103 unlocked, 104 loaded with a DOD + `data-locked`): euth auto-apply writes the unlocked rows, **skips the locked 104**, marks none dirty; simulated typing marks dirty; euth/comment re-apply update non-dirty rows and preserve hand-edited ones; toggles; empty `mankey` returns `[]` with no throw.
- **MySQL 8 escaping (9/9)** — the exact post-edit commit builder run with `DROP TABLE` payloads in `line` / `idno` / `newcomments` / genotype `allele`: all three tables survive and every payload is stored as **literal text**; the int-cast `animalautono` is inert.

---

## Carry-forward (unchanged)

- P5 (Phase G / #19 userbook management) and P6 smalls (`autoclipsheet.php` L11 parse error; committed-in-repo handoff/patch-file housekeeping; `instanceof mysqli_result` read-guard audit; orphaned-table sweep incl. the now-unused `temp_cage1..4` from P3) remain queued.
- Note: a benign pre-existing trailing space inside the main UPDATE's SQL string triggers a cosmetic `git apply` whitespace warning; it applies cleanly and was left untouched to keep the diff minimal.
