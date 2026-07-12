# Mousebook — Multi-User Concurrency Behavior

**Applies to:** `phase3_revisions` @ `ddaafb8`
**Issue:** #27 (Milestone 1 / M1-F — *investigation & documentation half*). The
implementation half is **M2-A**.

Mousebook is a shared web application: several lab members may have the same colony
open at once. This document enumerates what actually happens today when two people
mutate overlapping data at the same time, and triages each behavior into **document**
(acceptable for the MVP, users must simply know about it) or **fix in M2-A**.

**No behavior is changed by M1-F.** The MVP ships with these risks *known and
documented* rather than silent. Every claim below was reproduced against MySQL 8.0.46
using the app's real schema, not inferred from reading alone.

---

## 0. Summary for users (read this if nothing else)

| If two people… | What happens today | Data loss? |
|---|---|---|
| **Add animals** on the same line at the same time | The second person's batch **fails loudly** and creates nothing (duplicate-number error). They retry and it succeeds. | **No** — fails safe. |
| **Edit the same animal(s)** in Manage Animals at the same time | **Last save silently wins.** The earlier person's edits are overwritten with no warning. | **Yes** — silent. |
| **Edit the same cage(s)** / move or reassign the same cages | Last save wins. Moves are idempotent-ish, so this is usually benign. | Rarely. |
| **Use the cage-card print queue** at the same time | The print queue is **global, not per-user** — they share one queue and will see each other's cages. | No, but confusing. |
| **Add animals to the same line back-to-back** | Ear-tag (`idno`) numbers can **silently duplicate**. | **Yes** — silent. |

**Practical guidance for labs today:** coordinate before two people edit the *same*
animals simultaneously, and treat the card-print queue as a shared workspace.

---

## 1. Animal creation — autonumber reservation (`add_animals.php`)

### How it works
There is a real reservation system. `reservations_animals` holds a per-user "I've
claimed numbers up to N" marker, and both the reservation write and the purge run
under `LOCK TABLES … WRITE` (L71–80, L178–187). On every page load the user's own
stale reservations are purged (per-user; it does **not** clobber other users').

### What's actually protected — and what isn't
The lock covers the **reservation INSERT**, but the `max()` reads that *compute* the
number (L124–172) happen **outside** it. That's a classic time-of-check/time-of-use
window:

> Alice and Bob both press *Generate*. Both read `max(animalautono) = 100`. Both
> compute the same next range (101…103). Reproduced: **both derived the identical
> range.**

Two further findings:

- **The reservation table can't dedupe per user.** `reservations_animals` has no
  unique key on `user` — its PK is the AUTO_INCREMENT `maxautono` column itself — so
  the `ON DUPLICATE KEY UPDATE` clause (L179) effectively never fires on `user`.
  Reproduced: one user accumulated **two** live reservation rows. The intended
  "one live reservation per user" invariant does not hold.
- **The PK collision is an accidental safety net.** Because the reserved number is
  written *into* the PK column, the second colliding reservation fails with error
  **1062 (duplicate entry)**. Reproduced.

### Terminal behavior (the part that matters)
The confirm step takes `minauto`/`maxauto` from **hidden POST fields** (L441–442) and
inserts `animalautono` **explicitly** (L470/L516) — it never re-validates the range
against the reservation table. So a collision does reach the real insert. But
`table_animals.animalautono` is the primary key, so the duplicate insert **fails with
1062**, `$ins_error` is set, and the batch **halts at the first failing statement —
nothing is written** (the M1-B DOB gate reuses this same plumbing).

**Net effect: the animal-add race fails safe.** The loser's batch is rejected wholesale
and can simply be retried; no partial or corrupted animal records. The user-visible
symptom is a confusing SQL error rather than a clean "someone else just took those
numbers, please retry" message.

**Triage:**
- **Document (MVP):** simultaneous *Generate* on the same colony can make one user's
  confirm fail with a duplicate-number error. **Retry — it will succeed.** No data loss.
- **Fix in M2-A:** (a) move the `max()` reads **inside** the existing `LOCK TABLES`
  window so the range is computed and claimed atomically; (b) add a `UNIQUE KEY` on
  `reservations_animals.user` so one-reservation-per-user actually holds; (c) re-validate
  the reserved range at confirm; (d) translate 1062 into a plain-English retry message.

## 2. Ear-tag / per-line numbering (`idno`) — **silent duplicate**

`idno` (the per-line animal number) is computed from `max(cast(idno as unsigned))` on
the same TOCTOU pattern, but — unlike `animalautono` — **`idno` carries no unique
constraint**. Reproduced: two animals on the same line were inserted with the **same
`idno`, accepted silently**.

This is the sharpest *silent* risk in animal creation: the autonumber collision is
caught by the PK, but the ear-tag number simply duplicates.

**Triage:**
- **Document (MVP):** after concurrent additions on the same line, **verify ear-tag
  numbers**; duplicates are possible and are not rejected.
- **Fix in M2-A:** derive `idno` inside the same atomic claim as the autonumber, and
  consider a `UNIQUE (line, idno)` constraint (needs a dedupe migration first — same
  shape as M1-B's `key_allelebyline` fix).

## 3. Animal edits (`manage_animals.php`) — **classic lost update**

`confirm_changes` reads every field from POST and writes whole-row `UPDATE`s. There is
**no version column, timestamp, or row-hash anywhere in the schema** (verified: no
`version` / `updated_at` / `modified` column exists), so nothing detects that the row
changed after the editor loaded it.

> Alice opens animal #500 and edits its genotype. Bob opens #500 and edits its
> comments. Alice saves. Bob saves. **Bob's whole-row write silently reverts Alice's
> genotype change.** Neither is warned.

This is the textbook lost update and the **highest-severity silent risk** in the app.
It applies to any two users editing overlapping animal selections.

**Triage:**
- **Document (MVP):** avoid simultaneous edits to the same animals; last save wins,
  silently.
- **Fix in M2-A (top priority):** optimistic locking — add a version/`updated_at`
  column, carry it in the form, and reject the write with a conflict notice if it
  moved. This is the core of M2-A.

## 4. Cage mutations (`cage_location.php`, `cagerole.php`, `manage_cages.php`)

- **Moves / role assignments** are `UPDATE … WHERE cageid IN (…)` — last writer wins.
  Because these set a single field to an absolute value, a concurrent double-move is
  usually benign (the cage ends up wherever the later user put it) rather than
  corrupting. **Document; low priority for M2-A.**
- **Cage-number reservation** (`manage_cages.php` L841+) uses `reservations_cages`
  under `LOCK TABLES … WRITE`, and — unlike the animal path — is wrapped in a
  `try/catch` that falls back to committed-MAX behavior if the reservation table is
  unavailable. It shares the same TOCTOU shape (the `max()` feeding `$basecageno` is
  read outside the lock), but `cageid` collisions are absorbed by the
  `ON DUPLICATE KEY UPDATE cageno=cageno` clause on the cage insert rather than
  failing. **Document; M2-A should give it the same atomic-claim treatment as §1.**

## 5. Cage-card print queue (`cagecard_printer.php`) — **shared, not per-user**

`CagesForPrinting` has **no `user` column** — it is a single global queue for the whole
colony database. Two users printing at once are operating on the *same* queue: cages
one adds appear in the other's list, and a bulk-remove or remove-by-color clears cages
the other person queued. Nothing is lost permanently (the queue is a scratch list, and
M1-A made its state reflect immediately), but it is surprising.

**Triage:**
- **Document (MVP):** the print queue is **shared across all users** of a colony;
  coordinate, or clear it when you're done.
- **Fix in M2-A (or M2 backlog):** scope `CagesForPrinting` per user (add a `user`
  column and filter on it) — the same pattern the reservation tables already use.

## 6. Reservation purge semantics (no cross-user damage)

Worth stating explicitly because it's the one thing that *looks* dangerous and isn't:
the purge on page load deletes **only the current user's** reservation rows
(`DELETE … WHERE user = ?`). Reproduced: Bob loading Add Animals left Alice's live
reservation intact. **No action needed.**

---

## Triage summary

| # | Behavior | Severity | Silent? | MVP disposition |
|---|---|---|---|---|
| 1 | Autonumber TOCTOU → duplicate reservation | Medium | No (1062 error, batch halts) | **Document** — retry works; no data loss |
| 2 | `idno` ear-tag duplicate | **High** | **Yes** | **Document** — verify tags after concurrent adds |
| 3 | Animal-edit lost update | **High** | **Yes** | **Document** — M2-A top priority |
| 4 | Cage move/role last-writer-wins | Low | Yes, but benign | **Document** |
| 5 | Shared global print queue | Medium | No (visible) | **Document** |
| 6 | Reservation purge scope | — | — | **No issue** — correctly per-user |

**Nothing here blocks the MVP.** The one path that could corrupt animal records
(autonumber collision) fails safe. The two genuinely silent risks (§2 `idno`
duplicates, §3 lost updates) are real and are the substance of **M2-A**.

## M2-A implementation checklist (carried forward)

1. **Optimistic locking on animal edits** (§3) — version/`updated_at` column + conflict
   detection. Highest value.
2. **Atomic number claim** (§1, §2, §4) — move the `max()` reads inside the existing
   `LOCK TABLES` window; unique key on `reservations_animals.user`; consider
   `UNIQUE (line, idno)` (dedupe migration first); re-validate the range at confirm.
3. **Per-user print queue** (§5) — add `user` to `CagesForPrinting`.
4. **Human-readable conflict messages** — replace raw 1062 SQL errors with a retry prompt.

---

*Grounded against the tree at `ddaafb8`; all behaviors reproduced on MySQL 8.0.46 with
the app's real schema. Update if the mutation paths change.*
