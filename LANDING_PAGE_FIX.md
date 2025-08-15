# Landing Page Company Determination Fix

## Issue
The `index.php` landing page was incorrectly trying to determine company context and redirecting users to "Company Not Found" instead of showing the marketing/signup page.

## Root Cause
The original `index.php` had unnecessary company determination logic that was trying to figure out which company the user belonged to, even though this should be a simple marketing landing page for new signups.

## Problem with Original Logic
```php
// This was WRONG for a landing page:
$company_context = null;
$company_subdomain = null;
// ... complex company determination logic
```

The landing page should NOT try to determine company context - it should just:
1. Show marketing content
2. Offer signup and login options
3. Only redirect if user is already logged in

## Solution
Simplified the `index.php` to remove unnecessary company determination:

### Before (Problematic):
- Tried to determine company context on every visit
- Had complex logic that could fail and redirect to error pages
- Mixed marketing page with application logic

### After (Fixed):
- Simple session check for already-logged-in users
- Handles subdomain redirects (company.vessellogger.com)
- Handles direct company parameter (?company=xyz)
- Otherwise just shows the landing page - NO company determination

## New Logic Flow
1. **Already logged in?** → Redirect to dashboard
2. **Subdomain access?** → Redirect to company login
3. **Company parameter?** → Redirect to company login  
4. **Otherwise** → Show landing page (no company logic needed)

## Result
✅ `logicdock.org` now shows proper marketing landing page
✅ Users can sign up for new accounts
✅ Users can log into existing companies
✅ No more "Company Not Found" errors on the landing page
✅ Clean separation between marketing and application logic

## Key Lesson
**Landing pages should be simple!** They don't need complex application logic - just marketing content and clear calls-to-action for signup/login.
