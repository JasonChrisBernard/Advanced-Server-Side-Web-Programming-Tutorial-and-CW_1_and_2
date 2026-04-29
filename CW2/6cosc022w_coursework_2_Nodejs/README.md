# 6COSC022W Coursework 1 + Coursework 2

An Express, EJS, SQLite, and Nodemailer coursework project for the CW1 alumni portal on `localhost:3000` and the CW2 staff analytics dashboard on `localhost:3001`.

## Main features

- Alumni registration with allowed-domain validation, duplicate checking, and strong password rules
- Email verification with secure random tokens and expiry handling
- Login, logout, session timeout handling, CSRF protection, and rate limiting
- Forgot-password and reset-password flow with expiring tokens
- Alumni profile creation with:
  - programme, graduation date, and industry sector for CW2 staff filtering
  - biography and LinkedIn URL
  - multiple degrees, certifications, licences, short courses
  - employment history
  - profile image upload
  - profile edit and delete
- Blind bidding with:
  - hidden highest bid
  - live Winning/Not Winning feedback without revealing the highest amount
  - cancel bid for tomorrow's slot
  - increase-only updates
  - daily featured-slot bidding for tomorrow's slot
  - monthly appearance-limit tracking (3 features per month)
  - one verified alumni-event credit that unlocks a 4th monthly appearance
  - automated midnight winner resolution
  - recovery polling that finalizes overdue featured days after restarts or missed midnight runs
  - win/lose email notifications
- Security implementation with:
  - bcrypt password hashing
  - random verification/reset/API tokens
  - custom Helmet-style headers
  - configured API CORS policy
  - CSRF protection for browser forms
  - rate limiting on sensitive routes
- Public developer API with:
  - bearer-token client management
  - scoped API keys for Analytics Dashboard and Mobile AR App clients
  - `read:alumni`, `read:analytics`, and `read:alumni_of_day` permissions
  - token expiry handling
  - usage statistics and endpoint logging
  - token revocation
  - OpenAPI JSON and interactive Swagger UI
- Role-based navigation and route protection:
  - alumni can manage profiles and blind bids
  - developers can manage API clients, keys, usage logs, and API docs
- CW2 staff dashboard with:
  - dashboard KPI cards
  - graphs pages with 8 interactive local Chart.js visualizations including bar, line, pie, doughnut, radar, polar-area, legends, tooltips, and axis labels
  - alumni directory filters by programme, graduation date/year, and industry sector
  - global API key usage and endpoint-access logs

## Run the project

Install dependencies:

```bash
npm install
```

Start the app:

```bash
npm start
```

Start the CW1 alumni portal on `localhost:3000`:

```bash
npm run start:cw1
```

Start the CW2 staff dashboard on `localhost:3001`:

```bash
npm run start:cw2
```

Start with auto-reload during development:

```bash
npm run dev
```

Default local URLs:

- CW1 alumni portal: [http://localhost:3000](http://localhost:3000)
- CW2 staff dashboard: [http://localhost:3001](http://localhost:3001)

Seeded staff dashboard account for local CW2 testing:

- Email: `staff@iit.ac.lk`
- Password: `Password123!`

## Environment variables

Create a local `.env` file by copying `.env.example` and then fill in the values you want to use.

Important variables:

- `PORT`: Express server port
- `SESSION_SECRET`: session-signing secret
- `SESSION_MAX_AGE_MINUTES`: inactivity timeout for session cookies
- `ALLOWED_EMAIL_DOMAINS`: comma-separated registration allow-list
- `DEVELOPER_EMAILS`: comma-separated emails that should receive the `developer` role
- `STAFF_PORTAL_EMAILS`: comma-separated verified staff dashboard accounts to seed
- `STAFF_PORTAL_DEFAULT_PASSWORD`: local seed password for staff dashboard accounts
- `BCRYPT_SALT_ROUNDS`: password hashing cost factor
- `VERIFICATION_TOKEN_TTL_MINUTES`: expiry for email verification links
- `API_TOKEN_TTL_DAYS`: expiry window for bearer tokens created in the developer portal
- `ALUMNI_EVENT_CATALOG`: semicolon-separated verified alumni events in the format `Event Name|YYYY-MM-DD|ATTENDANCE_CODE`
- `APP_BASE_URL`: base URL used in email links
- `MAIL_*`: SMTP sender configuration
- `CORS_ALLOWED_ORIGINS`: comma-separated origins allowed to call `/api/*` from browsers
- `BLIND_BID_RECOVERY_INTERVAL_MINUTES`: background polling interval used to catch up missed blind-bid resolutions

## API documentation

- Human-readable docs: [http://localhost:3000/developer-api/docs](http://localhost:3000/developer-api/docs)
- Interactive Swagger UI: [http://localhost:3000/api-docs](http://localhost:3000/api-docs)
- OpenAPI JSON: [http://localhost:3000/developer-api/swagger.json](http://localhost:3000/developer-api/swagger.json)

Protected endpoints:

- `GET /api/v1/alumni` requires `read:alumni`
- `GET /api/v1/analytics/summary` requires `read:analytics`
- `GET /api/v1/alumni-of-day` requires `read:alumni_of_day`
- `GET /api/v1/featured-alumnus/today` is a compatibility alias for `read:alumni_of_day`

It requires:

```http
Authorization: Bearer YOUR_TOKEN_HERE
```

Create and revoke tokens from:

- `GET /developer-portal`

## Architecture summary

- `server.js` bootstraps Express, session security, headers, CSRF, routers, and background jobs
- `src/controllers/` contains request handlers for auth, profiles, blind bidding, and developer API
- `src/models/` contains SQLite query logic
- `src/services/` contains mail delivery, featured alumnus logic, and blind-bid automation
- `src/middleware/` contains auth, bearer-token auth, CSRF, CORS, and rate limiting helpers
- `src/views/` contains EJS pages
- `src/docs/` contains OpenAPI and project documentation

Additional design notes are documented in:

- `docs/architecture.md`
- `docs/database-schema.md`

## Database notes

The project uses normalized tables for:

- users
- alumni profiles and section tables
- blind bids, verified university events, alumni-event credits, and daily featured winners
- developer API clients, tokens, and usage logs

See `docs/database-schema.md` for the entity relationship diagram and indexing notes.
