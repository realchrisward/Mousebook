<img src="assets/mousebook_logo.png" width="50%" height="50%">

# Mousebook

A laboratory animal colony management system: a web application for tracking
animals, cages, litters, genotypes and cage cards in a research vivarium.
PHP and MySQL/MariaDB, self-hosted, no cloud service and no internet access
required once installed.

Mousebook is designed to be run **by a lab, on a lab's own hardware** — a
spare Raspberry Pi under a desk is enough for a colony of a few thousand
animals, and it runs equally well on a shared university web host.

![Placeholder: the Mousebook home page for a colony, showing the left-hand
navigation and the animal summary](docs/img/01-home.png)
*The colony home page. Replace this placeholder with a screenshot of your own
install — see `docs/img/README.md`.*

---

## What it does

- **Animals** — add, edit, search and retire animals; DOB, sex, line, strain,
  genotype, ear tag, cage.
- **Cages** — cage inventory, locations by room.
- **Litters** — litter log, wean tracking, dead-pup records.
- **Genotypes** — per-line alleles, a genotyping to-do list.
- **Cage cards & clip sheets** — printable PDFs sized for cage-card stock,
  including a blind-genotype variant for masked experiments.
- **Users** — accounts, per-colony access tiers (read-only / editor / admin),
  email invitations and self-service password resets.
- **Multiple colonies** — one Mousebook install can serve several colony
  databases on the same host, each with its own user access list.

---

## Requirements

| | |
|---|---|
| **Operating system** | Any Linux with Apache and PHP. Tested on Raspberry Pi OS (Debian 12) and RHEL/CloudLinux. |
| **Web server** | Apache 2.4 |
| **PHP** | 8.x, with the `mysqli` and `mbstring` extensions (`openssl` too, if you want email) |
| **Database** | MariaDB 10.11+ **or** MySQL 8.0/8.4 — both are supported |
| **Hardware** | A Raspberry Pi 5 (8 GB) is sufficient. So is any small VM or shared host. |

MariaDB is the recommended engine for a fresh install; if your host already
runs MySQL, keep MySQL. See [DB_ENGINE_SUPPORT.md](DB_ENGINE_SUPPORT.md) —
the two cannot be installed side by side, and Mousebook does not ask you to.

---

## Install

```bash
git clone https://github.com/realchrisward/Mousebook.git
sudo cp -r Mousebook /var/www/html/mousebook
cd /var/www/html/mousebook
sudo bash setup.sh
```

`setup.sh` asks you a series of questions and then does everything else:
creates the databases, loads the schemas, creates three least-privilege
database accounts, writes `config.php`, creates your first administrator,
and verifies that all of it actually works before it exits.

**Do not skip [INSTALL.md](INSTALL.md).** It is the step-by-step version of
the four commands above, written for someone who does not administer servers
for a living, and it covers the two things `setup.sh` cannot do for you:
configuring Apache, and turning on HTTPS.

Once you are logged in, [ADMIN_GUIDE.md](ADMIN_GUIDE.md) covers day-to-day
administration — inviting users, granting access, adding a second colony.

---

## Documentation

| Document | What it covers |
|---|---|
| [INSTALL.md](INSTALL.md) | Step-by-step installation on Raspberry Pi OS and RHEL/CloudLinux, plus troubleshooting. |
| [ADMIN_GUIDE.md](ADMIN_GUIDE.md) | First login, user management, access tiers, registering another colony, setting up your rooms. |
| [BACKUP.md](BACKUP.md) | Backups, restores, and regenerating the install schema. |
| [DB_ENGINE_SUPPORT.md](DB_ENGINE_SUPPORT.md) | MySQL vs MariaDB: what is supported, and how to migrate an existing MySQL colony. |
| [DEPENDENCIES.md](DEPENDENCIES.md) | Every external component, its licence, and whether it needs internet access. |
| [CONCURRENCY.md](CONCURRENCY.md) | What happens when two people edit the same record at once. Read this before running a busy shared colony. |

---

## Security notes

Mousebook holds no personal data beyond usernames and email addresses, but it
does hold your colony, and it is a web application:

- **Serve it over HTTPS.** Passwords are posted to the login page. Over plain
  HTTP they cross the network in the clear. INSTALL.md shows how.
- **Keep `config.php` off the web.** It contains a database password. The
  bundled `.htaccess` blocks it, but only if Apache is configured with
  `AllowOverride All` for the directory — INSTALL.md covers this too.
- **Do not expose it to the public internet** unless you have a reason to.
  A lab network or a VPN is the right place for it.

---

## Licence

MIT — see [LICENSE](LICENSE). Bundled third-party components (FPDF, PHPMailer,
Tabler Icons) carry their own permissive licences; all are catalogued in
[DEPENDENCIES.md](DEPENDENCIES.md).
