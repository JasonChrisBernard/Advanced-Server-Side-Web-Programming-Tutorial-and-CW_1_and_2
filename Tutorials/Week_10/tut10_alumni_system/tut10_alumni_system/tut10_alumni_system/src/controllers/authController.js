const bcrypt = require('bcryptjs');
const { validationResult } = require('express-validator');
const Alumni = require('../models/Alumni');
const { randomToken } = require('../utils/token');
const { sendVerificationEmail, sendPasswordResetEmail } = require('../services/emailService');

function getUniversityDomain() {
  return (process.env.UNIVERSITY_EMAIL_DOMAIN || 'westminster.ac.uk').toLowerCase();
}

function renderRegister(req, res) {
  res.render('auth/register', { title: 'Alumni Registration', formData: {}, errors: [] });
}

async function register(req, res, next) {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(422).render('auth/register', {
        title: 'Alumni Registration',
        formData: req.body,
        errors: errors.array()
      });
    }

    const domain = getUniversityDomain();
    if (!req.body.email.toLowerCase().endsWith(`@${domain}`)) {
      return res.status(422).render('auth/register', {
        title: 'Alumni Registration',
        formData: req.body,
        errors: [{ msg: `Email must use the university domain @${domain}.` }]
      });
    }

    const existing = await Alumni.findByEmail(req.body.email);
    if (existing) {
      return res.status(422).render('auth/register', {
        title: 'Alumni Registration',
        formData: req.body,
        errors: [{ msg: 'An account already exists with this email.' }]
      });
    }

    const passwordHash = await bcrypt.hash(req.body.password, 12);
    const verificationToken = randomToken();
    await Alumni.create({
      ...req.body,
      password_hash: passwordHash,
      verification_token: verificationToken
    });

    await sendVerificationEmail(req.body.email, verificationToken);
    req.flash('success', 'Registration successful. Check your email or terminal for the verification link.');
    res.redirect('/auth/login');
  } catch (error) {
    next(error);
  }
}

function renderLogin(req, res) {
  res.render('auth/login', { title: 'Login' });
}

async function login(req, res, next) {
  try {
    const { email, password } = req.body;

    console.log('LOGIN ATTEMPT:', email);

    const user = await Alumni.findByEmail(email);

    if (!user) {
      console.log('LOGIN FAILED: User not found');
      req.flash('error', 'Invalid email or password.');
      return res.redirect('/auth/login');
    }

    const passwordMatch = await bcrypt.compare(password, user.password_hash);

    console.log('USER FOUND:', user.email);
    console.log('PASSWORD MATCH:', passwordMatch);
    console.log('EMAIL VERIFIED:', user.email_verified);

    if (!passwordMatch) {
      console.log('LOGIN FAILED: Password does not match');
      req.flash('error', 'Invalid email or password.');
      return res.redirect('/auth/login');
    }

    req.session.alumniId = user.id;

    req.session.save((error) => {
      if (error) {
        console.log('SESSION SAVE ERROR:', error);
        return next(error);
      }

      console.log('LOGIN SUCCESS. Redirecting to /profile');
      req.flash('success', 'Logged in successfully.');
      return res.redirect('/profile');
    });
  } catch (error) {
    next(error);
  }
}

function logout(req, res, next) {
  req.session.destroy((error) => {
    if (error) return next(error);
    res.redirect('/auth/login');
  });
}

async function verifyEmail(req, res, next) {
  try {
    const { token } = req.query;
    const user = await Alumni.findByVerificationToken(token);
    if (!user) {
      req.flash('error', 'Invalid or expired verification link.');
      return res.redirect('/auth/login');
    }
    await Alumni.verifyEmail(user.id);
    req.flash('success', 'Email verified successfully. You can now use all profile and bidding features.');
    res.redirect('/auth/login');
  } catch (error) {
    next(error);
  }
}

function renderForgot(req, res) {
  res.render('auth/forgot', { title: 'Forgot Password' });
}

async function forgotPassword(req, res, next) {
  try {
    const { email } = req.body;
    const token = randomToken();
    const updated = await Alumni.setResetToken(email, token);
    if (updated) {
      await sendPasswordResetEmail(email, token);
    }
    req.flash('success', 'If that email exists, a password reset link has been sent or printed in the terminal.');
    res.redirect('/auth/login');
  } catch (error) {
    next(error);
  }
}

function renderReset(req, res) {
  res.render('auth/reset', { title: 'Reset Password', token: req.params.token });
}

async function resetPassword(req, res, next) {
  try {
    const user = await Alumni.findByResetToken(req.params.token);
    if (!user) {
      req.flash('error', 'Invalid or expired password reset link.');
      return res.redirect('/auth/forgot');
    }

    if (!req.body.password || req.body.password.length < 8 || req.body.password !== req.body.confirm_password) {
      req.flash('error', 'Password must be at least 8 characters and match the confirmation.');
      return res.redirect(`/auth/reset/${req.params.token}`);
    }

    const hash = await bcrypt.hash(req.body.password, 12);
    await Alumni.updatePassword(user.id, hash);
    req.flash('success', 'Password updated successfully. Please log in.');
    res.redirect('/auth/login');
  } catch (error) {
    next(error);
  }
}

module.exports = {
  renderRegister,
  register,
  renderLogin,
  login,
  logout,
  verifyEmail,
  renderForgot,
  forgotPassword,
  renderReset,
  resetPassword
};
