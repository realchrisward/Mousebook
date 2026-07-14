# Handoff — B-1b: starter schema → InnoDB/utf8mb4, and the install path to the ledger

**Branch:** `milestone2` (off `origin/milestone2` @ `d32dc25`)
**Patch:** `B1b_starter_schema_innodb_utf8mb4.patch` — `git apply --check` clean on a pristine clone
**Files:** `mousebook_install_schema.sql`, `mousebook_userbook_install_schema.sql`, `setup.sh`,
`BACKUP.md`, `INSTALL.md`, `docs/MIGRATIONS.md`, `.github/ci/schema-baseline.tsv`
**Scope doc:** `backlog/SCOPE_2026-07-13.md` (§4, B-1b)

---

## The problem

B-1 shipped a migration framework that converts an *existing* database to InnoDB/utf8mb4. It did not
convert the **starter SQL**, and `setup.sh` never called the runner. So every fresh install still landed
on **40 MyISAM tables, 11 latin1 / 24 utf8mb3 / 2 utf8mb4** — and had no ledger row to say otherwise.

`docs/MIGRATIONS.md` already claimed the opposite, in two places: that the install schema "already
reflects every migration shipped to date", and that `setup.sh` "then calls `stamp`". The documentation
was right about the design; the code hadn't caught up. This closes that gap.

---

## The finding that changed the work

**`CONVERT TO CHARACTER SET utf8mb4` rewrites column *types*, not just charsets.**

Converting the starter by hand — flipping `ENGINE=MyISAM` → `ENGINE=InnoDB` and `DEFAULT CHARSET=…` →
`utf8mb4` — produced a starter that **no migrated database will ever match.** `TEXT` is capped by *bytes*
(65,535), not characters, so to preserve capacity the server silently promotes each blob/text column one
tier: `TINYTEXT`→`TEXT`, `TEXT`→`MEDIUMTEXT`, `MEDIUMTEXT`→`LONGTEXT`.

Five columns are affected:

| Database | Column | Was | Becomes |
|---|---|---|---|
| colony | `Study_Cohorts.CohortDesc` | `text` | `mediumtext` |
| colony | `Study_Info.StudyDesc` | `text` | `mediumtext` |
| colony | `table_deadpups.comments` | `text` | `mediumtext` |
| colony | `table_litterlog.litter_comments` | `text` | `mediumtext` |
| userbook | `dbaccess.db_guide1_url` | `mediumtext` | `longtext` |

**The CI convergence job caught this** — the first real thing it caught, one day after F-1 merged. Left
in, a fresh install and an upgraded install would have been different products: a bug reproducing on one
and not the other, with nothing in the tree explaining why.

Migration 001 is already merged and its checksum is recorded in the ledger of any database that has run
it, so **the starter is the side that moves** (rule 3: a migration is never edited after release). The
five columns were changed to exactly the types a migrated database has — derived by diffing
`information_schema.columns` between a migrated v0 fixture and a fresh load, not by reading the script.

`docs/MIGRATIONS.md` **rule 7** now records the rule and the mechanism.

---

## What the patch does

**1. Starter schemas → target state.** 42/42 tables `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci` (never `utf8mb4_0900_ai_ci` — MySQL-only). Five columns widened as above.
Seed rows (`Limbo` location, `Community` role) untouched.

**2. `setup.sh` — new Step 7b, "Record the schema version".**
- `load_schema()` now records each database it actually loaded in `FRESHLY_LOADED`.
- Freshly-loaded databases get `mb_migrate.sh --db <name> stamp --yes` — migrations recorded as satisfied
  **without running them**, because the starter already contains everything they would do. A fresh install
  and a fully-migrated old install then have identical schemas *and* identical ledgers.
- A database the installer **kept** (it already had tables — someone's existing colony) is **never
  stamped**. That would tell the ledger that migrations it never ran are done. It gets the runbook
  instead: `preflight` → `status` → `apply`, with the backup warning.
- A failed stamp **warns but does not fail verification** (no `note_failure`). The schema is correct
  either way and `mb_migrate.sh` is convergent, so the worst case is a later run offering to apply 001 to
  a database it finds nothing to do on. The likely cause is mundane: `mb_migrate.sh` connects over TCP,
  and a host may only grant the admin account a socket login. Crying wolf on the install's green-check
  wall would be worse than the thing being warned about.

**3. `BACKUP.md`.** `--single-transaction` flips from *wrong* to *correct* — but **conditionally**, with
`mb_migrate.sh status` as the check. All-InnoDB → `--single-transaction`, consistent snapshot, no locks.
Any table still MyISAM → `--lock-tables`, as before. A *partially* converted database is the worst of both
(no consistency benefit, all the locking cost) — which is why 001 converts every table in one pass.

**4. `INSTALL.md`.** Says plainly what happens when the installer meets a database that already has
tables: your data is never overwritten, and it is also not marked up to date — upgrade it deliberately,
after a backup.

**5. `.github/ci/schema-baseline.tsv`.** Regenerated via `./mb_schema_check.sh --rebaseline`: 42 tables,
all InnoDB/utf8mb4. This is the intended workflow — the engine/charset ratchet is *supposed* to go red on
a change like this, and the baseline moves in the same PR so the change is visible in review.

---

## Validation (MariaDB 10.11, PHP 8.3.6)

| Check | Result |
|---|---|
| Fresh load of both starters | **42/42 InnoDB + utf8mb4_unicode_ci**, 0 exceptions |
| Seed rows present after load | 1 cage location, 1 cage role |
| `stamp --yes` on a fresh database | ledger records 001 as `stamped`; `status` → `pending: 0` |
| **Convergence:** (v0 fixture + migrations) vs (converted starter) | **identical — colony and userbook** |
| Idempotence: second `apply` | no-op, `pending: 0` |
| Fresh (stamped) database vs `mb_schema_check.sh` | matches reference |
| `php -l` on every PHP file | clean (nothing PHP changed, but cheap) |
| `bash -n setup.sh mb_migrate.sh mb_schema_check.sh` | clean |
| `git apply --check` on pristine `origin/milestone2` | clean |

---

## Open / residual

1. **MySQL 8.0 is proven only by CI.** The harness above was MariaDB 10.11. The `milestone2` PR's CI run
   is the confirmation — **a red MySQL leg is a merge blocker.** Same class of surprise as the
   `utf8mb4_0900_ai_ci` incident.
2. **Deployment order** (unchanged, load-bearing): on live installs, migrate the database *before* the new
   tree goes live. For existing colonies this is `mb_migrate.sh preflight` → backup → `apply`, on the
   weekend window.
3. **Stamp-over-TCP.** `mb_migrate.sh` forces `--protocol=TCP`. Hosts that only grant the admin account a
   socket login will see the warning in Step 7b. Non-fatal by design; remediation is printed inline.
4. **Not in this patch, folded into B-2:** the 5 `LOCK TABLES` sites (`add_animals.php:71,178`;
   `manage_cages.php:80,412,842`). They belong with the code that replaces the connection.

---

## Next

**B-2** — `mb_connect()`: 140 `new mysqli(...)` sites across 24 files (not the ~18 the 07-12 scope
assumed), plus `set_charset('utf8mb4')` (still **zero** repo-wide — now the last link in the mojibake
chain, since the columns are utf8mb4 as of this patch), plus centralizing `debug_mode`, plus the
`LOCK TABLES` → transaction conversion. One mechanical pass, per-file patches, every page HTTP-exercised
with a curl cookie jar before delivery.
