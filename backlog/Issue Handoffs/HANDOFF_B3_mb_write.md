# Handoff — B-3: `mb_write()`, the single write chokepoint

**Branch:** `milestone2` — **applies on top of B-1b, then B-2**
**Patch:** `B3_mb_write.patch` — `git apply --check` clean on a pristine clone with the two prior patches applied
**New file:** `includes/write.php`
**Touched:** `php/manage_animals.php` (the first caller)
**Scope doc:** `backlog/SCOPE_2026-07-13.md` §4, B-3 — **this closes Track B.**

---

## What `mb_write()` guarantees

```php
mb_write($conn, $table, $pk_col, $pk_val, array $new_values, array $opts = [])
  → ['status' => 'updated'|'unchanged'|'notfound'|'conflict'|'error', 'changed' => [...], 'error' => '']
```

1. **Before-image read `FOR UPDATE`** — the row is locked for the life of the transaction, so read-then-write
   inside the chokepoint is no longer a race. (Impossible on MyISAM. This is what B-1 was *for*.)
2. **Only changed columns are written** — and therefore only changed columns will be logged.
3. **Record change + audit rows commit or roll back together.** A colony whose data says one thing and whose
   audit log says another is worse than no audit log, because it is *trusted*.
4. **Identifiers whitelisted, values bound.** Table/column names can't be parameterized, so they are pattern-
   checked; everything else is bound. (Tested with injected table and column names — both rejected.)
5. **Nestable transactions** (`mb_tx_begin/commit/rollback`, depth-counted): a submit editing six animals is
   **one** transaction. Any failure rolls back all six. Previously: six independent writes, and a failure on
   the fourth left three saved, three not, and a page that just said "-failed".

**Seams left open, deliberately, for the tracks that need them:**
- `opts['expect']` → **C-1** optimistic locking (already implemented and tested — just needs the form to post it).
- `mb_audit_record()` → **Track D**. `mb_write()` calls it *inside* the transaction if it exists, and no-ops if
  it doesn't. Track D becomes one new function, not another sweep of every page.
- **E-1** CSRF has exactly one place to verify a token now.

---

## The finding that matters: **B-3 does NOT fix the lost update (#27a)**

I wrote the opposite into a code comment, and the harness proved me wrong. Correcting it here, in the open:

> **The diff compares the form against the CURRENT ROW — not against what the user was shown.**

Alice and Bob both open animal 1 (`sex=F, eartag=L`). Alice changes the eartag; Bob changes the sex. Neither
touches the other's field. Both save.

| | outcome |
|---|---|
| **A. Old code** (rewrites every column from its stale form) | `sex=M eartag=L` — **Alice's edit silently lost** |
| **B. B-3 `mb_write()`, diff only** | `sex=M eartag=L` — **still lost.** Bob's stale `L` differs from the freshly-saved `R`, so the diff reads it as an intentional edit and writes it. |
| **C. B-3 + `expect` = the values the form was rendered with** | Alice: `updated`. Bob: **`conflict`** → `sex=F eartag=R`. Alice's edit survives; Bob is told the record changed under him instead of clobbering it. |

So: the diff makes the write minimal and the future audit trail truthful. **It does not make concurrent edits
safe.** Only (C) does, and (C) is C-1: render the form's original values as hidden fields and pass them as
`expect`. The mechanism is built and tested; what remains is the form. Track C's priority is unchanged, and
its cost just dropped.

---

## Second finding: the diff is only as good as the comparison

First harness run reported `dob` as changed **on every save**. `dob` is `DATETIME`; the form posts
`2026-01-01`; the column holds `2026-01-01 00:00:00`. As strings, those differ.

Left alone, that would have rewritten columns nobody touched and — once Track D lands — written
`dob: 2026-01-01 00:00:00 → 2026-01-01` into the audit log every time anyone opened and saved the form.
Forever. **An audit trail that cries wolf on every save is worse than none**: people stop reading it, and the
one real change is buried in a thousand fake ones.

Fixed with a **type-aware diff**: column types are read once per table from `information_schema` and cached,
then dates are compared as instants and numbers as numbers. `NULL` and `''` stay distinct — `dob NULL` means
"unknown", which is not the same claim as an empty string, and conflating them would quietly invent data.

---

## `manage_animals` — what changed

The `confirm_changes` save was a concatenated multi-statement string executed with `multi_query()`. It is now:
one transaction → `mb_write()` per animal → prepared `INSERT` for new comments → `mb_write()` per genotype row.
All-or-nothing: if any record fails, **nothing** is saved and the page says so.

Also removed: three **whole-table** sweeps (`UPDATE table_animals SET dob=NULL WHERE dob=0`, same for
`dow`/`dod`) that ran **once per animal per save**. They existed to clean up zero-dates written by this very
code path; `mb_write()` writes a real `NULL` for an empty date, so nothing generates them any more. **Carried
to Track C:** if any live colony still holds zero-dates from the old code, normalizing them is a migration's
job — not something to re-run on every keystroke.

---

## Validation (PHP 8.3.6 + MariaDB 10.11)

**Unit (12/12 pass):** diff writes only the changed column · repeat save = `unchanged` (nothing written) ·
`NULL` round-trips as `NULL`, never `0000-00-00` · `notfound` · `expect` mismatch → `conflict`, **row
untouched** · `expect` match → `updated` · injected table name rejected · injected column name rejected ·
table still intact after injection attempts · **simulated audit failure rolls the record change back**.

**End-to-end over HTTP** (real form POST, authenticated, cookie jar): edit animal 1's sex, leave animal 2
alone, change a genotype, add a comment →
`-successful... 1 changed, 1 unchanged [1: sex]`; animal 2 **not written at all**; `dob` correctly **not**
flagged; genotype and comment landed.

**Regression:** all 24 pages still HTTP 200, zero fatals. `php -l` clean. `git apply --check` clean on a
pristine clone with B-1b + B-2 applied.

---

## Open / residual

1. **Only `manage_animals` uses the chokepoint.** Every other write path is still direct SQL. That is the
   staged plan (scope §11: "prove it, then expand"), but until the sweep finishes, **F-2's CI grep must not be
   turned on** — it would fail the build for the writes we have not migrated yet. Turn it on when the sweep
   completes, table by table.
2. **`mb_write()` handles UPDATE only.** INSERT and DELETE still go direct. Track D will need them audited too;
   the natural shape is `mb_insert()`/`mb_delete()` sharing the same transaction and audit hook.
3. **`mb_column_types()` costs one `information_schema` query per table per request.** Negligible at colony
   scale, and it buys a correct diff. Revisit only if profiling says so.
4. **`data_comments` is append-only** — a new comment is a new row, so there is no before-image to diff. It is
   prepared, not concatenated, but it does not go through `mb_write()`.

## Next

**Track B is closed.** Track C is unblocked and C-1 is now cheap: **C-1** (post the rendered values as
`expect` — the seam is built and tested), **C-2/C-3** (atomic `idno` claim + `UNIQUE (line, idno)`),
**C-4** (index gaps, as migration `002`).
