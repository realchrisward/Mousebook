# Mousebook — External Dependencies

**Applies to:** `phase3_revisions` @ `27459ff`
**Issue:** #18 (Milestone 1 / M1-C). Feeds the install & release-readiness work (M1-H / #36).

This document inventories every external resource Mousebook relies on, with its
**purpose**, **license**, and **self-containment status**:

- **Redistributed** — vendored in this repository; no download or package install needed.
- **User-provided** — a prerequisite the host administrator supplies (OS packages / services); not shipped by Mousebook.
- **Requires internet** — reached over the network at runtime.

Mousebook uses **no Composer and no npm** — every third-party PHP library is
committed directly to the repo and included with plain `require_once`. There is
no build step and no package-manager lockfile.

---

## 1. Runtime prerequisites (user-provided)

These are supplied by the host. `setup.sh` checks that `php`, a `mysql` client,
and (optionally) `apache2` are present; it does **not** install them.

| Resource | Version (target) | Purpose | License | Self-containment |
|---|---|---|---|---|
| **Web server — Apache HTTP Server** | 2.4.x | Serves the PHP application; enforces the `.htaccess` rule that blocks web access to `config.php`. | Apache License 2.0 | **User-provided.** Not redistributed. |
| **PHP** | 8.3 (8.x) | Application runtime. | PHP License v3.01 | **User-provided.** |
| **Database engine — MySQL or MariaDB** | MySQL 8.0/8.4 **or** MariaDB 10.11+/11.x | Two-database backend: `userbook` (auth/access) + one or more colony DBs (e.g. `animalbook`). | MySQL: GPLv2 (or commercial). MariaDB: GPLv2. | **User-provided.** Engine choice is per M1-G (#37) portability: MariaDB is the recommended default for fresh installs; existing MySQL hosts are left untouched. |
| **SMTP relay** | any | Outbound invitation / password-reset email (Phase G / #19). | n/a (external service) | **User-provided and optional.** Empty `smtp_host` disables mail entirely. Requires network reachability to the relay — an internal relay is fine; the public internet is not required. |

### Required PHP extensions

| Extension | Needed for | Required? |
|---|---|---|
| `mysqli` | All database access (23 files). | **Required.** |
| `mbstring` | Multibyte-safe string handling in the app and in PHPMailer. | **Required.** |
| `openssl` | STARTTLS / SSL to the SMTP relay (`includes/mail.php`). | **Required only if email is enabled.** |
| `zlib` | FPDF PDF stream compression (`gzcompress`, used opportunistically via `function_exists`). | **Optional** — PDFs generate uncompressed if absent. |

> The stock PHP builds on Debian/Ubuntu (`php-mysql`, `php-mbstring`) and
> RHEL/CloudLinux already provide these; `openssl`/`zlib` are compiled in by default.

---

## 2. Bundled third-party libraries (redistributed — no internet)

All three are committed to the repo and loaded with `require_once`. None needs a
package manager, and none reaches the internet for its own code.

### FPDF 1.9
- **Location:** `php/fpdf.php` (+ core fonts under `php/font/`, docs under `php/doc/`, `php/tutorial/`, `php/makefont/`).
- **Purpose:** server-side PDF generation — cage cards (`cagecard_gen5rs.php`, `cagecard_gen5rs-blindgeno.php`) and the clip sheet (`autoclipsheet.php`).
- **License:** FPDF permissive license (`php/license.txt`) — free of charge to use, copy, modify, distribute, sublicense, and sell; provided "as is." Compatible with the project's MIT license.
- **Self-containment:** **Redistributed.** No internet. The Helvetica/Courier/Times/Symbol/ZapfDingbats core fonts ship with FPDF; no external font download.

### PHPMailer 6.9.3
- **Location:** `includes/vendor/PHPMailer/` (`PHPMailer.php`, `SMTP.php`, `Exception.php`, `LICENSE`). Included manually in `includes/mail.php` (no Composer autoload).
- **Purpose:** send SMTP email (Phase G invitations and password resets).
- **License:** GNU **LGPL-2.1** (`includes/vendor/PHPMailer/LICENSE`).
- **Self-containment:** **Redistributed.** The library code needs no internet; at runtime it connects to the **user-provided SMTP relay** you configure (see §1). LGPL-2.1 note: PHPMailer is used unmodified as a library, which is compatible with MIT redistribution — keep it vendored as-is; if you ever modify PHPMailer's own source, LGPL source-availability obligations attach to those changes.

### Tabler Icons 3.33.0
- **Location:** `assets/tabler/tabler-icons.min.css` + `assets/tabler/fonts/tabler-icons.woff2` and `.woff`.
- **Purpose:** UI icon webfont (navigation, buttons, light/dark theme toggle).
- **License:** **MIT.**
- **Self-containment:** **Redistributed (local copy is primary).** `mousebook.js` loads the vendored copy first; it falls back to the jsDelivr CDN (`https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.33.0/...`) **only if the local `assets/tabler/` copy is missing**, and degrades to text labels if both fail. **With `assets/tabler/` present, no internet is used** — see the air-gap note in §4.

---

## 3. First-party assets (not external dependencies)

For completeness: the UI is built with **vanilla CSS/JS** — no jQuery, Bootstrap,
React, or other framework, and no CSS/JS pulled from a CDN.

| Asset | Purpose | License |
|---|---|---|
| `mousebook.css` | Application styling. | Project MIT (`LICENSE`, © 2018 Christopher S Ward). |
| `mousebook.js` | Client behavior (theme toggle, nav enhancement, Tabler loader). | Project MIT. |

---

## 4. Air-gap / offline capability

Mousebook can run **fully offline** on a private network. When operating
air-gapped, the only outbound network calls the application makes are:

1. **Database connection** — to the user-provided MySQL/MariaDB host (typically `localhost` or an intranet host).
2. **SMTP relay** *(optional)* — only if email is enabled; point it at an internal relay, or leave `smtp_host` empty to disable mail.
3. **Tabler icon CDN** *(fallback only, avoidable)* — never fetched when `assets/tabler/` is present, which it is in a normal deploy.

No other third-party service, telemetry, license server, or package registry is
contacted. There is one **non-runtime** external reference: `index.php` links to a
Google Doc for "Feature Requests and Bug Reports" — a user-clicked hyperlink, not
a runtime or install dependency.

---

## 5. License summary & compliance checklist

| Component | License | Redistributed here? |
|---|---|---|
| Mousebook (project) | MIT | — |
| FPDF 1.9 | FPDF permissive | Yes (`php/license.txt`) |
| PHPMailer 6.9.3 | LGPL-2.1 | Yes (`includes/vendor/PHPMailer/LICENSE`) |
| Tabler Icons 3.33.0 | MIT | Yes |
| Apache / PHP / MySQL / MariaDB | Apache-2.0 / PHP-3.01 / GPLv2 | No (user-provided) |

All bundled licenses are compatible with redistributing Mousebook under MIT.

**Compliance checklist (for a release):**
- [ ] Keep each vendored library's license file in place (FPDF `php/license.txt`; PHPMailer `LICENSE`). Tabler's MIT text is embedded in its distribution — consider adding a short `assets/tabler/LICENSE` note for completeness.
- [ ] Keep PHPMailer **unmodified** (preserves the simplest LGPL posture).
- [ ] Pin the bundled versions when upgrading (FPDF 1.9, PHPMailer 6.9.3, Tabler 3.33.0) and update this document.
- [ ] The stale `README.md` still cites "PHP 5.4 / MySQL 5.6" and the old search-replace install; the accurate stack is PHP 8.3 + MySQL 8.0/MariaDB with `config.php`. Refreshing the README is **M1-H** (#36), which should link this document.

---

*Grounded against the tree at `27459ff`. Update on version bumps or when a new
external resource is introduced.*
