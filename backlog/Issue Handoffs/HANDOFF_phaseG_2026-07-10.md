# Mousebook — Session Handoff: Phase G / issue #19 (userbook management)

**Date:** 2026-07-10
**Branch:** `phase3_revisions`
**Base HEAD at session start:** `c1475cf` ("phasep2 whitescreen bug patch")
**Merge path (unchanged):** `phase3_revisions` → `dev` → `master`, deferred until Phase 3 complete
**Runtime validated against:** PHP 8.3.6, MySQL 8.0.46 (prod-matched)

---

## 1. Objective

Build the initial slice of Phase G (issue #19): admin management of the
userbook auth database — user access administration, colony-database
registration, new-user onboarding, and password reset — plus (added
mid-session) self-service password change. Scope maps to the
`backlog/SCOPE_2026-07-08.md` decomposition of #19: sub-features (a)
provision/revoke, (b) invite/registration, (c) reset, (e) register colony.
Sub-feature (d) user preferences was left out of this slice.

Note: this pulled **P5 ahead of P2–P4** in the scope queue at Chris's
direction. P2 SQLi hardening had advanced two commits (`65c7f43`,
`c1475cf`) since the prior session — re-baseline confirmed before scoping.

---

## 2. Delivered artifacts

Two `git apply`-ready patches + a deploy note (all verified on a fresh
`c1475cf` clone; lint-clean after apply):

- **`phaseG_issue19.patch`** — all app-authored changes.
- **`phaseG_vendor_phpmailer.patch`** — vendored PHPMailer 6.9.3 (3 core
  files + LICENSE, no Composer). Apply this one **first**.
- **`DEPLOY_phaseG.md`** — deployment ordering + prerequisites.

### Files in the app patch

New:
- `includes/usertoken.php` — userbook write-connection bootstrap + token
  lifecycle + password policy (details in §4).
- `includes/mail.php` — `mb_send_mail()` over an SMTP relay via PHPMailer.
- `php/manage_users.php` — ADMIN: provision user (+invite), grant/change/
  revoke per-colony tier, admin-initiated reset (72h), edit email.
- `php/manage_databases.php` — ADMIN: register/edit/unregister colony rows
  in `dbaccess` (register-only; no spin-up).
- `php/set_password.php` — PUBLIC, token-gated: invite + reset landing.
- `php/forgot_password.php` — PUBLIC: user-initiated reset (30-min token),
  enumeration-safe.
- `php/change_password.php` — self-service change for any signed-in user.
- `migration_usertoken.sql` — standalone, idempotent.

Modified:
- `mousebook_userbook_install_schema.sql` — adds `usertoken` for fresh installs.
- `config.example.php` — new keys: `base_url`, `smtp_host`, `smtp_port`,
  `smtp_secure`, `smtp_auth`, `smtp_user`, `smtp_pass`, `mail_from`,
  `mail_from_name`.
- `includes/nav.php` — global "Change Password" entry; userbook-only block
  surfacing "Manage Users" / "Manage Databases" when the active db is userbook.
- `pages/databases.php` — "Forgot password?" link on the login page.

---

## 3. Design decisions (this session)

1. **Per-colony write credentials.** userbook is registered as its own row
   in `dbaccess` with **write-capable** `db_accessun`/`db_accesspw`. All
   userbook writes go through those creds. config's `server_user` stays
   read-only and is used only to *bootstrap-read* that row. (No new config
   credential introduced.)
2. **Token model.** New `usertoken` table; single-use (`used_at`),
   time-limited (`expires_at`), **sha256 hash stored, raw token only in the
   emailed link**. TTLs: invite = admin-set at generation, default **72h**
   (capped 30 days); admin-initiated reset = **72h**; user-initiated reset
   = **30 min**.
3. **Email = SMTP relay** (not PHP `mail()`), via vendored PHPMailer.
   Supports authenticated and unauthenticated relays (`smtp_auth` toggle),
   tls/ssl/none. Email preferred so users can self-serve resets; admin
   pages also display the generated link as a fallback if mail is down.
4. **Colony "creation" = registration only.** Manage Databases registers a
   colony whose schema + MySQL user/grants were created out of band. True
   spin-up / backups / restores explicitly **out of scope**.
5. **Self-service change (added mid-session).** Any signed-in user; identity
   from the session (`mb_user`), never a form field; re-verifies the current
   password before writing; new must differ from current. In the global nav.

---

## 4. Architecture notes (for the next session)

- **Admin pages** (`manage_users`, `manage_databases`) run in the *userbook
  context*: reach them with `dbname=userbook` so the Phase F session tier
  gate checks **admin-on-userbook**. They connect with the session's
  userbook credentials (= the write-capable `dbaccess` creds). A userbook
  admin is simply a user with an `admin` tier row in `userdbaccess` for
  `db_name='userbook'`. Fails closed if the user lacks that.
- **Public token pages** (`set_password`, `forgot_password`) and
  **`change_password`** have no colony session for userbook, so they obtain
  a write connection via `mb_userbook_conn($config)` in
  `includes/usertoken.php`: connect with config read creds → read the
  userbook `dbaccess` row → reconnect with its write creds. Single source
  of truth for "how to write to userbook without a session."
- **Token helpers** (all parameterised): `mb_token_generate()`,
  `mb_token_peek()` (validate w/o consuming — used to render the form),
  `mb_token_consume()` (atomic single-use via conditional UPDATE +
  `affected_rows`), `mb_password_policy_error()`.
- **Pending-user sentinel:** new users get `user_pass='INVITED-PENDING'`,
  which `password_verify()` can never match, so the account is unusable
  until the invite token is consumed.
- **`MB_USERBOOK_DB`** constant (default `'userbook'`) defined in
  `includes/usertoken.php`; nav reads it for the userbook-only block. If an
  install renames the auth db, change it there + config + schema `USE`.

---

## 5. Deployment sequence (load-bearing — see DEPLOY_phaseG.md)

1. `git apply phaseG_vendor_phpmailer.patch` then `git apply phaseG_issue19.patch`
2. `mysql -u <admin> -p userbook < migration_usertoken.sql` (before PHP)
3. Register `userbook` in `dbaccess` with write-capable creds
4. Grant an admin an `admin` tier on `userbook` in `userdbaccess`
5. Fill new `config.php` keys (base_url + smtp_* + mail_from*)
6. Deploy PHP files

**Host prereq:** PHPMailer needs `mbstring` + `openssl` (openssl for
TLS/SSL relays). Confirm both enabled on the RHEL box before cutover.

---

## 6. Validation performed

- userbook install schema + `migration_usertoken.sql` load on MySQL 8.0.46;
  migration idempotent (ran twice, rc 0).
- **21/21** behavioral checks: write-cred bootstrap; token generate/peek/
  consume single-use; expiry rejection; malformed/injection-shaped token
  rejection; password policy; full invite lifecycle (pending → set → verify);
  graceful mail-disabled degradation.
- HTTP smoke (built-in server + curl): all pages return 200, no fatals;
  `forgot_password` confirmed **enumeration-safe** (identical neutral copy
  for known vs unknown identifiers while a token is minted for the real user).
- HTTP integration (cookie-jar login) for `change_password`: correct-current
  → new hash verifies & old rejected; wrong-current rejected; no-session →
  sign-in prompt; nav entry present.
- Both patches `git apply --check` clean on a fresh `c1475cf` clone; all
  changed PHP `php -l` clean after apply.

---

## 7. Open items / next steps

- **Phase G remaining:** sub-feature (d) user self-preferences — deferred,
  not started.
- **P6 carry-forwards (untouched):** pre-existing parse error in
  `php/autoclipsheet.php` at L11; any residual scope-doc smalls.
- **Deferred DB tooling:** colony spin-up / backups / restores (desired
  future function, explicitly out of this phase).
- **Standalone migration hygiene (pre-existing, from earlier commits):** the
  two root `.patch` files committed to the repo had code applied but their
  `migration_*.sql` artifacts (`migration_reservations_cages.sql`) were never
  committed — existing deployments upgrading across those commits still need
  that extracted and run manually.

---

## 8. Re-baseline reminders for next session

- Fresh-clone and check `git log` HEAD first — the branch advances between
  sessions as Chris commits. This session's base was `c1475cf`; do not
  assume it's still HEAD.
- Never `sed -i` server-side (inode group-ownership reset → silent
  whitescreens). Use `cat tmpfile > file` or `git apply` on a clone.
- MySQL 8 harness must be started **and** queried within a single tool
  invocation (background mysqld doesn't survive across calls; datadir does).
- Chris manages all commits and server deployments; deliver patches only.
