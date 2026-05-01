# Tutorial 10 Alumni Platform Coursework Project

This project implements all three requested exercises using **Node.js, Express, EJS and MySQL** in an MVC-style folder structure.

## Included Exercises

### Exercise 1 - Alumni Profile Management System
- Alumni registration with university email domain validation.
- Secure password hashing using bcrypt.
- Email verification token flow.
- Login, logout, sessions and password reset flow.
- Profile editing with program, graduation year, industry, job title, company and LinkedIn URL.
- Profile image upload using Multer.
- Add, edit and delete:
  - Degrees
  - Certifications
  - Licenses
  - Professional courses
- Add, edit and delete employment history.
- EJS server-side rendered pages.

### Exercise 2 - Alumni Blind Bidding System
- Alumni can place a blind bid without seeing the current highest bid.
- Alumni can increase their own bid for the same day.
- Only one bid record per alumni per day.
- Maximum of 3 bidding days per alumni per month.
- Automated midnight winner selection using `node-cron`.
- Manual winner selection button included for coursework demonstration; it selects from today’s active bids so screenshots can be taken immediately.
- Alumni of the Day is stored for a 24-hour feature date.

### Exercise 3 - University Analytics Dashboard
- MySQL-backed alumni and bid data.
- Dashboard filters by program, graduation year and industry sector.
- Chart.js visualizations for:
  - Alumni by program
  - Industry demand
  - Career pathways
  - Graduation year distribution
  - Professional development trends
  - Monthly bidding activity
  - Skill gap indicators
- API key system for external client access.
- Granular API permissions:
  - `read:analytics`
  - `read:profiles`
  - `read:bids`

---

## Folder Structure

```text
tut10_alumni_system/
├── database/
│   └── schema.sql
├── public/
│   ├── css/
│   └── uploads/
├── scripts/
│   └── createApiKey.js
├── src/
│   ├── config/
│   ├── controllers/
│   ├── jobs/
│   ├── middleware/
│   ├── models/
│   ├── routes/
│   ├── services/
│   ├── utils/
│   ├── app.js
│   └── server.js
├── views/
│   ├── auth/
│   ├── bids/
│   ├── dashboard/
│   ├── errors/
│   ├── partials/
│   └── profile/
├── .env.example
├── package.json
└── README.md
```

---

## Setup Steps

### 1. Install dependencies

```bash
npm install
```

### 2. Create the MySQL database

Open MySQL, phpMyAdmin, MySQL Workbench or XAMPP MySQL shell and run:

```sql
SOURCE database/schema.sql;
```

Or copy and paste the SQL from `database/schema.sql` into phpMyAdmin.

### 3. Create `.env`

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

Then update the database details:

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=alumni_platform
UNIVERSITY_EMAIL_DOMAIN=westminster.ac.uk
```

For local testing, SMTP can be left empty. Verification and password reset links will be printed in the terminal.

### 4. Run the app

```bash
npm run dev
```

Open:

```text
http://localhost:3000
```

---

## Creating an API Key

After the database is ready, run:

```bash
npm run create-api-key "Dashboard Client" "read:analytics,read:profiles,read:bids"
```

The command prints one raw API key. Copy it immediately.

Example API request:

```bash
curl -H "x-api-key: YOUR_API_KEY" http://localhost:3000/api/analytics
```

Other secure API endpoints:

```text
GET /api/analytics   requires read:analytics
GET /api/profiles    requires read:profiles
GET /api/bids        requires read:bids
```

---

## Coursework Screenshot Checklist

Suggested screenshots to capture:

1. Registration page with university email validation.
2. Login page.
3. Terminal showing verification email link in development mode.
4. Alumni profile page.
5. Add degree/certification/license/course page.
6. Employment history CRUD page.
7. Profile image upload result.
8. Blind bidding page showing monthly limit and private bid form.
9. Bid history table.
10. Alumni of the Day result after running midnight selection demo.
11. Analytics dashboard with charts.
12. Dashboard filters.
13. API key creation terminal output.
14. API response from `/api/analytics` using `x-api-key`.

---

## Notes

- This is a coursework prototype, so the manual bidding selection button is included to make demonstration easier.
- The real scheduled job runs every day at midnight using the `Asia/Colombo` timezone.
- For production, configure a real SMTP account, HTTPS, stronger session storage, CSRF protection and admin role separation.
