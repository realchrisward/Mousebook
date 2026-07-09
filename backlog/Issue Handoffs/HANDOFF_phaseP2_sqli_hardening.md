# HANDOFF — Phase P2 (2b) SQL-Injection Hardening

**Repo:** `github.com/realchrisward/Mousebook` · **Branch:** `phase3_revisions`
**Baseline HEAD:** `a675333` ("issue24 - fix for new lines without alleles")
**Deliverable:** `phaseP2_sqli_hardening.patch` (963 lines, 8 files) — `git apply --check` passes on a fresh `a675333` clone.
**Validation stack:** PHP 8.3.6 (mysqli+session) + MySQL 8.0.46 — the canonical harness. All changed files `php -l` clean; behavioral tests via the PHP built-in server + curl cookie jars against a live MySQL 8 instance.

---

## Scope decisions locked this session

1. **Option B** for the round-tripped WHERE: stop round-tripping raw SQL through a hidden field; round-trip the filter **values** and rebuild the WHERE server-side in `includes/filters.php`.
2. **manage_cages:** fold in the **2b temp_cage** staging parameterization + route filter-WHERE through the hardened library. **#21 subset multi-select stays separate** (it's a feature). **#8 V1 session-keyed staging deferred** — see "Deferred" below.
3. **add_animals:** full **split-and-parameterize** of the `multi_query` INSERT batch (also fixes the silently-swallowed `multi_query` error).

---

## What's in the patch (all validated)

### `includes/filters.php` — centralized builder foundation (+165 lines)
New block inside the existing `if (!function_exists('mb_filters_loaded'))` guard:
- `animals_filter_fields()`, `animals_filter_values_from_post()`, `animals_filter_hidden_fields()` — the value round-trip surface.
- Discrete escaped/allowlisted predicate helpers: `deadoralive_where()` (allowlist), `date_bound_where()` (validates `^\d{4}-\d{2}-\d{2}$` then escapes, else drops the predicate), `line_eq_where()`, `source_category_where()`, `cage_eq_where()`.
- `animals_where_build($conn,$vals)` — assembles the full inner WHERE (`1=1 AND …`); sex is allowlisted against `MB_SEX_OPTIONS`, location/role use the existing subquery helpers, text filters use REGEXP word-boundary, dates are validated.
- `comment_regexp_escaped()`, `genotype_or_where($conn,$ag,$gf)` (normalizes non-arrays, returns `[where_or_text, group_count]`), `mb_int_values_list($raw)` (digit-only VALUES list for temp_cage batch).

**Unit-tested vs real MySQL:** every benign filter returns the correct rows; every injection payload is inert — string filters escape to a literal that matches nothing; sex/date reject via allowlist/format and the predicate safely collapses to "no filter" (never a UNION or bypass).

**Architecture note.** Fragment helpers use `real_escape_string` (prepared statements don't compose with reused WHERE fragments); **true prepared statements are reserved for discrete INSERT/UPDATE mutations** (add_animals). A consequence of Option B: because *values* — not SQL — now travel through the client, the old "single-quoted fragments only" round-trip constraint no longer applies, so the double-quoted helpers are safe.

### `php/query_animals.php` — Option B + genotype hardening
- Receive path rebuilds the WHERE from `animals_filter_values_from_post()` + `animals_where_build()` + `cage_eq_where()`; comment REGEXP now escaped.
- `get_genofilt` builds the predicate through `genotype_or_where()` (also fixes latent `count()`-on-string and `range(0,-1)` bugs from a zero-genotype line).
- Raw fragment region (2144 chars) deleted; hidden `animals_sql_where_text` field removed.
- **HTTP-tested:** benign `line_filter=KO` → exactly one option `KO-3`; `line_filter=KO` **+ injected `animals_sql_where_text=1=1`** → still only `KO-3` (old code would have leaked all rows); empty genotype filter → no crash.

### `php/manage_animals.php` — Option B
- Same receive/build rewrite; `$conn` now opened before the builder call (the page previously built query strings before opening the connection); hidden field removed.
- **HTTP-tested (discriminating):** `line_filter=KO` + injected `animals_sql_where_text=1=1` → still only `KO-3` (old code leaks all 3); UNION payload → no error, no leak.

### `php/add_animals.php` — split-and-parameterize
- Purge and reservation upserts: parameterized (`?`), LOCK/UNLOCK preserved as separate `query()` calls, redundant `` `$dbname`. `` prefix dropped (the connection's default schema is already `$dbname`). Reservation upsert uses an `AS newrow` row alias to avoid the `VALUES()` deprecation.
- Main batch: split into 4 **prepared statements** (`table_cages`/`table_animals`/`data_comments`/`table_genotypes`) prepared once, executed per row, halting on the first failure and reporting **which table** failed — this is the real fix for the swallowed `multi_query` error. `dob` binds as NULL-when-empty.
- **No transaction:** the four insert targets are **MyISAM**, so `begin_transaction`/`rollback` would be silent no-ops. The original `multi_query` never provided atomicity either; parameterization + per-statement error detection is the honest improvement. (True atomicity would require converting these tables to InnoDB — a separate migration decision.)
- **HTTP-tested:** a `DROP TABLE` payload in `line1` and an `OR "1"="1` payload in `parents1` are stored as **literal text**; `table_animals` survives; cage/comment created correctly; status `-successful`.

### `php/manage_cages.php` — filter-WHERE hardening + 2b temp_cage
- Both filter-build regions: the three raw interpolations (`$line_filter`, `$sex_filter`, `$source_category_selection`) now escaped inline, matching the pre-existing `$locf`/`$rolef` style.
- **2b temp_cage staging:** 8 single add/remove ops now use `(int)$animals_selection`; 4 batch adds source their VALUES from `mb_int_values_list()` (digits only) with an empty-guard. This closes a **serious live vector** — `animals_batchlist` was raw client SQL concatenated into `INSERT … VALUES`.
- **HTTP-tested:** single payload `1001); DROP …` → stored as int `1001`; batch `(1),(2)); DROP …` → stored as ints `1,2`; `table_animals` survives.

### `php/cage_location.php`, `php/cagerole.php`, `php/cagecard_printer.php` — Family-2 fragment hardening
- The three common raw filter interpolations escaped on each; plus `$locationA_selection` (cage_location) and `$roleA_selection` (cagerole). `$conn` confirmed open before each escape point. `php -l` clean. (Structurally identical to the HTTP-validated manage_animals escaping; not individually HTTP-driven.)

---

## Deploy ordering

1. `includes/filters.php` **first** (the pages call the new helpers; deploy the library before the pages).
2. Then the eight page files (order among them doesn't matter — no cross-page dependencies were introduced).

No schema migration is required for this patch.

---

## Deferred — the one remaining item

**manage_cages #8 V1 — session-keyed staging (concurrency isolation).**
The shared global `temp_cage1..4` (MyISAM) tables have no user/session column, so concurrent users collide. This is **not** an injection issue (all injection in these statements is now closed by 2b above) — it's an isolation/concurrency refactor, and it's large: ~25 touch points across the staging lifecycle — 4 add + 4 remove + 4 batch + 4 clear ops (≈lines 95–265), the purge (≈84), the staged-cage **display** (direct `SELECT … JOIN temp_cageN`, e.g. line 598), and the **8 commit statements** (`UPDATE table_animals JOIN temp_cageN … ` + comment INSERTs, ≈lines 334–412).

Chosen approach from the prior handoff is **Option A** (`$_SESSION`-array staging): hold the staged autono lists in `$_SESSION['mb_stage'][$dbname][1..4]`; rewrite the commit `JOIN`s as `WHERE animalautono IN (…)` from the session list; render the staged listboxes from the session array. Note Option A **replaces** the DB-backed staging statements entirely, so it should be done as one cohesive slice (the 2b parameterization shipped here protects the current DB-backed code in the interim and is not wasted under a session-column variant; under Option A those statements are superseded).

It was deferred to keep this patch a **single, fully-validated injection-hardening unit** rather than bundle a large concurrency refactor that needs its own full staging-lifecycle testing. Recommend it as the next slice.

**Also still separate:** #21 subset multi-select (feature); any remaining `->query` offenders outside the mapped families; the pre-existing `autoclipsheet.php` parse error (not touched here).

---

## Key gotchas encountered (for the next session's harness)

- **Never `sed -i` server-side** — resets inode group ownership (`christow:apache`) → silent white screens. Use `cat tmpfile > file` or `git apply` on a clone.
- **`dash`, not bash** in the tool shell — `source` is unavailable; use `bash -c '. script; …'`.
- **mysqld does not survive across tool calls** — bundle server start + dependent queries in one invocation; the datadir persists. Poll readiness with `mysqladmin ping`.
- **MyISAM insert targets** (`table_cages`/`table_animals`/`data_comments`/`table_genotypes`) — transactions are silent no-ops; `reservations_animals` is InnoDB.
- **`MYSQLI_REPORT_OFF`** is set at `includes/session.php` include time — needed so unauthenticated cold loads don't throw before the login form renders.
- Seeding needs `SET SESSION sql_mode=''` and an explicit `cageno` (NOT NULL, no default) on `table_cages`.

**Test scaffolding (NOT in the patch):** a synthesized `config.php` (gitignored) and `php/_seed.php` (untracked) were used to bypass login by pre-populating `$_SESSION['mb_dbaccess']['animalbook']`. Both are auto-excluded from `git diff`; delete `php/_seed.php` from any working clone before shipping.
