# T0.3 — Result read-guard audit (all `fetch_*` dereferences)

**Scope:** every `mysqli_fetch_*(...)` / `$result->fetch_*()` call site in the
application tree (`index.php`, `php/`, `pages/`, `includes/`; `vendor/` excluded).

**Why this matters (PHP 8):** `mysqli::query()` returns `false` on failure
(missing table, absent/!EXECUTE-privileged stored proc, SQL error). Under PHP 8,
`mysqli_fetch_array(false)` raises a `TypeError` and `false->fetch_assoc()` raises
an `Error` — both are **fatal** and, with output already started, blank the page
from that point down (the #22 / #23 white-screen family). `MYSQLI_REPORT_OFF`
(set at bootstrap) only stops `query()` from *throwing*; it does not change the
`false` return, so the deref still fatals unless guarded.

**Guard styles accepted as safe (all already present in the codebase):**
- `if ($r instanceof mysqli_result) { … }` enclosing block (index.php, P1/P2 work)
- `while (($r instanceof mysqli_result) && ($row = mysqli_fetch_array($r)))` (query_animals.php)
- `while (($r) && ($row = mysqli_fetch_array($r)))` truthiness (filters.php `_mb_col`, litterlogger.php)
- `if ($r && ($row = mysqli_fetch_array($r)))` single-row (add_animals, manage_cages)
- ternary `($r instanceof mysqli_result) ? …fetch… : false/null` (index.php, manage_cages single-fetch)

## Result

**91 real fetch sites. After Track 0: 91 guarded, 0 unguarded.**
- 35 were already guarded before Track 0 (pre-existing P1/P2 hardening, query_animals, filters, litterlogger, cage_location).
- 56 were guarded in Track 0 (54 existing sites hardened + 2 written pre-guarded in the new autoclipsheet.php).

| File | Sites | Pre-guarded | Guarded in T0.3 | Now unguarded |
|---|---:|---:|---:|---:|
| index.php | 2 | 2 | 0 | 0 |
| php/add_animals.php | 16 | 4 | 12 | 0 |
| php/autoclipsheet.php | 2 | 0 | 2 (new file) | 0 |
| php/cage_location.php | 3 | 3 | 0 | 0 |
| php/cagecard_printer.php | 6 | 0 | 6 | 0 |
| php/cagerole.php | 4 | 1 | 3 | 0 |
| php/litterlogger.php | 5 | 5 | 0 | 0 |
| php/manage_alleles.php | 6 | 0 | 6 | 0 |
| php/manage_animals.php | 7 | 0 | 7 | 0 |
| php/manage_cages.php | 12 | 7 | 5 | 0 |
| php/manage_databases.php | 1 | 1 | 0 | 0 |
| php/manage_lines.php | 4 | 0 | 4 | 0 |
| php/manage_roles.php | 1 | 0 | 1 | 0 |
| php/manage_strains.php | 1 | 0 | 1 | 0 |
| php/manage_users.php | 3 | 3 | 0 | 0 |
| php/query_animals.php | 7 | 7 | 0 | 0 |
| php/query_genotodo.php | 1 | 0 | 1 | 0 |
| php/query_viewer.php | 2 | 0 | 2 | 0 |
| php/record_dead_pups.php | 4 | 0 | 4 | 0 |
| includes/auth.php | 2 | 0 | 2 | 0 |
| includes/filters.php | 2 | 2 | 0 | 0 |
| **Total** | **91** | **35** | **56** | **0** |

## Guards added in Track 0 (transform applied)

Loop derefs were rewritten to test the result before fetching, preserving the
original loop body:

```php
// before
while ($row = mysqli_fetch_array($results)) { … }
// after
while (($results instanceof mysqli_result) && ($row = mysqli_fetch_array($results))) { … }
```

Single-row derefs were made conditional:

```php
// before  (manage_cages.php maxcageno lookups)
$row = mysqli_fetch_array($results);
// after
$row = ($results instanceof mysqli_result) ? mysqli_fetch_array($results) : false;
```

## Notable / bespoke cases

- **includes/auth.php (2 sites).** Prepared-statement paths. The chained
  `$stmt->get_result()->fetch_assoc()` was split so the intermediate result is
  guarded: `$authres = $stmt->get_result(); $row = ($authres instanceof mysqli_result) ? $authres->fetch_assoc() : null;`.
  The `while` over the second `get_result()` was wrapped with the same
  `instanceof` test. A failed `execute()` now degrades to "no row" instead of a
  fatal.
- **php/manage_cages.php ~L824.** The reserved-cageno single-fetch already sat
  inside a `try { … } catch (\Throwable $e)` (a `TypeError` would have been
  caught), so it was not a live white-screen risk; it was still converted to the
  `instanceof` ternary so correctness no longer depends on catching a language
  `TypeError`, and the `try/catch` remains as a secondary net for the
  `reservations_cages`-absent case.
- **php/query_viewer.php (2 sites).** These sit inside the CSV-export path
  (headers already sent). A failed query here would have corrupted the download
  mid-stream rather than white-screening a page; guarded regardless.
- **php/autoclipsheet.php (2 sites).** New file (T0.7); both queries are written
  with `instanceof mysqli_result` guards from the start.

## Method

Sites were enumerated with a regex scan over the tree (comments excluded), each
site's result variable resolved, and a guard detected via same-line
`instanceof`/truthiness in the `while` condition or an enclosing
`if ($r instanceof mysqli_result)` / `if ($r …)` within scope. Classification of
"pre-existing" vs "added" was computed by re-scanning each file's `HEAD` content
(`git show HEAD:<path>`) against the working tree. Every changed file passes
`php -l`.
