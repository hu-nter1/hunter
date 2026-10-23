# Vulnerability Lab Inventory

This lab contains 37 distinct, toggleable vulnerabilities for educational security testing. Each can be managed via the Admin Settings page or the Superadmin Backend.

## Authentication & Session
- [ ] VULN-004: SQL Injection in Login (login.php)
- [ ] VULN-005: Weak Password Hashing (login.php)
- [ ] VULN-006: No Rate Limiting (login.php)
- [ ] VULN-007: Predictable Session Tokens (includes/session_check.php)
- [ ] VULN-008: Session Fixation (includes/session_check.php)
- [ ] VULN-009: Username Enumeration (login.php)
- [ ] VULN-024: Insecure "Remember Me" (includes/session_check.php)

## SQL Injection Depth
- [ ] VULN-001: SQL Injection in Product Search (products.php)
- [ ] VULN-010: Error-based SQL Injection (products.php)
- [ ] VULN-011: Blind Boolean SQL Injection (products.php)
- [ ] VULN-012: Blind Time-based SQL Injection (products.php)
- [ ] VULN-014: Header-based SQL Injection (includes/log_ua.php)
- [ ] VULN-031: SQL Comment Filter Bypass (products.php)

## XSS & Client-Side
- [ ] VULN-015: Reflected XSS (products.php)
- [ ] VULN-016: Stored XSS (product.php)
- [ ] VULN-017: Stored XSS Bypass (product.php)
- [ ] VULN-018: XSS via Attribute Injection (profile.php)
- [ ] VULN-019: DOM-based XSS (product.php)
- [ ] VULN-020: Insecure use of eval() (product.php)

## Access Control & Business Logic
- [ ] VULN-002: IDOR on Order Viewing (order_success.php)
- [ ] VULN-003: Path Traversal in Admin Panel (admin.php)
- [ ] VULN-021: Broken Access Control (admin.php)
- [ ] VULN-022: IDOR on Profile Edit (pages/edit_user.php)
- [ ] VULN-023: Horizontal Priv Esc (cart.php)
- [ ] VULN-024: Vertical Priv Esc (admin.php)
- [ ] VULN-025: HTTP Method Tampering (includes/session_check.php)
- [ ] VULN-026: URL Quirk Bypass (admin.php)
- [ ] VULN-027: Price/Quantity Tampering (checkout.php)
- [ ] VULN-028: Mass Assignment (checkout.php)
- [ ] VULN-029: Integer Overflow (pages/update_cart_quantity.php)

## File Handling & SSRF
- [ ] VULN-030: Unrestricted File Upload (product.php)
- [ ] VULN-031: Path Traversal (Download) (download_receipt.php)
- [ ] VULN-032: SSRF (pages/fetch_remote_image.php)
- [ ] VULN-033: XXE Injection (pages/import_products.php)

## Information Disclosure
- [ ] VULN-034: Verbose Error Exposure (config.php)
- [ ] VULN-035: Exposed Backup Files (config.php.bak)
- [ ] VULN-036: Debug Endpoints (debug.php)
- [ ] VULN-037: Missing Security Headers (includes/header.php)
