# Issue #22 follow-up — "Cages already in destination" + location dropdowns

**Patch:** `issue22_followup_cage_destination_list.patch`
(2 files: `includes/filters.php` +18, `php/cage_location.php` net +36)
**Base:** current `phase3_revisions` HEAD `4abaa04` (issue22_patch — the #22
guards are already committed upstream; this stacks directly on it).
`git apply --check` clean; `php -l` clean on both files.
**Deploy:** PHP-only, no schema/migration.

## What this does (per your spec)
1. **"Cages already in destination" box now populates.** Replaced the orphaned
   block (which looped a stale get_lines() result) with a real query: cages in
   the selected destination room (`locationB_selection`) that still hold a live
   animal (`dod IS NULL AND dob IS NOT NULL`) — cages whose mice were euthanized
   are excluded. Runs only once a destination is chosen. **Prepared statement**,
   since the room value is user-controlled.
2. **locationB (destination) = active locations only** — already the case via
   `location_assign_options()` (`WHERE active = 1`); left as-is, comment clarified.
3. **locationA (filter) = any room with a live-animal cage.** New dedicated
   `location_liveanimal_options()` in filters.php; cage_location's locationA now
   uses it instead of the shared `location_filter_options()`. Retired-but-occupied
   rooms appear (so you can filter to their live cages); active-but-empty and
   dead-only rooms do not.

## Why a new filters.php function (not editing the shared one)
`location_filter_options()` is used by six pages (manage_cages, manage_animals,
litterlogger, query_animals, record_dead_pups, and cage_location). Changing its
semantics would alter those five other filters, so the live-animal-occupancy
logic lives in a **separate** `location_liveanimal_options()` used only here.

## Validation (MySQL 8.0.46 + PHP 8.3.6, curl + cookie jars)
Data: RoomA/RoomB/RoomD active, RoomC retired; RoomD empty; RoomB has a live
cage (C_B1) and a dead-only cage (C_B2); RoomC (retired) has a live cage (C_C1).
- locationA = RoomA, RoomB, RoomC (live-cage rooms; RoomD empty excluded). ✓
- locationB = RoomA, RoomB, RoomD (active only; RoomC retired excluded). ✓
- destination RoomB → box = [C_B1] (dead-only C_B2 excluded). ✓
- destination RoomC → box = [C_C1] (occupancy-based, ignores retired flag). ✓
- destination RoomD → box = [] (empty room). ✓
- multiple live animals in one cage → cage listed once (GROUP BY dedupe). ✓
- injection `RoomB" OR "1"="1` → inert, no rows, no fatal (bound param). ✓
- disconnected / no session → no fatal, "please connect" (no #22 regression). ✓

## One gotcha worth noting
The first cut used `SELECT DISTINCT c.cageid ... ORDER BY c.cageno`, which MySQL 8
rejects (error 3065: ORDER BY column not in the DISTINCT select list — same
DISTINCT/GROUP-BY family as the ONLY_FULL_GROUP_BY constraint). Final form uses
`GROUP BY c.cageid, c.cageno ORDER BY c.cageno`, which dedupes and orders cleanly.

## Still open (unchanged from #22 handoff)
`addcage_single` move is not tier-gated (non-`button_*` name, so mb_guard_write()
misses it) — flagged in-code for the P6 non-button_* mutation-path audit. This
page's remaining string-interpolated SQL (`$sql_where_text`, the move UPDATE's
`locationB_selection`/`cageselection`) is P2 2b hardening.
