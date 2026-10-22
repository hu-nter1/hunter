# ShopEase (PHP)

A normal-looking 5-page shop built in plain PHP + PDO (no framework) as an intentionally vulnerable target for security/API testing practice.

⚠️ **For local, authorized testing only.** Do not deploy publicly, do not use real data.

## Pages
1. `login.php` — SQL injection auth bypass + insecure deserialization ("remember me" cookie → PHP Object Injection)
2. `products.php` — UNION-based SQL injection
3. `profile.php?id=` — IDOR
4. `product.php?id=` — stored XSS (comments) + unrestricted file upload
5. `admin.php` — broken access control, local file inclusion, OS command injection
6. `register.php` — mass assignment (register as admin), user enumeration, no password policy

Admin product management accepts an image URL or a local JPG, PNG, GIF, or WebP upload. Storefront
product cards preserve image aspect ratios and fit images within each card.

The file upload (page 4) and the LFI (page 5) chain together into full remote code execution — see **VULNERABILITIES.md** for the exact walkthrough, plus curl commands and CWE ids for every bug.

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
This truncates the users, products, orders, comments, reviews, flags, and activity tables before reseeding. It refuses to run from a web request or against SQLite. Only use it when replacing the existing data is intended.

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

If replacing existing database contents is intended, run
`php scripts/reset_demo_data.php --confirm` as a one-off job using the service's environment.
This operation truncates the shop's data tables and reseeds the demo users and products.
