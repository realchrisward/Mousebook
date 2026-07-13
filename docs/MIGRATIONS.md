# Mousebook — Schema Migrations

## The question this answers

*"If migrations live in the repo, how does a user know which one applies to their database?"*

**They don't need to know.** Requiring an operator to work out which migration matches their database
is a design defect — they will get it wrong, at the worst possible time, on the only copy of their
colony data.

Two mechanisms make the question unnecessary:

1. **The database records what has been applied**, in a ledger table (`mb_schema_version`). The
   runner reads it, compares against `migrations/`, and applies only what is missing.
2. **Every migration is convergent** — it inspects the database and changes only what is not already
   in the target state. So even a database with *no* ledger and an unknown history (which is exactly
   what every existing Mousebook install is today) converges correctly.

The operator runs one command. The tooling works out the rest.

---

## Using it

**`mb_migrate.sh` is the only migration script you run.** The files in `migrations/` are never
invoked by hand — the runner executes them, in order, and records each in the ledger. Running one
directly would leave the ledger lying about what happened to the database.

```bash
./mb_migrate.sh --db mycolony preflight   # is a utf8mb4 conversion safe on this data?
./mb_migrate.sh --db mycolony status      # what is applied, what is pending
./mb_migrate.sh --db mycolony apply       # apply pending migrations
./mb_migrate.sh --db mycolony stamp       # fresh installs only: record without running
```

There are exactly two scripts in the repo root, and they do different jobs:

| Script | Job |
|---|---|
| **`mb_migrate.sh`** | Runs migrations. The launcher. |
| **`mb_schema_check.sh`** | Verifies a database *matches* the schema we ship. Also `--rebaseline`. |

Everything under `migrations/` is data for the runner: numbered steps, plus `migrations/lib/` which
holds shared code (the charset safety check) and is never executed as a step.

Connection details come from the environment, same as `setup.sh`:
`DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`.

### `status` output

```
==============================================================
 Mousebook migrations — database: mycolony
==============================================================
 applied: 1
   [x] 001_innodb_utf8mb4.sh  (applied 2026-07-12 15:22:04)
 pending: 0 — this database is up to date.
==============================================================
```

An old install that has never seen the runner simply shows `applied: 0` and lists everything as
pending. That is not an error state — it is the normal starting point.

---

## Applying to a live install — the runbook

Do this on the host, from the repo root. It takes under a minute for a colony of any realistic size
(a 5k-animal colony converts in **under 1 second**), but the backup step is not optional.

```bash
# 0. Set the connection once (same variables setup.sh uses)
export DB_HOST=localhost DB_PORT=3306 DB_USER=youradminuser DB_PASS='...'

# 1. BACK UP BOTH DATABASES. DDL cannot be rolled back; this is your only undo.
mysqldump -h "$DB_HOST" -u "$DB_USER" -p mycolony  > mycolony-$(date +%F).sql
mysqldump -h "$DB_HOST" -u "$DB_USER" -p userbook  > userbook-$(date +%F).sql

# 2. PREFLIGHT — is a utf8mb4 conversion safe on this data? (see hazard below)
./mb_migrate.sh --db mycolony preflight
./mb_migrate.sh --db userbook preflight

# 3. See what's pending (changes nothing)
./mb_migrate.sh --db mycolony status
./mb_migrate.sh --db userbook status

# 4. Apply. Prompts for backup confirmation; converts engine -> charset -> indexes.
./mb_migrate.sh --db mycolony apply
./mb_migrate.sh --db userbook apply

# 5. Confirm — twice over
./mb_migrate.sh --db mycolony status                    # pending: 0
./mb_schema_check.sh --db mycolony --kind colony        # COMPLIANT
./mb_schema_check.sh --db userbook --kind userbook      # COMPLIANT
```

**Run it against every Mousebook database — the colony DB *and* `userbook`.** They migrate
independently and each keeps its own ledger. `userbook` is 100% latin1/MyISAM today, so it needs this
just as much as the colony does.

**No application changes are required first.** The migration is transparent to the current code: the
existing `LOCK TABLES` paths work on InnoDB, and nothing in PHP names an engine. Migration can
therefore run ahead of the Track B code work.

---

## The charset hazard — read before running preflight

`ALTER TABLE ... CONVERT TO CHARACTER SET utf8mb4` converts the *characters* in a column, trusting
that the bytes stored there really are in the charset the column is declared as.

**Mousebook has never called `set_charset()`.** So a `latin1` column may well contain UTF-8 bytes,
written through a connection nobody configured. Converting those "correctly" mangles them
permanently:

```
stored in a latin1 column:   4D C3BC 6C6C6572          "Müller"  (UTF-8 bytes)
after CONVERT TO utf8mb4:    4D C383C2BC 6C6C6572      "MÃ¼ller"  <-- corrupted, irreversibly
```

The correct treatment for that case is a **binary round-trip** (`MODIFY col VARBINARY`, then
`MODIFY col VARCHAR ... CHARACTER SET utf8mb4`), which preserves the bytes and reinterprets them.

**The danger is narrower than it looks:**

- `utf8mb3` → `utf8mb4` is **always safe** — a strict superset.
- A `latin1` column containing only **ASCII** is **always safe** — latin1, utf8mb3 and utf8mb4 are
  byte-identical for ASCII.
- The **only** hazard is a `latin1` column holding **non-ASCII** bytes.

Colony data (line names, ear tags, cage IDs, genotypes) is usually pure ASCII, so most installs are
in the safe case — but "usually" is not a basis for running DDL on someone's animal records.

**So migration 001 checks rather than assumes.** Phase 0 scans every latin1 character column for
non-ASCII bytes and **aborts before touching anything** if it finds any:

```
  !! conversion_geno.allelegroupscombo: 1 non-ASCII row(s)
ABORTING: 1 latin1 column(s) contain non-ASCII bytes.
Run ./mb_migrate.sh --db <name> preflight for a full report...
```

Nothing is converted, nothing is recorded in the ledger, and the database is exactly as it was. Fix
the cause (or choose the binary round-trip for those specific columns) and re-run — migrations are
convergent, so re-running is always safe.

`./mb_migrate.sh --db <name> preflight` gives the full report, including a hex dump of the offending
values so you can tell double-encoded UTF-8 (`C3BC`) from genuine latin1 (`FC`). It runs the same
code as the migration's own gate — `migrations/lib/charset_guard.sh` — so the preflight answer and
the gate's answer can never disagree. Two copies of a safety check drift apart, and the copy that
drifts is the one guarding your data.

---



```sql
CREATE TABLE `mb_schema_version` (
  `migration`  varchar(128) NOT NULL,   -- filename, e.g. 001_innodb_utf8mb4.sh
  `checksum`   char(64)     NOT NULL,   -- sha256 of the file when it was applied
  `applied_at` datetime     NOT NULL,
  `applied_by` varchar(64)  NOT NULL,
  `outcome`    enum('applied','stamped') NOT NULL DEFAULT 'applied',
  PRIMARY KEY (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Created automatically on first contact. Lives **in each database it describes** — so a colony DB
carries its own history, and copying/restoring a colony carries its migration state with it.

**The checksum matters.** If a migration file changes after it was applied somewhere, the runner
warns loudly: the repo and the database now disagree about what actually ran. **A released migration
must never be edited.** Add a new one instead.

---

## Rules for writing a migration

1. **Convergent, not assumptive.** Inspect `information_schema`; act only on what is not already in
   the target state. Never assume a starting state — existing installs are in states nobody recorded.
2. **Safe to re-run.** A migration that has already been applied must be a clean no-op.
3. **Never edited after release.** The checksum will catch it. Add `002_…` instead.
4. **Portable across MariaDB 10.11 and MySQL 8.0.** In particular: `utf8mb4_unicode_ci`, never
   `utf8mb4_0900_ai_ci` (MySQL-only — this has already broken the project once).
5. **Numbered, monotonic, zero-padded:** `001_`, `002_`, … They apply in filename order.
6. `.sql` files are piped to the client. `.sh` files run with `DB_HOST`/`DB_PORT`/`DB_USER`/
   `DB_PASS`/`DB_NAME`/`CLIENT` in the environment — use these when the change requires inspecting
   the database first (which convergence usually does).

---

## Why DDL migrations need a backup, always

`apply` refuses to run until you confirm you have a backup, and that gate is not ceremony:

**DDL cannot be rolled back in MySQL or MariaDB.** `ALTER TABLE` auto-commits. There is no
transaction to abort. If migration 004 fails on the 30th of 37 tables, the first 29 are already
changed and there is no undo. Your recovery is the backup, or nothing.

This is also why convergence is a hard requirement rather than a nicety: after a partial failure, the
correct action is to fix the cause and **re-run** — and re-running is only safe if every migration
tolerates a half-migrated database.

---

## `001_innodb_utf8mb4.sh` — and the two traps it exists to handle

This migration converts every base table to **InnoDB** and **utf8mb4 / utf8mb4_unicode_ci**. Two
things about it were discovered by testing, not by reasoning, and both are recorded here because
they are easy to reintroduce.

### Trap 1 — the order is mandatory: engine first, then charset

`table_cages` has a `varchar(255)` **primary key** (`cageid`). At 4 bytes per character that is a
1020-byte key, over MyISAM's **1000-byte** key limit. Converting charset while the table is still
MyISAM fails outright:

```
ERROR 1071 (42000): Specified key was too long; max key length is 1000 bytes
```

InnoDB's limit is 3072 bytes, so the identical conversion succeeds once the engine has changed.

**Do not "optimise" phases 1 and 2 into a single combined `ALTER` loop.** The order is load-bearing.

### Trap 2 — a charset conversion on MyISAM silently truncates indexes

Worse than the hard failure is the soft one. On `table_animals`, converting to utf8mb4 while still
MyISAM **succeeds** — by quietly shortening the indexes to fit the 1000-byte limit:

```
KEY `fk_table_animals_table_lines1_idx` (`line`)         -- before
KEY `fk_table_animals_table_lines1_idx` (`line`(250))    -- after: a 250-char PREFIX
```

**Converting the engine to InnoDB afterwards does not restore them.** The truncation is permanent and
invisible unless you diff the schema.

Any install where someone hand-applied a charset fix — which is a real possibility, since the M1-G
collation work circulated as a patch and was never committed to the repo — is carrying this damage
right now.

It is not only a performance issue. A prefix index under a **`UNIQUE`** constraint enforces
uniqueness on the *truncated* value, which is a materially different constraint than the one
intended. The planned `UNIQUE (line, idno)` on `table_animals` would therefore mean something subtly
wrong on exactly those installs.

**Phase 3 of the migration repairs this**: the shipped schema declares no prefix indexes anywhere, so
any index with a `SUB_PART` is damage by definition, and is dropped and rebuilt at full length.

### Verified behaviour

Tested against three simulated installs in deliberately different states:

| Install | Starting state | Result |
|---|---|---|
| **A** | Pristine old install (MyISAM, latin1 + utf8mb3) | 35 tables → InnoDB, 35 → utf8mb4 |
| **B** | Someone hand-applied a partial charset fix on MyISAM | 35 → InnoDB, 34 → utf8mb4, **2 truncated indexes repaired** |
| **C** | Already fully converted | Complete no-op |

All three converged to a **byte-identical schema** (`mysqldump --no-data`, same sha256). That is the
property that matters: *the starting state does not need to be known.*

---

## Fresh installs

`setup.sh` creates the database from the install schema, which already reflects every migration
shipped to date. It then calls:

```bash
./mb_migrate.sh --db <name> stamp --yes
```

which records the migrations as applied **without running them**. A fresh install and a fully
migrated old install then have identical schemas *and* identical ledgers — and the next migration
applies to both the same way.

---

## Verifying a database: `mb_schema_check.sh`

There are two ways to arrive at a Mousebook database:

- **fresh install** — load the starter SQL;
- **upgraded install** — load an older starter SQL, then apply migrations.

**These must produce the same schema.** When they drift apart, new installs and upgraded installs
quietly become different products: a bug reproduces on one and not the other, and nobody can work out
why. It is a slow, silent, miserable failure — so it is worth a tool.

```bash
./mb_schema_check.sh --db mycolony --kind colony
./mb_schema_check.sh --db userbook --kind userbook
```

It builds a reference database from the starter SQL **plus every migration**, dumps both structures,
normalises away everything that legitimately differs (AUTO_INCREMENT counters, `utf8`/`utf8mb3`
spelling, the ledger table, statement order), and diffs what remains. Anything left is a real
structural difference.

```
 COMPLIANT — mycolony is structurally identical to the schema
 a fresh install would produce (starter SQL + migrations).
```

A `DIVERGENT` result has three likely causes, and the script lists them in order of probability:
migrations not applied; the starter SQL drifted from the migration chain; or someone hand-edited the
database.

### Frozen fixtures, and the CI gate

`tests/fixtures/schema_v0_*.sql` are frozen snapshots of the install schemas from before any
migration existed. **They are never edited.** CI loads them, migrates them forward, and asserts the
result matches what a fresh install produces today:

    (v0 fixture + migrations)  ==  (current starter SQL + migrations)

If someone edits the starter schema and forgets to write the migration, those two diverge and the
build goes red. That is the entire point, and it only works because the fixture stays frozen.

CI also asserts **idempotence**: applying migrations a second time must be a no-op. Convergence is
what makes it safe to re-run after a partial failure, so it is not optional.

### Rebaselining

```bash
./mb_schema_check.sh --rebaseline
```

Regenerates the CI drift baseline (`.github/ci/schema-baseline.tsv`) from the starter SQL. Do this
**only** when a schema change is intentional, and commit the result in the same pull request so the
change is visible in review. Never rebaseline to make CI green — that turns a real signal into
noise, and the next unintended drift sails straight through.
