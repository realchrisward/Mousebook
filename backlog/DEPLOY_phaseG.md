# Phase G (issue #19) — deployment notes

Base: `phase3_revisions` @ `c1475cf`. Two patches, apply vendor first.

## Apply order

```
git apply phaseG_vendor_phpmailer.patch     # vendored PHPMailer 6.9.3 (3 core files + LICENSE)
git apply phaseG_issue19.patch              # app changes
```

## Server deploy sequence (load-bearing)

1. **Migration before PHP.** Run the token-table migration against the auth db:
   ```
   mysql -u <admin> -p userbook < migration_usertoken.sql
   ```
   (Idempotent; safe to re-run. Already folded into
   `mousebook_userbook_install_schema.sql` for fresh installs.)

2. **Register `userbook` as a colony with WRITE credentials.** The admin
   pages and the token pages reach the auth db through the `dbaccess` row
   for `userbook`; its `db_accessun`/`db_accesspw` must have
   INSERT/UPDATE/DELETE on `userbook` (config's `server_user` stays
   read-only and is used only to bootstrap-read that row):
   ```sql
   INSERT INTO dbaccess (db_name, db_accessun, db_accesspw, db_formurl, db_host)
     VALUES ('userbook','<rw_user>','<rw_pass>','userbook','<host>');
   ```

3. **Grant an admin an `admin` tier on `userbook`** so the management
   pages are reachable (they gate on admin-of-userbook):
   ```sql
   INSERT INTO userdbaccess (user_idno, db_name, db_accesstier)
     VALUES (<admin_user_idno>, 'userbook', 'admin');
   ```

4. **Fill in the new `config.php` keys** (copy from `config.example.php`):
   `base_url`, `smtp_host`, `smtp_port`, `smtp_secure`, `smtp_auth`,
   `smtp_user`, `smtp_pass`, `mail_from`, `mail_from_name`.

5. Deploy the PHP files.

## Reaching the pages

- **Manage Users / Manage Databases**: sidebar links appear only when the
  active context is the `userbook` db (log into it via `databases.php`).
- **Forgot password**: linked from the `databases.php` login page →
  `php/forgot_password.php`.
- **Set/reset password**: `php/set_password.php?token=…`, reached from the
  emailed link.
- **Change password (self-service)**: `php/change_password.php`, in the
  global sidebar for any signed-in user. Re-verifies the current password
  before writing the new hash; identity comes from the session, so a user
  can only change their own account.

## Token TTLs
- Invite: admin-set at generation, default 72h (capped 30 days).
- Admin-initiated reset: 72h.
- User-initiated reset (forgot_password): 30 min.

## Out of scope (deferred, as agreed)
- Actual DB spin-up / backups / restores (Manage Databases only *registers*
  a separately-created colony).
- User self-preferences (Phase G sub-feature d).

## Notes
- PHPMailer needs the PHP `mbstring` + `openssl` extensions (openssl for
  TLS/SSL relays). Confirm both are enabled on the RHEL host.

## SMTP relay — config & troubleshooting
`smtp_port` and `smtp_secure` MUST match the relay, or the connection hangs:

| smtp_port | smtp_secure |
|-----------|-------------|
| 587       | `tls`  (STARTTLS)      |
| 465       | `ssl`  (implicit TLS)  |
| 25        | `''`   (none/internal) |

- A white screen on "send" is almost always a port/secure mismatch or an
  unreachable relay. `mail.php` now uses `smtp_timeout` (default 15s) to
  fail fast and log the reason rather than hang to `max_execution_time`.
- To see the exact cause: `tail -f /var/log/httpd/error_log` while
  reproducing. For full SMTP tracing, set `'smtp_debug' => true` in
  config.php (logs the client/server conversation to the PHP error log;
  turn off afterward).
- Internal relay with a self-signed / mismatched cert: set
  `'smtp_allow_selfsigned' => true` (trusted relays only).
- Authenticated relay: set `'smtp_auth' => true` plus `smtp_user`/`smtp_pass`.
  Unauthenticated internal relay: leave `smtp_auth => false`.
- Some relays reject a `mail_from` whose domain they don't own — set it to
  an address the relay is allowed to send as.
- Validation: userbook schema + migration loaded on MySQL 8.0.46; 21/21
  behavioral checks (token generate/peek/consume single-use, expiry,
  malformed-token rejection, password policy, full invite lifecycle,
  graceful mail-disabled degradation); all 4 pages + login page return
  HTTP 200 with no fatals; forgot-password confirmed enumeration-safe.
```
