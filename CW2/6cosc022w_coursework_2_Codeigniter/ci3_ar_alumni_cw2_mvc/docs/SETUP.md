# Setup Guide

## Requirements

- PHP with `pdo_sqlite` enabled.
- A browser for the CW1 alumni portal, CW2 staff dashboard, and Swagger UI.
- CW1 local URL: `http://localhost:8080/`.
- CW2 local URL: `http://localhost:8081/`.

## First Run

1. Open a terminal in the project root.
2. Start both development servers in separate terminals:

   CW1 alumni/developer portal:

   ```powershell
   php -S localhost:8080
   ```

   CW2 staff analytics portal:

   ```powershell
   php -S localhost:8081
   ```

3. If `application/data/ar_alumni.sqlite` does not exist, run the setup runners in this order from the browser or CLI:

   - `setup_database_done.php`
   - `setup_profile_tables_done.php`
   - `setup_bidding_tables_done.php`
   - `setup_api_tables_done.php`
   - `setup_staff_auth_tables_done.php`
   - `setup_cw2_analytics_tables_done.php`
   - `setup_api_scope_upgrade_done.php`

4. Visit `http://localhost:8080` for CW1. It opens `auth/login`.
5. Visit `http://localhost:8081` for CW2. It opens `staff_auth/login`.
6. Generate API keys from `http://localhost:8080/index.php/api-keys` using separate platforms for mobile and analytics clients.

## Configuration Checklist

- `application/config/config.php` now detects the current host/port, so links stay on `8080` for CW1 and `8081` for CW2.
- CW1 and CW2 use separate session and CSRF cookie names so both portals can stay open in the same browser.
- Keep `cookie_httponly` enabled.
- Restrict `api_allowed_origins` to the URLs used by the local dashboard/mobile client.
- Use `.env.example` as a submission checklist for deployment values.

## Clean Submission Checklist

- Do not submit local SQLite database files unless your module tutor asks for seeded demo data.
- Do not submit uploaded profile images unless they are required sample assets.
- Keep setup runner copies renamed or ignored after setup is complete.
