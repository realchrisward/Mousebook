# Issue #22 — cage_location.php crashes on empty/disconnected (P1)

**Patch:** `issue22_cage_location_guards.patch` (1 file, `php/cage_location.php`; +74 / −41)
**Base:** `phase3_revisions` HEAD (`4775fd2`, issue23_patch); `git apply --check` clean.
**Deploy:** PHP-only, no schema/migration. Apply the patch. (Server-side: `git apply`
on a clone or `cat tmp > file` — never `sed -i`.)

## Root causes (reproduced on MySQL 8.0.46 + PHP 8.3.6)
Two distinct whitescreens, both in the unguarded-result family:
1. **Fresh DB / disconnected session** → fatal at the `get_lines()` query
   (`mysqli object is already closed`). The top connect check echoes "please
   connect" but does **not** exit (matches sibling pages), so execution reaches
   the render-path queries and calls `->query()` on a failed connection.
2. **Selecting a location with no cages / empty colony** → fatal at
   `implode('"),("', $cage_batchlist)` with `$cage_batchlist` still `null`
   (no rows appended). `implode()` on null is a TypeError under PHP 8.

## Fix (consistent with the #23 pattern)
- `$dbconnected` flag set once at the top connect check; every DB-dependent
  render block (`get_lines`, the location dropdowns via `includes/filters.php`,
  the source-cage query, and the `addcage_single` move) is gated on it and only
  iterates a real `mysqli_result`. Disconnected now degrades to the login prompt
  with empty controls — no fatal.
- DB-dependent controls and `$cage_batchlist` are initialised up top, so
  `implode()` receives an empty array (no-op) instead of null, and empty controls
  don't raise undefined-variable warnings.
- The `addcage_single` move opens its connection inside the guarded handler.

## Validation (curl + cookie jars)
- R1 fresh/disconnected → no fatal, "please connect", form + sidebar render.
- R2 empty colony → no fatal (was the implode crash).
- R2b live session, select a location with no cages → no fatal.
- R3 with data → source cage listbox populates (H001, H002); location dropdown lists rooms.
- R3b filter to RoomA → only H001 shows.
- R4 move H001 → RoomB → "successful"; DB row updated to RoomB.
`php -l` clean.

## Two items flagged in-code, NOT fixed here (need your call)

### 1. "Cages already in destination" box is not populated (symptom 3) — PENDING
This box (`$cage_listbox`) is driven by an **orphaned block**: it loops a stale,
already-consumed result set from `get_lines()` (on a closed connection) and reads
a `cageid` column that result never had — so it is always empty. I guarded it so
it cannot fatal and left the content unfixed, because the intended query is a
design decision I don't want to guess.

My read of the UI (the box sits under the destination selector `locationB_selection`):
it should list cage IDs currently in the selected destination room, e.g.
`SELECT cageid FROM table_cages WHERE cagelocation_room = <locationB_selection>`
— optionally honouring retired-location rules. **Please confirm** the intended
contents (and whether it filters by locationB, active cages only, etc.) and I'll
add it. Note: `locationB_selection` is unsanitised, so I'd use a prepared
statement / escape here (ties into the P2 2b hardening).

### 2. `addcage_single` mutation is not tier-gated (P6)
The move posts as `addcage_single`, not `button_*`, so Phase F's
`mb_guard_write()` does not neutralise it for read-only users. I gated it on
`$dbconnected` for crash-safety but did **not** add a write-permission check —
that belongs to the P6 "non-button_* mutation-path audit." Flagged in-code.

## Next in P1
#22 was the last P1 crash item alongside #23. After the cage_listbox decision,
P2 (2b SQL-injection hardening) is next — and the `locationB_selection` /
`$cage_batchlist` / `$sql_where_text` interpolations on this page are part of
that offender map.
