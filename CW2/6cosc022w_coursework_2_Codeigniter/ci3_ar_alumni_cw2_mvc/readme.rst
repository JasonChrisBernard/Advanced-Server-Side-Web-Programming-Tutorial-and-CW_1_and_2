################################################
AR Alumni CW1/CW2 - CodeIgniter 3 MVC Application
################################################

This project is a CodeIgniter 3 coursework application split into two local
port-based portals: CW1 runs on ``localhost:8080`` and CW2 runs on
``localhost:8081``.

************
Key Features
************

- Staff dashboard with summary cards, API-loaded charts, loading states, and navigation.
- CW1 alumni/developer portal remains available on port ``8080``.
- Analytics report screen with 8 chart datasets, multiple Chart.js chart types, insight cards, CSV export, print report, and chart PNG download.
- Alumni filtering by programme, graduation year, and industry sector with filtered CSV export.
- Scoped bearer-token API keys for ``mobile_ar_app`` and ``analytics_dashboard`` clients.
- Swagger/OpenAPI documentation for scoped endpoints and required permissions.
- Security controls including CSRF for web forms, HttpOnly cookies, restricted CORS, rate limiting, request logging, and response security headers.

***********
Quick Start
***********

1. Install PHP with PDO SQLite enabled.
2. Start both CodeIgniter dev servers from the project root in two terminals::

      php -S localhost:8080

      php -S localhost:8081

3. Use ``http://localhost:8080`` for CW1 alumni/developer work.
4. Use ``http://localhost:8081`` for CW2 staff analytics work.
5. If the SQLite database is missing, run the setup scripts documented in
   ``docs/SETUP.md``.
6. Review API scopes and examples in ``docs/API_GUIDE.md``.
7. Review the entity relationships in ``docs/ERD.md``.

The application detects the active port and routes the default page
accordingly: ``8080`` opens CW1 login, while ``8081`` opens CW2 staff login.

*******************
Main Web Routes
*******************

- ``http://localhost:8080/index.php/login`` - CW1 alumni/developer login.
- ``http://localhost:8080/index.php/profile`` - CW1 alumni profile management.
- ``http://localhost:8080/index.php/bidding`` - CW1 Alumni of the Day bidding.
- ``http://localhost:8081/index.php/staff/dashboard`` - CW2 University analytics dashboard.
- ``http://localhost:8081/index.php/staff/graphs`` - CW2 full analytics graph report.
- ``http://localhost:8081/index.php/staff/reports/charts-export`` - CSV export for chart datasets.
- ``http://localhost:8081/index.php/api-docs`` - Swagger UI for the scoped API.

*******************
Main API Routes
*******************

- ``GET /index.php/api/v1/mobile/alumni-of-day`` requires ``read:alumni_of_day``.
- ``GET /index.php/api/v1/alumni/{id}`` requires ``read:alumni``.
- ``GET /index.php/api/v1/analytics/summary`` requires ``read:analytics``.
- ``GET /index.php/api/v1/analytics/charts`` requires ``read:analytics``.

API requests use::

   Authorization: Bearer YOUR_API_KEY

******************
Submission Notes
******************

The ``.env.example`` file documents expected local settings. Runtime artifacts
such as SQLite databases, uploaded files, and one-off setup runner copies are
ignored in ``.gitignore`` so the submitted project stays clean.
