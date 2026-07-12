# Issue #23 — index.php whitescreen + disconnected-login (P1)

**Patch:** `issue23_index_result_guards.patch` (1 file, `index.php`; +64 / −27)
**Base:** `phase3_revisions` HEAD (`3d23d8d`); verified `git apply --check` clean.
**Deploy:** PHP-only, no schema/migration. Apply the patch, done. (Server-side:
`git apply` on a clone or `cat tmp > index.php` — never `sed -i`.)

## Root cause (reproduced on MySQL 8.0.46 + PHP 8.3.6)
One fatal, three symptoms. `index.php` dereferenced the `get_colonystats()`
result with no guard (`$result->fetch_array()`). When that query *fails*
(returns `false`) the page fatals at that line and everything below it never
renders — colony-stats table, wean table, **and** the `mousebook.js` sidebar
toggle. That's why the stats/wean tables were empty and the sidebar was stuck
expanded with no toggle: all downstream of the same halt.

Empty data does **not** trigger it — `SUM()` over zero rows still returns a row.
The realistic fresh-install trigger is a **failed** query: the colony DB account
missing `EXECUTE` on the stored procs (SELECT/INSERT/UPDATE/DELETE granted,
EXECUTE forgotten), or a missing proc. Reproduced the exact fatal that way:
`Uncaught Error: Call to a member function fetch_array() on false ... :109`.

## Fix
1. **Colony-stats guard.** Only `fetch_array()` a real `mysqli_result`; else
   fall back to a zeroed row. Every stat var now `?? 0` (counts) / `?? ''`
   (month labels), so the page always renders. `$statsavailable` drives a small
   graceful notice under the stats header when the query failed (distinguishes a
   broken setup from a genuinely empty colony — empty colonies show 0s, no notice).
2. **Wean-table guard.** `mysqli_fetch_array(false)` is a TypeError fatal under
   PHP 8; the loop now only runs inside `if ($results instanceof mysqli_result)`,
   otherwise the header-only table renders.
3. **Disconnected-login redirect (option a).** A pre-DOCTYPE prologue (runs
   before any output, so `header()` is valid): if `button_login` is posted but
   there's **no live session** for the requested colony, redirect to
   `pages/databases.php` — the canonical single login origin. This covers both a
   fresh login typed into the index.php box **and** a dead-session reconnect via a
   stale colony button. A live-session colony button is unaffected (no redirect).

## Validation (curl + cookie jars against php built-in server)
- **A** cold GET, no session → 200, "please connect", no redirect (unchanged).
- **B** direct login on index.php (creds, no session) → **302 → pages/databases.php**.
- **C** dead-session reconnect (dbname+button_login, no creds, no session) → **302**.
- **D** live session, access user **without EXECUTE** (the original fatal) → 200,
  **no fatal**, stats header + graceful notice + wean header + `mousebook.js` all
  present (sidebar restored).
- **E** EXECUTE granted, empty colony → 200, no notice, count cells render `0 0 0 0`.
- **F** EXECUTE granted, with data → 200, Total=2, wean-eligible L-cage appears in
  the wean table.
`php -l index.php` clean.

## Next
#22 (`cage_location.php`) is the same unguarded-result / empty-`IN()` family —
apply the same guard pattern there next.
