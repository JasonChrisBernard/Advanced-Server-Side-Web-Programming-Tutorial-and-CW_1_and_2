// Allow access only when a logged-in user exists in the session.
exports.requireAuth = (req, res, next) => {
  // If there is no session user, send the visitor to the login page.
  if (!req.session.user) {
    return res.redirect("/login");
  }

  // Otherwise continue to the protected route.
  next();
};

exports.requireRole = (allowedRoles) => {
  const roleList = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];

  return (req, res, next) => {
    if (!req.session.user) {
      return res.redirect("/login");
    }

    if (!roleList.includes(req.session.user.role)) {
      return res.status(403).render("auth/message", {
        title: "Access restricted",
        message: "Your account role does not have permission to access this page.",
      });
    }

    next();
  };
};

// Allow access only when the visitor is not logged in yet.
exports.requireGuest = (req, res, next) => {
  // If a user is already logged in, sending them to login/register makes no sense.
  if (req.session.user) {
    return res.redirect("/");
  }

  // Otherwise continue to the guest-only route.
  next();
};
