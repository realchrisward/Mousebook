# Screenshots

The install and admin docs reference the images below. They are **placeholders**
— the `.png` files are not in the repository yet. Until they are added, the
docs render with a broken-image icon and the caption underneath, which still
reads correctly; nothing is misleading, just unillustrated.

Capture these from a real install (a throwaway colony with a handful of fake
animals looks far better than an empty one), drop them in this folder under
exactly these filenames, and they will appear.

| File | Referenced from | What to capture |
|---|---|---|
| `01-home.png` | README.md | The colony home page: left-hand nav, the animal/cage summary. The showcase image — use a colony with some data in it. |
| `02-setup-complete.png` | INSTALL.md §A5 | The terminal at the end of a clean `setup.sh` run: the green `[OK]` verification block and the "Setup complete" banner. |
| `03-login.png` | INSTALL.md §5 | `pages/databases.php` before logging in: username, password, the "forgot password" link. |
| `04-database-chooser.png` | INSTALL.md §5 | Straight after logging in: the colony button and the `userbook` button side by side. |
| `05-manage-users.png` | INSTALL.md §5 | Manage Users with the admin account listed and its tiers on both databases. |
| `06-userbook-sidebar.png` | ADMIN_GUIDE.md §1 | The sidebar in the `userbook` context, with Manage Users and Manage Databases visible. Contrast with the colony sidebar. |
| `07-add-user.png` | ADMIN_GUIDE.md §3 | The new-user form plus the access-grant table beneath it. |
| `08-manage-databases.png` | ADMIN_GUIDE.md §5 | Manage Databases listing the registered colonies. |

## Before you publish them

- **Redact credentials.** `02-setup-complete.png` is a terminal shot; make sure
  no password scrolled into frame.
- **Use fake names.** No real lab members in `05` or `07`.
- Crop to the browser content area (no OS chrome, no bookmark bars).
- ~1200 px wide is plenty; PNG.
