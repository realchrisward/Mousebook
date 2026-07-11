# AUDIT — M1-B (#25): allele↔line side-effects — 2026-07-11

**Branch:** `phase3_revisions`  **Base HEAD:** `0036446`
**Purpose:** characterize what happens to animal genotype data when an allele
group is added, removed, re-added, or a line is deleted *after* animals already
exist on that line. Records the failure modes that **M1-E (#28, admin repair
surface)** is expected to remediate, and states what M1-B guards now vs. defers.

## Data model (as-shipped)

- **`key_allelebyline (id, line, allelegroup)`** — the per-line allele-group
  assignment. `id` is a surrogate PK; there is **no** natural uniqueness on
  `(line, allelegroup)` (schema L266–273).
- **`table_genotypes (genoid, allelegroup, allele, comments, animalautono)`** —
  one row per animal per allele group. Surrogate `genoid` PK; **no** uniqueness
  on `(animalautono, allelegroup)` (schema L566–575).
- **`php/add_animals.php`** builds the per-animal genotype columns by reading the
  *current* `key_allelebyline` for the selected line at generate time
  (`add_animals.php` L282, L300). A `table_genotypes` row is written per animal
  per assigned allele group at confirm time (L499–503).

The key consequence: genotype rows are created **only** at animal-creation time,
from the assignment set that existed **at that moment**. Nothing reconciles
existing animals when the assignment set later changes.

## Side-effect matrix

| # | Operation (on a line with existing animals) | Current behavior | Crash? | Disposition |
|---|---|---|---|---|
| 1 | **Add** allele group (`button_addallele`, `manage_lines.php` L128) | New assignment row added. **Existing** animals get **no** `table_genotypes` row for the new group; only animals created afterward are genotyped for it. | No | **M1-E backfill target** (unchanged) |
| 2 | **Re-add / double-add** the same group | Silent **duplicate** `key_allelebyline` rows (no unique key). `add_animals` masks it via `GROUP BY`, but counts and the M1-E surface see duplicates. | No | **Guarded in M1-B** |
| 3 | **Remove** allele group (`button_delallele`, L147) | Assignment row deleted; **existing `table_genotypes` rows are left in place** (orphaned). The M1-A cage-card query (`left join table_genotypes`) still renders the de-assigned group. | No | **Documented; deferred** |
| 4 | **Delete** line (`button_deleteline`, L89) | `table_lines` + all `key_allelebyline` rows for the line deleted; **`table_animals`/`table_genotypes` untouched** → orphaned animals + genotypes if the line had any. | No | **Documented; deferred** |

None of the four crashes at HEAD — the #24 allele-less work already made the
zero-genotype path degrade cleanly (empty geno rather than a fatal). These are
**data-integrity / stale-display** issues, not availability issues.

## What M1-B changes now

**#2 (duplicate assignments) — fixed.**
- `php/manage_lines.php` `button_addallele`: a pre-insert existence check
  (`SELECT 1 … WHERE line=? AND allelegroup=?`) skips the insert and reports
  *"skipped - allele group already assigned to this line"* instead of duplicating.
- `migration_unique_allelebyline.sql`: dedupes any pre-existing duplicates
  (keeps lowest `id`) and adds a UNIQUE index `uniq_line_allelegroup` on
  `(line(166), allelegroup(166))` as a race/direct-write backstop. (Prefix length
  is forced by MyISAM's 1000-byte key limit on utf8mb3 varchar(255) columns;
  166×3×2 = 996 B. Identifiers are short, so the prefix is exact in practice.)

## What M1-B does **not** change (deferred to M1-E / explicit decision)

- **#1 (add → existing animals ungenotyped).** This is the intended M1-E job:
  an admin-gated surface that, for a line that gained an allele group, finds the
  animals lacking a `table_genotypes` row for it, presents genotype select boxes,
  and inserts. **M1-E must guard against creating a second row for an animal that
  already has one** for that group (there is no unique key on
  `(animalautono, allelegroup)`), i.e. the backfill should target only animals
  with a missing row and treat re-runs as idempotent.
- **#3 (remove → orphaned genotype rows).** Deleting genotype history is
  destructive and reversible only from backup; it is deliberately **not**
  auto-remediated by a guard. Candidate resolutions to decide later:
  (a) leave as-is and document; (b) filter genotype display by the live
  `key_allelebyline` so de-assigned groups stop rendering without losing history;
  (c) offer an admin action to purge orphaned rows for a de-assigned group.
  Recommended default: (b) at the print/display layer (non-destructive), folded
  into the M1-E surface.
- **#4 (delete line with animals).** Out of M1-B scope. Recommend M1-E (or a
  dedicated guard) either block deletion of a line that still has live animals or
  require an explicit cascade choice; today it silently orphans.

## Non-goals / notes

- The `button_addallele` / `button_delallele` blocks in `manage_lines.php` build
  SQL by string concatenation (not parameterized). M1-B's existence check uses a
  prepared statement, but the surrounding INSERT/DELETE were left as-is to keep
  the patch surgical; this SQLi surface is a **separate** hardening item, noted
  here for the record, not addressed in M1-B.
- Migration targets each **colony** database (e.g. `animalbook`), not `userbook`.
