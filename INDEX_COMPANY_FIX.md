# Index.php Company-Not-Found Fix

## Issue
When accessing `http://logicdock.org/index.php`, users were being redirected to `company-not-found.php` instead of seeing the landing page.

## Root Cause
The `determineCompanyContext()` function in `config_saas.php` didn't have a domain mapping for `logicdock.org`, so it returned `null` for the company context. This triggered the redirect logic in the SaaS configuration.

## Solution
Added `logicdock.org` to the domain mappings in `config_saas.php`:

```php
// Method 4: Installation domain mapping
// For single-domain installations with path-based separation
$install_mappings = [
    'localhost' => 'demo_company',
    'vessel.company-a.com' => 'company_a',
    'vessel.company-b.com' => 'company_b',
    'logicdock.org' => 'logicdock'  // <- Added this mapping
];
```

## Result
- ✅ `index.php` now displays the proper SaaS landing page
- ✅ Users can access signup and login links from the homepage
- ✅ Existing logged-in users are properly redirected to their dashboard
- ✅ Company context is correctly determined for `logicdock.org`

## Flow Now Works
1. User visits `logicdock.org` or `logicdock.org/index.php`
2. Landing page displays with signup/login options
3. Users can sign up for new accounts or log into existing ones
4. Post-login redirects work properly to the welcome dashboard

The SaaS platform now has a complete user journey from landing page → signup → welcome dashboard!
