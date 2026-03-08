## Summary

<!-- 1-3 bullet points explaining what this PR does and why. Keep it under 30 seconds to read. -->

- 
- 
- 

## Changes

<!-- List specific files or areas changed, grouped logically. -->

| Area | Files Changed |
|------|--------------|
|  |  |

## Test Plan

<!-- Checklist of steps to verify the changes work correctly. -->

- [ ] 
- [ ] 
- [ ] 

## Related Issues

<!-- Reference ticket numbers: Fixes #123 or Relates to #456 -->

---

## Peer Review Checklist

> To be completed by the **reviewer**, not the author.

### Code Standards
- [ ] PHPCS passes — `composer run lint` returns 0 errors
- [ ] Functions and variables use `snake_case`
- [ ] Classes use `PascalCase`, constants use `UPPER_SNAKE_CASE`
- [ ] 2-space indentation, no tabs
- [ ] No `TODO` or `FIXME` comments — convert to tracked issues
- [ ] No commented-out code blocks

### OWASP A01 — Broken Access Control
- [ ] `current_user_can()` check before every privileged action
- [ ] No direct object references without ownership verification
- [ ] Role-based checks on all admin endpoints

### OWASP A03 — Injection
- [ ] All database queries use `$wpdb->prepare()` — zero raw SQL
- [ ] `sanitize_sql_like()` used where needed
- [ ] No dynamic query construction from user input

### OWASP A07 — Authentication Failures
- [ ] Nonce verification on all form submissions: `wp_verify_nonce()`
- [ ] AJAX requests use `check_ajax_referer()`
- [ ] Secure session handling — no custom session logic

### Input Sanitization
- [ ] `sanitize_text_field()` for text inputs
- [ ] `absint()` for integer inputs
- [ ] `sanitize_email()` for email inputs
- [ ] All `$_POST`, `$_GET`, `$_REQUEST` values sanitized before use

### Output Escaping
- [ ] `esc_html()` for plain text output
- [ ] `esc_url()` for URLs
- [ ] `esc_attr()` for HTML attributes
- [ ] `wp_kses()` for HTML content
- [ ] No unescaped dynamic output in templates

### No Hardcoded Secrets
- [ ] Zero API keys, tokens, passwords in code
- [ ] Credentials use `wp-config.php` constants or environment variables
- [ ] `.env` files are in `.gitignore`

### Complexity
- [ ] Cyclomatic complexity per function ≤ 10
- [ ] Max nesting depth ≤ 3 levels
- [ ] Early returns / guard clauses used to reduce nesting

### Test Coverage
- [ ] New functions have unit tests (PHPUnit)
- [ ] Critical paths have integration tests
- [ ] All new tests pass locally

### WordPress Specific
- [ ] Scripts enqueued via `wp_enqueue_scripts` — no inline `<script>`
- [ ] Strings use `__()` or `_e()` with text domain `custom-plugin`
- [ ] `vendor/autoload.php` guarded with `class_exists()` check

---

## Branch Type

- [ ] `feature/` — peer review + lead final review required
- [ ] `bugfix/` — peer review + lead final review required
- [ ] `hotfix/` — lead review direct, back-merge to develop planned
- [ ] `db/` — DBA review required, Liquibase changelog included
