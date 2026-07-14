# Mousebook — Backup, Restore & Schema Regeneration

Operational guide for backing up the Mousebook databases, restoring them, and
regenerating the authoritative install schema. Written for the live stack
(RHEL/Apache, MySQL 8.0.45).

## What has to be backed up

Mousebook uses **two** databases and a backup is incomplete without both:

- **`animalbook`** — all colony data (animals, cages, genotypes, litters, lookups, views, stored procedures).
- **`userbook`** — authentication (accounts, bcrypt password hashes, per-user db access rows).

Losing `userbook` locks everyone out even if `animalbook` is intact, so always
dump the pair together.

## Engine note (`--lock-tables` today, `--single-transaction` once you migrate)

**Which flag is correct depends on which engine your database is on**, so check
before you trust either:

```bash
./mb_migrate.sh --db animalbook status     # applied / pending
```

**Every table InnoDB** (a fresh install, or an install that has applied
migration `001_innodb_utf8mb4`):

> Use **`--single-transaction`**. It gives a consistent snapshot from a single
> read view **without locking anything**, so backups no longer block the app.
> This is the reason the flag flipped from wrong to right — see
> [docs/MIGRATIONS.md](docs/MIGRATIONS.md).

**Any table still MyISAM** (an install that has not migrated yet):

> Use **`--lock-tables`** (mysqldump's default), as the script below does.
> MyISAM is non-transactional, so `--single-transaction` does **not** produce a
> consistent snapshot of it — that flag only helps InnoDB, and on a mixed schema
> it will hand you a dump that *looks* fine and is silently torn. Writes are
> brief, so the lock window is short; run backups off-peak regardless.

A **partially** converted database is the worst of both: it gets no consistency
benefit and keeps the locking cost. That is why migration 001 converts every
table in one pass rather than a table at a time.

## A dedicated, least-privilege backup account

Don't back up as `root`. Create a read-only account and store its credentials in
a protected defaults file rather than on the command line (command lines are
visible in `ps`).

```sql
-- run once as an admin
CREATE USER 'mb_backup'@'localhost' IDENTIFIED BY '<strong-random-password>';
GRANT SELECT, PROCESS ON *.* TO `mb_backup`@`localhost`;
GRANT SHOW_ROUTINE ON *.* TO `mb_backup`@`localhost`;
GRANT SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER ON `animalbook`.* TO `mb_backup`@`localhost`;
GRANT SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER ON `userbook`.* TO `mb_backup`@`localhost`;
FLUSH PRIVILEGES;
```




Credentials file `/etc/mousebook/backup.cnf` (owned by the backup user, `chmod 600`):
# you may need to make the directory to place the cnf first

```ini
[client]
user = mb_backup
password = <strong-random-password>
host = localhost
```

```bash
install -o root -g root -m 600 /dev/null /etc/mousebook/backup.cnf
# then edit in the contents above
```

## Backup script

`/usr/local/bin/mousebook_backup.sh` — dumps both databases **with data**,
compresses, and rotates. This is a *data* backup and is distinct from the
structure-only install schema (see the last section).

```bash
#!/usr/bin/env bash
set -euo pipefail

DEFAULTS=/etc/mousebook/backup.cnf
DEST=/var/backups/mousebook
KEEP_DAYS=14
STAMP=$(date +%F_%H%M%S)

mkdir -p "$DEST"

for DB in animalbook userbook; do
  OUT="$DEST/${DB}_${STAMP}.sql.gz"
  mysqldump --defaults-extra-file="$DEFAULTS" \
    --lock-tables \
    --routines --triggers --events \
    --add-drop-table \
    "$DB" | gzip -c > "$OUT"
  # fail loudly if the dump is suspiciously small (e.g. auth/lock error)
  if [ "$(stat -c%s "$OUT")" -lt 1024 ]; then
    echo "ERROR: $OUT is too small — dump likely failed" >&2
    exit 1
  fi
done

# rotation: delete dumps older than KEEP_DAYS
find "$DEST" -name '*.sql.gz' -type f -mtime +"$KEEP_DAYS" -delete

echo "backup complete: $STAMP"
```

```bash
chmod 750 /usr/local/bin/mousebook_backup.sh
```

## Scheduling

Nightly at 02:15, as the backup user (or root):

```cron
15 2 * * * /usr/local/bin/mousebook_backup.sh >> /var/log/mousebook_backup.log 2>&1
```

Offsite copy: the dumps in `/var/backups/mousebook` should be pushed somewhere
off the host (rsync to another server, an object-storage bucket, etc.).
A backup that only lives on the database server does not survive that server
dying. Rotate the offsite copies too.

## Restore

Restoring overwrites the target database, so confirm the target first.

```bash
# inspect available dumps
ls -lh /var/backups/mousebook

# restore one database (example: animalbook)
gunzip -c /var/backups/mousebook/animalbook_2026-07-05_021500.sql.gz \
  | mysql --defaults-extra-file=/etc/mousebook/restore.cnf animalbook
```

`restore.cnf` is like `backup.cnf` but points at an account with write
privileges (the dumps contain `DROP TABLE` / `CREATE` / `INSERT`). If the target
database doesn't exist yet, create it first: `CREATE DATABASE animalbook;`.

**Test your restores.** A backup you have never restored is a hope, not a
backup. Periodically restore the latest dump into a scratch database
(`animalbook_restore_test`) and spot-check row counts and that the views/procs
recreate without error.

## Regenerating the install schema (`mousebook_install_schema.sql`)

The repo's authoritative **structure-only** install file is produced from live
and then made portable. To regenerate it after schema changes:

```bash
# 1. structure + routines, no data
mysqldump --defaults-extra-file=/etc/mousebook/backup.cnf \
  --no-data --routines --triggers --events \
  animalbook > animalbook_structure.sql

# 2. make it install-portable: strip account-bound DEFINER clauses and
#    switch views to INVOKER security (so it loads on any host)
perl -0777 -pe "s/DEFINER=\`[^\`]+\`\@\`[^\`]+\` ?//g; s/SQL SECURITY DEFINER/SQL SECURITY INVOKER/g" \
  animalbook_structure.sql > mousebook_install_schema.sql
```

Then re-add the seed block (currently just the `Limbo` location) and the
`CREATE DATABASE IF NOT EXISTS` / `USE` header. The committed
`mousebook_install_schema.sql` already contains these; diff against it after
regenerating so the seed and header aren't lost.

Do the same for `userbook` when a fresh dump is available, to replace the stale
`default_userbook.sql`.
