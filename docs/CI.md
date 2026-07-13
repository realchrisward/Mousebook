# Mousebook — Continuous Integration

**What this is:** an automated check that runs on GitHub every time code is pushed. It does, in
about two minutes, the verification that was previously done by hand every session: lint the PHP,
and load the schema against **both** supported database engines.

**Why it exists:** Mousebook promises to run on MariaDB *and* MySQL (`DB_ENGINE_SUPPORT.md`). That
promise has been broken before — a `utf8mb4_0900_ai_ci` collation is valid on MySQL 8 and invalid on
MariaDB, and the schema shipped with it. The only reason it was caught is that someone stood up both
engines by hand and tried. CI makes that automatic and unskippable.

---

## 1. What runs, and when

### Triggers

| Trigger | When it fires | Why |
|---|---|---|
| **Pull request** into `master` or `dev` | Every PR, and every new push to that PR's branch | **This is the gate that matters.** A red check here means *do not merge*. |
| **Push** to `master`, `dev`, `phase*`, `m1*`, `m2*` | Every push to those branches | Catches a bad commit even when no PR is open. |
| **Manual** (`workflow_dispatch`) | You press "Run workflow" in the **Actions** tab and pick a branch | Re-run after a flake, or check a branch the filters above skip. |

If the same branch is pushed twice quickly, the older run is cancelled automatically — its result was
stale the moment new commits landed.

### Jobs

The workflow (`.github/workflows/ci.yml`) runs **three jobs in parallel**:

| Job | What it does | Runtime |
|---|---|---|
| **PHP lint (8.3)** | `php -l` on every `.php` file in the repo — ours *and* vendored. A vendored file that doesn't parse is just as broken as one of ours. | ~20s |
| **Schema load — MariaDB 10.11** | Starts a real MariaDB 10.11 server, loads both install schemas, verifies them. | ~90s |
| **Schema load — MySQL 8.0** | The same, against MySQL 8.0. | ~90s |

The two schema jobs are configured `fail-fast: false` **on purpose**: if MariaDB fails, you still
want to know whether MySQL failed too. Stopping at the first failure would hide half the answer.

---

## 2. What the schema job actually checks

Three checks, in order. Each is a separate failure mode with a different meaning.

### [1/3] LOAD

Both schemas must import with zero errors — into databases named **`ci_colony_check`** and
**`ci_userbook_check`**, deliberately *not* `animalbook` / `userbook`. The installer lets the
operator name the colony database (M1-H), so CI proves the schema doesn't secretly depend on being
called anything in particular. The test databases are dropped afterward.

### [2/3] INVENTORY

The expected objects must exist after the load:

| Object | Expected |
|---|---|
| colony tables | 37 |
| colony views | 13 |
| colony procedures | 10 |
| userbook tables | 5 |

This catches the nasty case where the schema file *loads* but a statement silently failed and an
object is simply missing.

### [3/3] RATCHET — engine + charset drift

Every table's `ENGINE` and `CHARSET` is compared against a checked-in snapshot,
`.github/ci/schema-baseline.tsv`.

**Read this carefully, because it is the check most likely to be misunderstood:**

> The ratchet does **not** assert the schema is *correct*. Today's baseline records
> `MyISAM` + `latin1` + `utf8mb3` — because that is what the schema actually ships.
> The ratchet asserts the schema has not **drifted**.

It fails when an engine or charset changes *without anyone meaning it to*. When a change **is**
intentional — the MyISAM → InnoDB migration, or a utf8mb4 conversion — you regenerate the baseline
and commit it **in the same pull request**, so the change appears as a reviewable diff instead of a
silent mutation.

**Why the baseline is engine-independent** (both of these were learned the hard way on the first CI
run, and both look like "drift" when they are not):

- **Charset spelling.** MariaDB and MySQL have variously spelled 3-byte UTF-8 as `utf8` and
  `utf8mb3`. The check normalises them.
- **Sort order.** `information_schema.table_name` collates **case-insensitively on MariaDB** and
  **case-sensitively on MySQL** — so the `Study_*` tables land in a different position, and every
  line after them appears to have moved. **Never let the server sort.** The snapshot is sorted in the
  shell with `LC_ALL=C`, so both engines produce identical bytes.

A related trap, in the same script: the MySQL client prints *"Using a password on the command line
interface can be insecure"* to stderr on every invocation. If stderr is merged into stdout, that
warning is captured as **query data** — producing gems like `expected 37, found
mysql:[Warning]...37`. The password is therefore passed via `MYSQL_PWD`, and stderr is never merged
into a data query.



- The **userbook schema is 100% `latin1`** — all five tables.
- The colony schema is **11 `latin1` + 24 `utf8mb3` + 2 `utf8mb4`**.
- There is no `migrations/` directory in the repo on any branch.

So fresh installs are *not* utf8mb4, despite the M1-G collation work. Combined with the missing
`set_charset()` call in the application (zero occurrences repo-wide), this is the mojibake bug. The
ratchet's job is to make sure that when Track B fixes it, it *stays* fixed.

---

## 3. Reading the output

Go to the **Actions** tab, click the run, click a job. Or, on a pull request, click **Details** next
to the check.

### Success looks like this

```
==================================================
 Mousebook schema check — MariaDB 10.11
==================================================
 server: 10.11.14-MariaDB

--- [1/3] Loading schemas into non-default database names ---
  OK   colony schema   -> ci_colony_check
  OK   userbook schema -> ci_userbook_check

--- [2/3] Object inventory ---
  OK   colony tables            37
  OK   colony views             13
  OK   colony procedures        10
  OK   userbook tables          5

--- [3/3] Engine + charset ratchet (vs .github/ci/schema-baseline.tsv) ---
  OK   42 table(s) match the baseline exactly

==================================================
 PASS — MariaDB 10.11: schemas load, inventory correct, no drift
==================================================
```

and, for lint:

```
PHP lint: 47 file(s) checked, 0 failure(s)
OK — all files parse.
```

A green check mark on the PR means: **every PHP file parses, and the schema loads cleanly on both
engines with the expected objects and no unintended engine/charset changes.** It does *not* mean the
application works — there are no functional tests yet. It means the floor is intact.

### Failure — a syntax error

```
FAIL  ./php/manage_animals.php
      PHP Parse error:  syntax error, unexpected ';' in ./php/manage_animals.php on line 412
--------------------------------------------------
PHP lint: 47 file(s) checked, 1 failure(s)
```

GitHub also annotates the offending line inline in the PR's **Files changed** view.

**What it means:** a page with a syntax error is a white screen in production. Always a real bug.
**What to do:** fix the syntax, push again.

### Failure — the schema won't load

```
--- [1/3] Loading schemas into non-default database names ---
::error::colony schema failed to load into ci_colony_check
ERROR 1064 (42000) at line 386: You have an error in your SQL syntax ...
```

**What it means:** the install schema is broken *on that engine*. Note **which engine's job failed** —
if MariaDB is red and MySQL is green, you have written something MySQL-only (this is exactly the
`utf8mb4_0900_ai_ci` failure mode), and a fresh install on a Pi would fail.
**What to do:** the error line number is real; fix and re-push.

### Failure — inventory mismatch

```
  FAIL colony tables             expected 37, found 36
```

**What it means:** the schema loaded but an object is missing — a statement silently didn't create
what it should have. Or you legitimately added/removed a table, in which case the expected counts at
the top of `.github/ci/check_schema.sh` need updating in the same PR.

### Failure — schema drift (the ratchet)

```
::error::schema drifted from .github/ci/schema-baseline.tsv

  Lines starting '-' are the baseline; '+' is what this run produced.
  -colony	table_animals	MyISAM	utf8mb3
  +colony	table_animals	MyISAM	utf8mb4
```

**Two very different meanings — decide which one you're in:**

- **You meant to do this** (e.g. the InnoDB migration, or a utf8mb4 conversion). Regenerate the
  baseline and commit it in the same PR — see below.
- **You did not mean to do this.** Something edited an engine or charset by accident. This is
  precisely what the check exists to catch. Fix the schema, don't touch the baseline.

**Never regenerate the baseline just to make CI green.** That converts a real signal into noise, and
the next unintended drift will sail through.

---

## 4. Running it locally

Everything CI runs is a plain shell script you can run yourself — CI is not a special environment,
just a scheduled one.

**Lint** (needs only `php`):

```bash
bash .github/ci/lint.sh
```

**Schema check** (needs a reachable MariaDB or MySQL):

```bash
DB_HOST=127.0.0.1 DB_PORT=3306 DB_USER=root DB_PASS=yourpassword \
    bash .github/ci/check_schema.sh
```

It creates `ci_colony_check` / `ci_userbook_check`, verifies them, and drops them. It does not touch
your real databases — but it does *drop those two names* first, so don't use them for anything else.

**Regenerating the baseline** (only when an engine/charset change is intentional):

```bash
DB_HOST=127.0.0.1 DB_PORT=3306 DB_USER=root DB_PASS=yourpassword \
    bash .github/ci/regen_baseline.sh

git diff .github/ci/schema-baseline.tsv    # review it
```

Commit the regenerated baseline **in the same PR** as the schema change that caused it.

---

## 5. Limitations — what CI does *not* tell you

Being explicit about this, because a green check can breed false confidence:

- **There are no functional tests.** CI proves the code parses and the schema loads. It does not
  prove that adding an animal works, that login works, or that a page renders.
- **The MySQL 8.0 leg is unproven until it runs on GitHub.** Everything above was validated locally
  against MariaDB 10.11; there is no MySQL server in the local development container. The MySQL job
  is written to be correct, but *expect the possibility of a first-run adjustment* — and note that
  if it goes red on the first run, that is CI doing its job, not CI being broken.
- **Vendored code is linted but not audited.** `php -l` on PHPMailer proves it parses, nothing more.

---

## 6. Planned additions

As the Track B work lands, CI gains gates that are impossible today:

| Gate | Blocked on |
|---|---|
| **Write-chokepoint check** — grep asserting no direct `UPDATE`/`INSERT`/`DELETE` against a tracked table outside `mb_write()`. This is what makes the audit trail's "you cannot forget to log" guarantee *enforced* rather than merely intended. | `mb_write()` (Track B-3) |
| **Engine assertion** — once the InnoDB migration lands, the baseline records `InnoDB` for all 37 tables, and any table that reappears as MyISAM fails the build. | Track B-1 |
| **`set_charset` assertion** — a grep ensuring no `new mysqli(` outside `mb_connect()`. | Track B-2 |
| **Smoke test** — load the schema, seed a user, and exercise login + add-animal over HTTP with a curl cookie jar. | Track A-2 (seed data) |
