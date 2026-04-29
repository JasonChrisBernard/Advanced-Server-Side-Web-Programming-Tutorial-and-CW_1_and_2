# Architecture Notes

This coursework project is organized into clear layers so features can evolve without placing all logic in one file.

## Layered structure

- `server.js`
  - application bootstrap
  - security headers, CORS, CSRF, session configuration
  - route mounting
  - startup jobs such as mail verification and blind-bid resolution
- `src/routes`
  - HTTP route definitions
  - route-level middleware such as auth and rate limiting
- `src/controllers`
  - request handling and response rendering
  - validation flow orchestration
  - coordination between models and services
- `src/models`
  - SQL queries and database transactions
  - normalized persistence logic
- `src/services`
  - email delivery
  - featured alumnus selection
  - blind-bid automation
- `src/middleware`
  - session auth
  - bearer-token auth
  - CSRF
  - CORS
  - rate limiting
- `src/views`
  - EJS web pages for user-facing features

## Feature mapping

- Auth:
  - routes: `src/routes/authRoutes.js`
  - controller: `src/controllers/authController.js`
  - model: `src/models/userModel.js`
  - service: `src/services/emailService.js`
- Alumni profile:
  - routes: `src/routes/profileRoutes.js`
  - controller: `src/controllers/profileController.js`
  - model: `src/models/alumniProfileModel.js`
- Blind bidding:
  - routes: `src/routes/blindBidRoutes.js`
  - controller: `src/controllers/blindBidController.js`
  - model: `src/models/blindBidModel.js`
  - services: `src/services/blindBidService.js`, `src/services/featuredAlumnusService.js`
- Developer API:
  - routes: `src/routes/developerApiRoutes.js`
  - controller: `src/controllers/developerApiController.js`
  - model: `src/models/apiClientModel.js`
  - middleware: `src/middleware/apiAuthMiddleware.js`
  - docs: `src/docs/developerApiSpec.js`

## Request flow examples

### Browser auth flow

1. Browser requests a page.
2. `server.js` applies headers, sessions, CSRF setup, and routers.
3. `authRoutes.js` maps the request to `authController.js`.
4. `authController.js` validates input and calls `userModel.js`.
5. If email is needed, `emailService.js` sends or logs the message.
6. EJS view renders the response.

### Developer API flow

1. External client sends `Authorization: Bearer <token>`.
2. `apiAuthMiddleware.js` hashes the token and validates it through `apiClientModel.js`.
3. On success, usage is logged and the request proceeds.
4. `developerApiController.js` calls `featuredAlumnusService.js`.
5. `featuredAlumnusService.js` reads today's resolved winning bid and returns the active featured alumnus.

### Blind bidding resolution flow

1. An alumnus places or updates a bid for tomorrow's featured day.
2. `blindBidController.js` checks the user's monthly win count and whether a verified alumni-event attendance code unlocks a 4th appearance.
3. `blindBidService.js` runs at startup, at local midnight, and on a recovery interval to finalize any overdue featured days.
4. `blindBidModel.js` resolves the highest eligible bid and skips bidders who already used their monthly allowance.
5. Winner and loser emails are sent after resolution when SMTP is configured.

## Security summary

- Passwords are hashed with bcrypt
- Verification and reset tokens are cryptographically random and stored as hashes
- Verification and reset links expire
- Sessions use signed cookies with timeout handling
- CSRF tokens protect browser form submissions
- Rate limiting protects sensitive routes
- Bearer tokens are stored as hashes, not plain text
- Bearer tokens expire automatically and overdue tokens are marked `EXPIRED`
- Security headers and API CORS rules are applied centrally
