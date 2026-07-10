# Mousebook — Session Handoff: Phase G email white-screen (mb_send_mail collision)

**Date:** 2026-07-10 (follow-up to `HANDOFF_phaseG_2026-07-10.md`)
**Scope of this segment:** diagnose and fix the white-screen error when
sending invite / password-reset email, first observed on the *deployed*
branch.

---

## 1. Branch reality (re-baseline — read first)

Phase G is now being tested/deployed on **`usebook_interface_2`** (note the
missing "r" — that is the actual branch name), **not** `phase3_revisions`.

Commit lineage on `usebook_interface_2`:
```
878d113  troubleshoot smtp            <- commits smtp_diagnose.php to the repo
b103fba  phaseg issue 19 related patches  <- applied this session's Phase G work
c1475cf  phasep2 whitescreen bug patch    <- shared base with phase3_revisions
```
`phase3_revisions` HEAD is still `c1475cf`. **The two lines have diverged**:
Phase G lives on `usebook_interface_2`; `phase3_revisions` does not have it
committed. Do not assume they match — check `git log` on the specific branch
before cutting anything.

Deploy path on the server: RHEL/Apache, docroot `/var/www/html/Mousebook`,
PHP via php-fpm (error log: `/var/log/php-fpm/www-error.log`).

---

## 2. Symptom → root cause

**Symptom:** clicking "send" on invite (manage_users) or forgot-password
produced a white screen and no email. CLI worked; the browser did not.

**Root cause (a bug in this session's Phase G code):**
`mb_send_mail` is a **built-in function of the PHP `mbstring` extension**.
`includes/mail.php` defined its wrapper inside
`if (!function_exists('mb_send_mail'))`. On any host where `mbstring` is
loaded — which the production web SAPI is — that guard sees the built-in
already exists and **skips defining the wrapper**. The calling code then
invoked the *built-in* `mb_send_mail(string $to, …)` with the `$config`
array in the `$to` slot:

```
PHP Fatal error: Uncaught TypeError: mb_send_mail(): Argument #1 ($to)
must be of type string, array given in .../php/manage_users.php:72
```

The `TypeError` is thrown at the call site (outside the wrapper's own
try/catch, which never runs), so it is uncaught → white screen.

**Why it hid so long:** the sandbox used for the original validation had
`mbstring` NOT loaded, so `function_exists('mb_send_mail')` was false there
and the wrapper *was* defined — tests passed. `smtp_diagnose.php` uses
PHPMailer directly and never calls `mb_send_mail`, so it also worked. The
relay, STARTTLS-on-587, unauthenticated send, config, and network were all
fine the whole time (the diagnostic delivered a real message).

Earlier misdirections in this segment (extension prereqs, then SMTP
timeout/hang, then SELinux `httpd_can_network_connect`) were wrong; the
`www-error.log` line is what identified the true cause.

---

## 3. The fix

Rename the colliding function to `mb_send_relay_mail` (mb-prefixed for
consistency, but not a built-in) at the definition, the `function_exists`
guard, and both call sites. No config or schema change.

### Which patch goes where — DO NOT cross-apply

| Target branch | Patch to apply | Notes |
|---|---|---|
| **`usebook_interface_2`** (deployed) | **`fix_mb_send_mail_collision.patch`** | 3-file rename only; verified `git apply` clean on branch HEAD `878d113`. This is the one Chris needs. |
| `phase3_revisions` (future) | `phaseG_issue19.patch` | Full Phase G changeset, cut against `c1475cf`; already includes the rename. Would CONFLICT on `usebook_interface_2` (which already has Phase G). |

Deploy for the deployed branch: `git apply fix_mb_send_mail_collision.patch`,
then push the three changed files (`includes/mail.php`,
`php/manage_users.php`, `php/forgot_password.php`). Nothing else.

---

## 4. Diagnostic tooling produced

`smtp_diagnose.php` — standalone CLI probe (now committed to the branch at
`878d113`). Run from the Mousebook root: `php smtp_diagnose.php you@dom`.
Reads real `config.php`, bypasses web/session/render (cannot white-screen),
prints effective settings, a raw TCP reachability probe, and a full verbose
PHPMailer trace ending in SUCCESS/FAILED. Best first tool for any future
relay issue. Safe to delete; writes nothing to the DB.

---

## 5. Validation performed (this segment)

- Reproduced the exact production condition: installed `mbstring` in the
  sandbox so `function_exists('mb_send_mail')` returns `true`.
- Confirmed the old name resolved to the built-in (the trap); the new name
  `mb_send_relay_mail` defines and a config-array-first call returns cleanly
  (no `TypeError`).
- HTTP smoke of the forgot-password path for a real user with an email (the
  scenario that fataled): **HTTP 200**, neutral message rendered, reset token
  minted, zero fatals in the server log.
- Scanned every `mb_*` helper in `includes/` against PHP's internal
  functions: `mb_send_mail` was the **only** collision — nothing else at risk.
- `php -l` clean on all three changed files; surgical patch verified
  `git apply --check` clean on a pristine `usebook_interface_2` clone.

---

## 6. Key learnings (carry forward)

- **Never name a project function after a PHP built-in**, and be wary of the
  `mb_` prefix specifically — it is `mbstring`'s namespace. `mb_send_mail`
  was the collision; all other `mb_*` Mousebook helpers are clear, but the
  next new one should be checked against `get_defined_functions()['internal']`.
- **A `function_exists()` guard around a builtin-colliding name fails
  silently** — it skips your definition and the builtin wins. Guards are for
  double-include safety, not for names that might already exist as builtins.
- **Validate mail/`mbstring`-adjacent code with `mbstring` LOADED.** The
  sandbox default had it off, which masked this. When in doubt,
  `apt-get install php-mbstring` before testing.
- **CLI vs web SAPI differ** (extensions, ini, security context). A CLI
  `php -m` check does not prove the web SAPI's state.

---

## 7. Open items

- **Unrelated bug spotted in the same log (untouched):**
  `php/manage_cages.php:573` — `mysqli_fetch_array(): Argument #1 must be of
  type mysqli_result, false given`. A failed query result fed to
  `fetch_array` without a guard. Offered to fix; awaiting go-ahead.
- **Branch reconciliation:** Phase G currently only on `usebook_interface_2`.
  Decide how/when it merges back toward `phase3_revisions` → `dev` → `master`.
- **Phase G remaining:** sub-feature (d) user self-preferences — deferred.
- **P6 carry-forwards (untouched):** pre-existing parse error in
  `php/autoclipsheet.php` L11; residual scope-doc smalls.

---

## 8. Deliverables this segment

- `fix_mb_send_mail_collision.patch` — **the fix for `usebook_interface_2`.**
- `smtp_diagnose.php` — CLI relay probe (also now committed at `878d113`).
- `phaseG_issue19.patch` — full Phase G for `phase3_revisions`, updated to
  include the rename (not for the deployed branch).
- Prior context: `HANDOFF_phaseG_2026-07-10.md`, `DEPLOY_phaseG.md`.
