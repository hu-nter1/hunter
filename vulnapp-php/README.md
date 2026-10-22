# ShopEase (PHP)

A normal-looking 5-page shop built in plain PHP + PDO (no framework) as an intentionally vulnerable target for security/API testing practice.

⚠️ **For local, authorized testing only.** Do not deploy publicly, do not use real data.

## Pages
1. `login.php` — SQL injection, plaintext credentials, and session fixation.
2. `products.php` / `api/search.php` — product browsing and search.
3. `profile.php` — profile display and earned-flag history.
4. `product.php` — product details and validated image display.
5. `admin.php` — admin pages and product/user/order management.
6. `register.php` — learner registration with an email address for achievement attribution.

Admin product management accepts an image URL or a local JPG, PNG, GIF, or WebP upload. Storefront
product cards preserve image aspect ratios and fit images within each card.

The current code does not implement every vulnerability named by its configuration switches.
See [VULNERABILITIES.md](VULNERABILITIES.md) for the source-verified inventory, limitations,
and flag storage/submission workflow. Use only an isolated local instance with synthetic data.

The named challenge-flag catalog is initialized in the active SQLite or PostgreSQL/Neon
database without resetting existing shop data. A signed-in learner can submit a discovered
flag at `/submit_flag.php` by pasting it or selecting its downloaded plain-text file from
their computer. Valid submissions download a small PDF certificate and are listed in the
Superadmin Hall of Heroes with username, email, flag, vulnerability type, and timestamp.
Uploaded files are limited to 16 KB, read as plain text, and not stored by the app. Flag
submission is a self-reported achievement and does not automatically detect an exploit.

## Setup
Needs PHP 8.x with the `sqlite3`/`pdo_sqlite` extension for local development (commonly
`php-sqlite3` on Debian/Ubuntu). The container also includes `pdo_pgsql` for Neon.
```bash
php -S 127.0.0.1:8081
```
Visit `http://127.0.0.1:8081`. `data/vulnapp.db` is created and seeded automatically on first request; delete it to reset.
To use PostgreSQL instead, set `DATABASE_URL` to a PostgreSQL connection URL; the schema and demo rows are initialized automatically. The seeded catalog includes product photography, and an empty database receives the demo users and products.

To intentionally replace **all** shop records in a PostgreSQL database with the demo users and products, set `DATABASE_URL` to that database and run the reset command from a one-off job:
```bash
php scripts/reset_demo_data.php --confirm
```
This deletes the users, products, orders, comments, reviews, flags, and activity records before reseeding. It refuses to run from a web request or against SQLite. Only use it when replacing the existing data is intended.

Demo logins: `alice/alice123`, `bob/bobpass`, `admin/admin!2024`

Register a new account at `/register.php`.

Make sure the `uploads/` folder is writable by the PHP process (`chmod 755 uploads` is enough on most local setups).


## Testing locally
```bash
cd vulnapp-php
php -S 127.0.0.1:8081
```
Open `http://127.0.0.1:8081/register.php` in a browser, or test the API directly:
```bash
# normal registration (auto-logs in, redirects to /products.php)
curl -i -X POST http://127.0.0.1:8081/register.php \
  -d "username=testuser&password=1234&confirm=1234&email=t@x.com"

# mass-assignment exploit: is_admin isn't on the form but the backend trusts it anyway
curl -i -X POST http://127.0.0.1:8081/register.php \
  -d "username=evil&password=1&confirm=1&is_admin=1&ssn=111-11-1111"

# registering an existing username -> user enumeration
curl -i -X POST http://127.0.0.1:8081/register.php \
  -d "username=alice&password=x&confirm=x"
```
Delete `vulnapp.db` any time to reset to the seeded demo data.

## Deploying to Render
This is a single PHP service. The included Dockerfile installs PDO support for SQLite and
PostgreSQL and enables the HTTP Basic Auth gate. Configure `LAB_AUTH_USER`, `LAB_AUTH_PASS`,
and `SUPERADMIN_KEY` in the Render service environment before exposing the intentionally
vulnerable lab.

For durable relational data, set `DATABASE_URL` as a secret environment variable in the Render
service, using a Neon PostgreSQL URL with SSL (`sslmode=require`). When Neon is empty, the app
creates its schema and seeds the demo accounts and product catalog. Product uploads remain in
`uploads/` and need separate durable file storage to survive redeploys.

Render startup does not reset or delete existing Neon data. To intentionally replace it, run the
guarded `php scripts/reset_demo_data.php --confirm` command from an authorized one-off job or
shell session. Do not enable a startup reset for a persistent production database.
