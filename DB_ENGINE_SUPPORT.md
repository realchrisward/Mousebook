# Database Engine Support (MySQL / MariaDB)

**Issue:** #37 (M1-G) — DB-engine portability
**Status:** Mousebook runs on **both MySQL 8.x and MariaDB 10.11+**.
**Recommended default for fresh installs:** **MariaDB 10.11 LTS.**
**Existing MySQL deployments:** keep MySQL. Nothing forces you to switch.

---

## 0. Summary for administrators

| Question | Answer |
|---|---|
| Which engine should I install? | **MariaDB 10.11+** on a new server (incl. Raspberry Pi). |
| I already run MySQL 8. Must I migrate? | **No.** Mousebook fully supports MySQL 8.x. |
| Can I install both on one host? | **No** (see §4). The OS packages conflict. |
| Can I move a MySQL colony to MariaDB later? | **Yes — but only after applying the migration in §3.** |
| Does MySQL 8.0 going EOL break Mousebook? | No. It is a *support* concern, not a compatibility one. |

Why MariaDB is the default for new installs: MySQL 8.0 reached end of life in
April 2026 (8.0.46 was the final release), and there is **no native MySQL 8
package for arm64 Raspberry Pi OS** — which the Raspberry Pi 5 reference
environment requires. MariaDB is packaged natively everywhere Mousebook targets.

---

## 1. Verified support matrix

Both engines were tested by loading the shipped schemas and exercising the app's
real queries (views, cage-card genotype joins, reservation locking, procedures).

| Engine | Version tested | Install schema | 13 views | 10 procedures | Result |
|---|---|---|---|---|---|
| MySQL | 8.0.46 | loads | queryable | callable | **Supported** |
| MariaDB | 10.11.14 LTS | loads | queryable | callable | **Supported** |

Both engines produce **identical results** on the same seed data (view row
counts, genotype-conversion joins, reservation joins).

### What was audited and found portable

- **Version-gated dump syntax** (`/*!50001`, `/*!50003`, `/*!50503`) — executes correctly on MariaDB.
- **No MySQL-8-only SQL** anywhere in the app: no window functions, CTEs, `JSON_TABLE`, `ANY_VALUE`, `REGEXP_LIKE`.
- **Storage engines** — 37 MyISAM + 2 InnoDB tables: supported on both.
- **`LOCK TABLES … WRITE`** (the reservation system in `add_animals.php` / `manage_cages.php`): identical semantics on both.
- **Mixed charsets** (`latin1` / `utf8mb3` / `utf8mb4`) and `CONVERT(… USING utf8mb3)` in the cage-card join: behave identically on both.
- **No `DEFINER=` clauses** in the shipped dumps, so imports do not depend on a specific DB user existing.
- **Auth plugins** — MySQL 8 defaults to `caching_sha2_password`, MariaDB to `mysql_native_password`. PHP 8.3's `mysqli` supports **both**; no config change needed.

---

## 2. The one incompatibility (fixed)

MySQL 8 introduced the collation **`utf8mb4_0900_ai_ci`**, which **does not exist
in MariaDB**. It appeared in 13 places in `mousebook_install_schema.sql`: the two
InnoDB reservation tables and 11 view definitions.

Loading the old schema on MariaDB failed hard:

```
ERROR 1273 (HY000): Unknown collation: 'utf8mb4_0900_ai_ci'
```

This failure is **dangerous, not merely noisy**: the import aborts partway, leaving
a database with tables but **no views and no stored procedures** — a colony DB that
looks present but is silently broken.

**Fix:** the schema now uses **`utf8mb4_unicode_ci`**, which exists on both engines
and sorts equivalently for Mousebook's data. No PHP code changes were required.

---

## 3. Migrating an existing MySQL colony database

**You only need this if your database was created before this change** — i.e. if it
was built from a schema containing `utf8mb4_0900_ai_ci`.

Your database keeps working on MySQL either way. But the collation is baked into
the stored metadata of your tables *and* views, so a `mysqldump` of an un-migrated
database re-emits `utf8mb4_0900_ai_ci` — **and that backup cannot be restored onto
MariaDB.** Applying the migration makes your existing backups portable.

Check whether you're affected (run against each colony database):

```sql
SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_COLLATION LIKE '%0900%';
SELECT TABLE_NAME FROM information_schema.VIEWS
  WHERE TABLE_SCHEMA = DATABASE() AND COLLATION_CONNECTION LIKE '%0900%';
```

Any rows returned → apply the migration:

```bash
mysql -u <admin> -p <colony_db> < migration_m1g_collation_portability.sql
```

The migration is **idempotent** (safe to re-run), touches **no data**, and applies
to **colony (animalbook) databases only** — the userbook schema was never affected.

### Verified migration path (MySQL → MariaDB)

| Scenario | Result on MariaDB |
|---|---|
| Backup of an **un-migrated** MySQL colony | **Fails** — error 1273; only 25 of 37 tables, 0 views, 0 procedures |
| Backup of a **migrated** MySQL colony | **Succeeds** — 37 tables, 13 views, 10 procedures, all working |

Use the documented backup command from `BACKUP.md` (`--routines --triggers
--events`); a plain `mysqldump` omits the 10 stored procedures.

---

## 4. MySQL and MariaDB cannot coexist on one host

The OS packages conflict — installing one **removes** the other:

```
# apt-get install mysql-server   (with MariaDB present)
The following packages will be REMOVED:
  mariadb-server mariadb-server-core ...
```

This is why Mousebook is portable rather than tied to one engine: on a shared or
institutional host that already runs MySQL for other applications, Mousebook must
use the engine that is already there. **Do not uninstall a working MySQL to install
MariaDB.** If you truly need both engines on one machine, run one of them in a
container or as a second instance on a different port — do not attempt a second
native install.

Note that MariaDB provides `mysql`, `mysqld`, and `mysqldump` as compatibility
command names (`/usr/sbin/mysqld` is a symlink to `mariadbd`), so existing scripts
and habits continue to work.

---

## 5. Known limitation (not an engine issue)

`mousebook_install_schema.sql` hardcodes its target database:

```sql
CREATE DATABASE IF NOT EXISTS `animalbook`;
USE `animalbook`;
```

Piping it into a differently-named database (`mysql -u root -p my_colony <
mousebook_install_schema.sql`) **silently writes to `animalbook` instead**. To
create a colony database under another name, edit those two lines first. Tracked
for the installation work (#36).
