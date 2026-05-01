const crypto = require("crypto");

function generateToken(req) {
  const token = crypto.randomBytes(32).toString("hex");
  req.session.csrfToken = token;
  return token;
}

function attachCsrfToken(req, res, next) {
  if (req.method === "GET") {
    res.locals.csrfToken = generateToken(req);
  }
  next();
}

function validateCsrfToken(req, res, next) {
  const protectedMethods = ["POST", "PUT", "PATCH", "DELETE"];

  if (!protectedMethods.includes(req.method)) {
    return next();
  }

  const sessionToken = req.session.csrfToken;
  const submittedToken = req.body._csrf || req.headers["x-csrf-token"];

  if (!sessionToken || !submittedToken) {
    return res.status(403).render("error", {
      message: "CSRF validation failed: token missing."
    });
  }

  const sessionBuffer = Buffer.from(sessionToken);
  const submittedBuffer = Buffer.from(submittedToken);

  const isValid =
    sessionBuffer.length === submittedBuffer.length &&
    crypto.timingSafeEqual(sessionBuffer, submittedBuffer);

  if (!isValid) {
    return res.status(403).render("error", {
      message: "CSRF validation failed: invalid token."
    });
  }

  next();
}

module.exports = {
  attachCsrfToken,
  validateCsrfToken
};