const Alumni = require('../models/Alumni');

async function attachUser(req, res, next) {
  res.locals.currentUser = null;

  if (!req.session.alumniId) return next();

  try {
    const user = await Alumni.findById(req.session.alumniId);

    if (user) {
      req.user = user;
      res.locals.currentUser = user;
    }

    next();
  } catch (error) {
    next(error);
  }
}

function requireAuth(req, res, next) {
  if (!req.user) {
    req.flash('error', 'Please log in to continue.');
    return res.redirect('/auth/login');
  }

  next();
}

function requireVerified(req, res, next) {
  if (!req.user.email_verified) {
    req.flash('error', 'Please verify your university email before using this feature.');
    return res.redirect('/profile');
  }

  next();
}

function requireDeveloper(req, res, next) {
  if (!req.user || req.user.role !== 'developer') {
    req.flash('error', 'Developer access only.');
    return res.redirect('/profile');
  }

  next();
}

function requireAlumni(req, res, next) {
  if (!req.user || req.user.role !== 'alumni') {
    req.flash('error', 'Only alumni accounts can place bids.');
    return res.redirect('/bids');
  }

  next();
}

module.exports = {
  attachUser,
  requireAuth,
  requireVerified,
  requireDeveloper,
  requireAlumni
};