# Handoff — B-2: `mb_connect()`, one connection helper

**Branch:** `milestone2` (applies on top of B-1b)
**Patch:** `B2_mb_connect.patch` — `git apply --check` clean on a pristine clone
**New file:** `includes/db.php`
**Touched:** 27 PHP files (24 pages/includes + `index.php` + `pages/databases.php`)
**Scope doc:** `backlog/SCOPE_2026-07-13.md` §4, B-2

---

## What landed

| | Count |
|---|---|
| `new mysqli(...)` → `mb_connect(...)` | **140 sites, 24 files** (the 07-12 scope said ~18 sites — see the B-1b handoff) |
| `debug_mode` blocks → `mb_debug_init($config)` | **24 blocks** |
| `set_charset('utf8mb4')` calls before this patch | **0** |
| `LOCK TABLES` sites removed | **5** (`add_animals` ×2, `manage_cages` ×3) |
| `multi_query()` stacked-statement sites removed | **3** (all in `manage_cages`) |

### `includes/db.php`

- **`mb_connect($host,$user,$pass,$db,$port=null)`** — connects, then calls `set_charset('utf8mb4')`.
  **Returns a `mysqli` object even on failure**, with `->connect_errno`/`->connect_error` populated. That
  is a hard compatibility requirement, not laziness: every page is written as
  `$conn = new mysqli(...); if ($conn->connect_error) { ...show a message... }`. Returning `null` or
  throwing would convert each of those *graceful* branches into a fatal on a null-object call — a white
  screen instead of a login prompt, on the one code path whose entire job is to handle an unreachable
  database. The helper sets `MYSQLI_REPORT_OFF` around the connect and **restores the previous mode**, so
  each caller's query-error behaviour is unchanged. (Today that mode happens to be OFF globally because
  `session.php` sets it — but that is action-at-a-distance, and not every entry point loads `session.php`.)
- **`mb_debug_init($config, $announce=false)`** — the debug block, in one place.
- **`mb_request_id()` / `mb_actor()`** — per-request identity for the audit trail. Cheap to establish
  here, impossible to reconstruct later; Track D consumes them.

### `LOCK TABLES` — removed, and why that is not a regression

All five sites wrapped **exactly one DML statement**. A single statement is already atomic, so the lock
was buying nothing. It also could not stay: **under InnoDB, `LOCK TABLES` implicitly `COMMIT`s** and takes
the session out of transactional mode — it would have silently defeated the transaction `mb_write()` opens
in B-3.

**Stated plainly, because it matters:** in `add_animals.php` the lock never covered the `MAX(...)` read
that precedes the reservation write, so it never closed the read-then-reserve race it appeared to guard.
Two users adding to the same line can still claim the same `idno` — that was true before this patch and
is true after it. **That race is C-2's job** (atomic claim + `UNIQUE (line, idno)`). Removing a lock that
was not preventing it changes nothing about the exposure.

### `multi_query()` → prepared statements (bundled, shares the touch points)

The three `manage_cages` reservation paths built `LOCK TABLES …; DELETE/INSERT …; UNLOCK TABLES;` as one
string and sent it through `multi_query()`. Beyond the lock issue, **`multi_query()` enables stacked
queries on that connection** — the mechanism that turns a SQL injection from "read one row" into "drop the
table". They are now single prepared statements with bound parameters. Three fewer `real_escape_string`
sites, three fewer stacked-query surfaces (E-2 progress, taken while the file was open).

---

## Validation (PHP 8.3.6 + MariaDB 10.11, live harness)

**Every page exercised over HTTP, authenticated, with a curl cookie jar** — the check that a
140-site single-pass refactor actually needs, because its failure mode is a silently white-screened page:

| | Result |
|---|---|
| Login POST (`xusername`/`xpassword`/`dbname`) | HTTP 200, session established |
| **24 pages GET, authenticated** | **all HTTP 200; zero fatals; zero warnings; zero `connect_error` branches** |
| `php -l`, every PHP file | clean |
| `git apply --check`, pristine clone | clean |

Pages covered: `index`, `databases`, `manage_animals`, `add_animals`, `manage_cages`, `cage_location`,
`cagerole`, `litterlogger`, `query_animals`, `query_genotodo`, `query_viewer`, `record_dead_pups`,
`manage_lines`, `manage_strains`, `manage_alleles`, `manage_roles`, `manage_users`, `manage_databases`,
`genotype_backfill`, `cagecard_printer`, `autoclipsheet`, `change_password`, `forgot_password`,
`set_password`.

### The charset bug, demonstrated rather than asserted

Simulating a host whose client library default is **latin1** (which is what the live RHEL/MySQL box looked
like — it is *why* the schema was latin1), writing the strain name `Müller`:

| | connection charset | bytes stored | read back |
|---|---|---|---|
| **pre-B-2** (`new mysqli`, no `set_charset`) | `latin1` | `4D` **`C383C2BC`** `6C6C6572` | `MÃ¼ller` |
| **B-2** (`mb_connect`) | `utf8mb4` | `4D` **`C3BC`** `6C6C6572` | `Müller` |

The pre-B-2 row is **double-encoded on disk**. It is not a display bug — the wrong bytes are written, so
no amount of "reading it differently" recovers the original. This is the failure `set_charset()` prevents,
and it became *more* likely with B-1b, not less: the columns are now utf8mb4, so a connection left at the
server/client default is the last remaining link in the chain.

---

## Open / residual

1. **Connection churn is unchanged.** `mb_connect()` does not pool or cache: `add_animals.php` still opens
   ~20 connections per request, exactly as it did before. That was deliberate — a per-request cache
   interacts with the ~120 `$conn->close()` calls scattered through these files (a cached handle handed
   back after a `close()` is a fatal), and mixing that hazard into a 140-site mechanical pass would have
   made a clean refactor unreviewable. Now that construction is centralised it is a contained follow-up.
   **Suggested: G-track, after B-3.**
2. **`multi_query()` remains at 5 other sites** (`manage_cages:406`, `manage_lines:95`, `manage_animals:374`,
   `litterlogger:152`, `record_dead_pups:128`) — batch-update paths, not reservation paths. They are
   **E-2** (the parameterization sweep), not B-2, and were left alone rather than expanding scope.
3. **`mb_actor()` reads `$_SESSION['username']`** — confirm that is the key Phase F sets before Track D
   relies on it (B-3 will exercise it).
4. **No behaviour change was intended anywhere.** If a page misbehaves after deploy, the first suspect is
   the `require_once` insertion point, which is mechanical (immediately after the first `<?php`).

---

## Next

**B-3** — `mb_write()`: read the before-image, diff, write the record **and** its audit rows in one
transaction; `manage_animals` as the first caller. The two things it needs now exist: a real transaction
(B-1/B-1b) and a single place that owns the connection and the actor/request identity (this patch).
