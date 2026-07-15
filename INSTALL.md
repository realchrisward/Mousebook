# Installing Mousebook

This guide is written for someone who is comfortable at a command line but does
not administer servers for a living. Every command is given in full; you should
not have to improvise anything.

There are two supported reference environments. Pick the one that matches you:

- **[A. Raspberry Pi 5 / Raspberry Pi OS](#a-raspberry-pi-5--raspberry-pi-os)** —
  a machine of your own, on the lab network. The simplest option, and the one
  we recommend for a lab standing up its first Mousebook.
- **[B. RHEL / CloudLinux shared hosting](#b-rhel--cloudlinux-shared-hosting)** —
  a hosting account (GoDaddy and similar), or an institutional RHEL VM.

Both end at the same place: **[First login](#5-first-login)**.

**Time required:** about 45 minutes on a Pi, most of it waiting for packages to
download.

---

## What you are installing

Mousebook is four things on one machine:

| Piece | What it is for |
|---|---|
| **Apache** | The web server. It is what your browser talks to. |
| **PHP** | The language Mousebook is written in. Apache runs it. |
| **MariaDB** (or MySQL) | The database. It is where your colony actually lives. |
| **Mousebook itself** | The PHP files, which you copy into Apache's web folder. |

And **two** databases inside that one database server:

- **the auth database** — accounts, passwords, and who is allowed into which
  colony. It is called `userbook` by default. If your host prefixes database
  names (cPanel does), it will be something like `myaccount_userbook`, and that
  is fine: `setup.sh` asks for the name and records it in `config.php`.
- **your colony** — the animals, cages and litters. This one you name yourself
  (`animalbook` is the default). One Mousebook install can serve several
  colonies; see ADMIN_GUIDE.md.

---

## A. Raspberry Pi 5 / Raspberry Pi OS

Recommended hardware: **Raspberry Pi 5, 8 GB**, booting from an SSD or a good
USB 3 drive rather than an SD card. Colony data is small, but a database on a
cheap SD card will corrupt itself eventually.

### A1. Update the system

```bash
sudo apt update && sudo apt full-upgrade -y
```

### A2. Install Apache, PHP and MariaDB

```bash
sudo apt install -y apache2 mariadb-server \
                    php php-cli libapache2-mod-php php-mysql php-mbstring
```

> **Why MariaDB and not MySQL?** MySQL does not publish a native package for
> the Pi's 64-bit ARM processor, and MySQL 8.0 reached end of life in April
> 2026. MariaDB is the same thing for our purposes: Mousebook runs on either,
> unchanged. See DB_ENGINE_SUPPORT.md.

Confirm all three arrived:

```bash
apache2 -v
php -v
mariadb --version
php -m | grep -E '^(mysqli|mbstring|openssl)$'
```

The last command must print `mbstring`, `mysqli` and `openssl`. If any is
missing, Mousebook will not run — install it before going on.

### A3. Secure the database server

```bash
sudo mariadb-secure-installation
```

Answer:

| Prompt | Answer |
|---|---|
| Enter current password for root | *(press Enter — there isn't one yet)* |
| Switch to unix_socket authentication | **n** |
| Change the root password | **Y**, then set one and **write it down** |
| Remove anonymous users | **Y** |
| Disallow root login remotely | **Y** |
| Remove test database | **Y** |
| Reload privilege tables | **Y** |

You will need that root password once, in step A5.

### A4. Put Mousebook in the web folder

```bash
sudo apt install -y git
cd /tmp
git clone https://github.com/realchrisward/Mousebook.git
sudo mkdir -p /var/www/html/mousebook
sudo cp -rT /tmp/Mousebook /var/www/html/mousebook
sudo rm -rf /var/www/html/mousebook/.git
sudo chown -R "$USER":www-data /var/www/html/mousebook
```

Three things in there are deliberate, and worth understanding before you copy
them:

**The lowercase `mousebook`.** The source folder is `Mousebook` (the repository
name); the destination is `mousebook` (lowercase). That destination name becomes
part of the address people type — `http://your-pi/mousebook` — and on Linux,
URLs are case-sensitive. Lowercase is simply the convention, and it is what
`setup.sh` offers as the default when it asks for the subdirectory. You can
call it anything you like, but if you do, use that same name when `setup.sh`
asks for the **subdirectory** and the **base URL**. The three must agree.

**`cp -rT`, not `cp -r`.** With plain `cp -r`, if the destination directory
already exists — which it will the second time you run this, after a false
start — the source is copied *inside* it, and you end up with
`/var/www/html/mousebook/Mousebook/index.php`. Nothing then works, for a reason
that is very hard to see. `-T` means "treat the destination as the directory to
fill", which does the right thing whether it exists or not.

**Deleting `.git`.** The clone brings a `.git` folder with it, and you have just
put it inside the folder Apache serves to the world. Apache blocks `.htaccess`
by default but **nothing else** — `http://your-pi/mousebook/.git/config` returns
`200`, and so do the packfiles under `.git/objects`. Anyone can reconstruct your
entire repository from them, *including history*: if a `config.php` with a
database password was ever committed and later removed, it is still in there.
Deleting `.git` removes the problem outright, and you do not need it — nothing
in Mousebook uses git at runtime.

> **If you would rather keep `.git` so you can `git pull` updates:** don't
> delete it, and instead make sure the bundled `.htaccess` is being honoured
> (step A6 below) — it contains a `RedirectMatch 404 /\.git` rule for exactly
> this. Verify with
> `curl -s -o /dev/null -w '%{http_code}\n' http://localhost/mousebook/.git/config`,
> which must return `404`. If it returns `200`, delete the folder. The safest
> arrangement of all is to keep the clone *outside* the web root and copy from
> it, which is what the commands above do.

**`"$USER"` is not a placeholder.** Leave it exactly as written — it is a shell
variable that your shell substitutes for your own username before `sudo` ever
runs, so it becomes e.g. `chown -R pi:www-data`. (Check yours with
`echo $USER`.) The point of the command is to leave the files owned by *you*
while giving the group to Apache, so that you can edit them and Apache can read
them. Do not use `root:root`, and do not use `www-data:www-data` — Apache should
not own files it only ever needs to read.

### A5. Run the installer

```bash
cd /var/www/html/mousebook
sudo bash setup.sh
```

Answer the questions. Where you are unsure, the default in `[brackets]` is
right. The ones that matter:

| Question | What to say |
|---|---|
| Database admin username / password | `root`, and the password from step A3. |
| Colony database name | Anything you like — `animalbook`, or your lab's name. Letters, digits and underscores only. |
| What do you call your animals | `mice`/`mouse`, or `rats`/`rat`. This is only cosmetic labelling. |
| Web root / subdirectory | Accept the defaults (`/var/www/html`, `mousebook`). |
| Debug mode | **no**. |
| SMTP relay host | Leave **blank** for now if you don't have one. You can add it later; see [Email](#6-email-optional). |
| Base URL | `http://<your-pi's-address>/mousebook` |
| Admin username / email / password | Your own account. This is the account you will log in with. |
| Account names and passwords | Accept the defaults and let it **generate** the passwords. You never need to see them. |

It ends with a run of checks. **Every line must say `[OK]`.** If any says
`[WARN]`, go to [Troubleshooting](#7-troubleshooting) before continuing.

> **If the installer finds a database that already has tables**, it will not
> touch it — your data is never overwritten without your say-so. But that
> database may predate the current schema, so `setup.sh` will not mark it as
> up to date either. Upgrade it deliberately, after a backup:
>
> ```bash
> export DB_HOST=localhost DB_USER=root DB_PASS='...'
> ./mb_migrate.sh --db <your-colony-db> preflight   # is the conversion safe on this data?
> ./mb_migrate.sh --db <your-colony-db> apply       # BACK UP FIRST — see BACKUP.md
> ```
>
> A **brand-new** database needs none of this: it is created already converted
> (InnoDB, utf8mb4), and the installer records that for you. See
> [docs/MIGRATIONS.md](docs/MIGRATIONS.md).

![Placeholder: the terminal at the end of a successful setup.sh run, showing
the green [OK] verification lines and the "Setup complete" banner](docs/img/02-setup-complete.png)
*A successful `setup.sh`. Every check green.*

### A6. Let Apache honour the `.htaccess` file

Mousebook ships an `.htaccess` file that blocks the web from reading
`config.php`, **any backup of it**, and `.git`. Debian's Apache ignores
`.htaccess` files by default, so you must turn that on:

```bash
sudo sed -i 's|AllowOverride None|AllowOverride All|' /etc/apache2/apache2.conf
sudo systemctl restart apache2
```

> That `sed` edits an Apache config file, which is safe. Do not use `sed -i`
> on Mousebook's own PHP files — it resets their group ownership and Apache
> will stop being able to read them.

Check it worked:

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost/mousebook/config.php
```

`403` is what you want. If you get `200`, the override is not in effect — see
[Troubleshooting](#7-troubleshooting).

> **Why this matters more than it looks.** `config.php` itself is *fairly* safe
> even unprotected, because Apache hands `.php` files to PHP, which executes
> them and prints nothing. But a file named `config.php.bak.20260712120000`
> does **not** end in `.php`. Apache will not run it — it will serve the raw
> text, database password included. `setup.sh` therefore keeps its backups
> outside the web root entirely, and the `.htaccess` rule blocks the whole
> `config.php*` family as a second line of defence. Both, because either one
> alone can be defeated by a misconfiguration.

### A7. Open the firewall (only if you have one)

Raspberry Pi OS ships with no firewall enabled. If you have added `ufw`:

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

Now go to **[First login](#5-first-login)**.

---

## B. RHEL / CloudLinux shared hosting

Shared hosting differs from a machine of your own in three ways, and all three
change the instructions:

1. **You are not root.** You cannot install packages. Whatever PHP and MySQL/
   MariaDB the host provides is what you get.
2. **The database already exists, or you create it from a control panel** (
   cPanel, Plesk), not from a shell.
3. **The database admin account is not `root`.** It is whatever the panel gave
   you, and it may not be allowed to `CREATE USER`.

### B1. Check what the host gives you

Over SSH:

```bash
php -v
php -m | grep -E '^(mysqli|mbstring|openssl)$'
mysql --version     # or: mariadb --version
```

You need **PHP 8.x** with `mysqli` and `mbstring`. If the host offers a PHP
version selector in its control panel, choose 8.1 or newer. If `mbstring` is
absent, most panels have a PHP-extensions page where you can enable it.

### B2. Create the databases and a database user from the control panel

In cPanel this is *MySQL® Databases*. Create:

- an **auth** database — call it `userbook`, and
- a database for your **colony** — e.g. `animalbook`.

**cPanel will almost certainly prefix both names with your account name**, so
what you actually end up with is something like `myaccount_userbook` and
`myaccount_animalbook`. That is fine. Write down the **real** names, prefix and
all — `setup.sh` asks for them, and Mousebook stores whatever you give it.

> Older versions of Mousebook required the auth database to be named exactly
> `userbook` and could not be installed on a prefixing host at all. That
> restriction is gone: the name lives in `config.php` as `userbook_db`.

Then create **one** database user, and grant it **all privileges on both
databases**. `setup.sh` will use it as the admin account, and will try to
create the three application accounts with it.

### B3. Upload Mousebook

Either clone it over SSH:

```bash
cd ~
git clone https://github.com/realchrisward/Mousebook.git
mkdir -p public_html/mousebook
cp -rT Mousebook public_html/mousebook
rm -rf public_html/mousebook/.git
```

(`-T` rather than plain `-r`, so that re-running this does not nest a second
copy at `public_html/mousebook/Mousebook`. And `.git` goes, because it would
otherwise be served — see the note in section A4.)

...or download the ZIP from GitHub and upload it through the panel's File
Manager, into a `mousebook` folder inside your web root (`public_html`).

### B4. Run the installer

```bash
cd ~/public_html/mousebook
bash setup.sh
```

(No `sudo` — you are not root, and you do not need to be.)

Answer as in the Pi instructions, except:

| Question | Shared-hosting answer |
|---|---|
| Database admin username / password | The user you made in B2. |
| Database host | Usually `localhost`. Some hosts use a separate database server — check the panel. |
| **Auth database name** | The **real** name from B2, prefix included — e.g. `myaccount_userbook`. |
| **Colony database name** | Likewise — e.g. `myaccount_animalbook`. |
| Web root | `/home/<you>/public_html` |
| Subdirectory | `mousebook` |
| Base URL | `https://yourdomain.example/mousebook` |

**If account creation fails** with a privileges error, your panel user is not
allowed to `CREATE USER`. That is common. Do this instead:

1. Create three database users from the control panel by hand:
   `mousebook_login`, `mousebook_ub`, `mousebook_app`. Give each a password.
2. Grant them, in the panel:
   - `mousebook_login` → **SELECT** on `userbook`
   - `mousebook_ub` → **ALL PRIVILEGES** on `userbook`
   - `mousebook_app` → **ALL PRIVILEGES** on your colony database
3. Re-run `setup.sh` and, when it offers to generate passwords, answer **no**
   and type in the three passwords you just set.

> Panels rarely offer a `SELECT`-plus-`UPDATE`-on-one-table grant, so
> `mousebook_login` may end up with plain `SELECT`. That works, with one
> cost: Mousebook cannot silently upgrade an old password hash on login. It
> is not a security problem.

### B5. `.htaccess` and HTTPS

Shared hosts almost always have `AllowOverride All` on already, so the bundled
`.htaccess` protects `config.php` with no action from you. Confirm by visiting
`https://yourdomain.example/mousebook/config.php` — you should get
**403 Forbidden**.

Turn on HTTPS from the panel (nearly all of them offer free Let's Encrypt
certificates) and force a redirect from HTTP.

Now go to **First login**.

---

## 5. First login

Open:

```
http://<your-server>/mousebook/pages/databases.php
```

![Placeholder: the Mousebook login page, showing the username and password
fields and the "forgot password" link](docs/img/03-login.png)
*The login page. This is the only page an unauthenticated visitor can reach.*

Log in with the administrator username and password you gave `setup.sh`. You
should see **two buttons**:

- one named after your colony (`animalbook`, or whatever you chose)
- one named **`userbook`**

![Placeholder: the database chooser after logging in, showing the colony button
and the userbook button side by side](docs/img/04-database-chooser.png)
*Two buttons: your colony, and user administration.*

The `userbook` button is the user-administration console. If you see only the
colony button, your admin account did not get its `userbook` grant — see
[Troubleshooting](#7-troubleshooting).

### The five-minute smoke test

Do all of these now, before you enter any real data. Each takes seconds, and
together they prove the install is sound.

| # | Do this | You should see |
|---|---|---|
| 1 | Click your colony's button | The colony home page, with the navigation down the left |
| 2 | Click **Manage Cages** | The cage-management page, no errors |
| 3 | Click **Add animals** | The add form. It offers the location **Limbo** and the cage role **Community** — the two defaults a new colony ships with. The strain/line drop-downs are empty until you create your own (ADMIN_GUIDE.md §4). |
| 4 | Click **Card Printer** → generate | A PDF downloads. It will have no cards on it — you have no animals yet. What matters is that a valid PDF arrives, not a broken file. |
| 5 | Go back and click **`userbook`** | The same home page, but the sidebar now has **Manage Users** and **Manage Databases** |
| 6 | Click **Manage Users** | Your admin account listed, with its access to both databases |

If step 5 shows no Manage Users link, or step 6 says it cannot reach the user
database, stop and read Troubleshooting. Everything else can be fixed later;
that one cannot be worked around.

![Placeholder: the Manage Users page, showing the admin account and its access
tiers on both databases](docs/img/05-manage-users.png)
*Manage Users, reached through the `userbook` button.*

**Next:** [ADMIN_GUIDE.md](ADMIN_GUIDE.md) — set up your rooms, invite your
lab, and start entering animals.

---

## 6. Email (optional)

Mousebook can email new users an invitation link and let people reset their own
passwords. It needs an **SMTP relay** — your institution almost certainly has
one; ask IT for the hostname.

Without email, Mousebook still works: when you create a user, Manage Users shows
you their set-password link on screen, and you send it to them yourself.

To turn email on, edit `config.php` and fill in:

```php
'smtp_host'      => 'smtp.example.edu',   // ask IT
'smtp_port'      => '587',                // 587 with tls, 465 with ssl, 25 with ''
'smtp_secure'    => 'tls',
'smtp_auth'      => false,                // true if the relay wants a login
'mail_from'      => 'no-reply@example.edu',
'base_url'       => 'https://mousebook.example.edu',  // must be right, or the links are wrong
```

**`smtp_port` and `smtp_secure` must agree.** A mismatched pair does not fail
cleanly — it hangs, and you get a white screen. The valid pairs:

| Port | `smtp_secure` |
|---|---|
| 587 | `tls` |
| 465 | `ssl` |
| 25 | `''` (empty) |

Then create a test user in Manage Users and check the mail arrives. If it does
not, set `'smtp_debug' => true` in `config.php` and watch the PHP error log
(`/var/log/apache2/error.log` on Debian, `/var/log/httpd/error_log` on RHEL)
while you retry. Turn it back off afterwards.

---

## 7. Troubleshooting

### `setup.sh` says a PHP extension is missing

Install it, then re-run `setup.sh`. It is safe to re-run: it will not overwrite
a database that already has tables without asking you first.

```bash
sudo apt install php-mysql php-mbstring && sudo systemctl restart apache2  # Debian/Pi
sudo dnf install php-mysqlnd php-mbstring && sudo systemctl restart httpd   # RHEL
```

### `setup.sh` cannot connect to the database

- On the Pi: is MariaDB running? `sudo systemctl status mariadb`
- Is the password right? Test it directly: `mariadb -u root -p -e 'SELECT 1;'`
- On shared hosting: the database host may not be `localhost`. Check the panel.

### "The database rejected a password as too weak"

MySQL's `validate_password` policy. Re-run `setup.sh` and let it **generate**
the account passwords — the generated ones satisfy the default policy.

### Account creation failed — "command denied"

Your database admin account cannot `CREATE USER`. Normal on shared hosting;
follow the fallback in [step B4](#b4-run-the-installer).

### A blank white page

PHP hit a fatal error and is configured not to show it. Look in the error log:

```bash
sudo tail -50 /var/log/apache2/error.log     # Debian / Pi
sudo tail -50 /var/log/httpd/error_log       # RHEL
```

The two usual causes:

- **Apache cannot read `config.php`.** It is mode `640` and group-owned by the
  web server user. Check with `ls -l config.php` — the group must be
  `www-data` (Debian) or `apache` (RHEL). Fix:
  `sudo chgrp www-data config.php && sudo chmod 640 config.php`
- **A missing PHP extension**, usually `mysqli`.

Do **not** fix files with `sed -i`. It rewrites the file and resets its group,
which causes exactly this white screen.

### `http://…/config.php` returns 200 instead of 403

Apache is ignoring `.htaccess`. On Debian/Pi, `AllowOverride` is `None` by
default — do [step A6](#a6-let-apache-honour-the-htaccess-file). Your
credentials are not actually exposed while PHP is working (PHP executes the
file rather than serving its text), but if PHP is ever misconfigured, the file
would be served as plain text with the password in it. Fix it.

### I logged in but there is no `userbook` button

Your admin account has no access grant on `userbook`. Add one by hand — replace
`admin` with your username:

```sql
INSERT INTO userbook.userdbaccess (user_idno, db_name, db_accesstier)
SELECT user_idno, 'userbook', 'admin' FROM userbook.userpass
 WHERE user_name = 'admin';
```

### "Manage Users" says it cannot reach the user database

`userbook` is not registered as a database in its own right. Mousebook reaches
the auth database through its own `dbaccess` row, using write-capable
credentials. Check:

```sql
SELECT db_name, db_accessun FROM userbook.dbaccess;
```

There must be a row where `db_name` is `userbook`. If it is missing, re-running
`setup.sh` will add it (it will offer to keep your existing databases — say
yes).

### I can log in but everything is read-only

Your access tier is not `admin` or `editor`. Anything Mousebook does not
recognise — `1`, `administrator `, an empty string — is treated as
**read-only**, deliberately, so a mis-seeded row can never accidentally grant
write access. The three valid values are exactly `read-only`, `editor`,
`admin`.

```sql
UPDATE userbook.userdbaccess SET db_accesstier = 'admin' WHERE ...;
```

### Cage-card PDFs are blank or corrupt

Almost always a PHP warning being printed into the PDF stream. Set
`'debug_mode' => 'False'` in `config.php` — with debug on, any notice ends up
inside the file.

---

## 8. After the install

- **Set up backups.** [BACKUP.md](BACKUP.md). Do this before you enter real
  data, not after you lose it.
- **Turn on HTTPS.** Passwords cross the network in the clear otherwise.
- **Configure your rooms and cage roles** — [ADMIN_GUIDE.md](ADMIN_GUIDE.md).
- **If you are migrating an existing MySQL colony** and want to be able to
  restore your backups onto MariaDB later, see
  [DB_ENGINE_SUPPORT.md](DB_ENGINE_SUPPORT.md) for the collation migration.

### Upgrading an install that predates this version

Fresh installs need none of this — `setup.sh` and the schemas already have it.
On an **existing** install, widen the auth database's columns:

```sql
ALTER TABLE `dbaccess`
    MODIFY COLUMN `db_name`     varchar(64)  NOT NULL,
    MODIFY COLUMN `db_accesspw` varchar(255) NOT NULL,
    MODIFY COLUMN `db_formurl`  varchar(255) NOT NULL;

ALTER TABLE `userdbaccess`
    MODIFY COLUMN `db_name` varchar(64) NOT NULL;
```

Every change is a widening, so no existing value can be truncated; it is safe to
re-run, and it rewrites no data.

If your auth database is **not** named `userbook`, also add its real name to
`config.php`:

```php
'userbook_db' => 'myaccount_userbook',
```

Existing colonies keep whatever cage locations and roles they already have —
the `Limbo` / `Community` seed rows only apply to newly created colonies.
