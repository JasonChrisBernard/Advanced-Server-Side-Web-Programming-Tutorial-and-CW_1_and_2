const express = require('express');
const { body } = require('express-validator');
const authController = require('../controllers/authController');

const router = express.Router();

router.get('/register', authController.renderRegister);
router.post(
  '/register',
  [
    body('first_name').trim().notEmpty().withMessage('First name is required.'),
    body('last_name').trim().notEmpty().withMessage('Last name is required.'),
    body('email').trim().isEmail().withMessage('A valid university email is required.'),
    body('password').isLength({ min: 8 }).withMessage('Password must be at least 8 characters.'),
    body('confirm_password').custom((value, { req }) => value === req.body.password).withMessage('Passwords must match.')
  ],
  authController.register
);

router.get('/login', authController.renderLogin);
router.post('/login', authController.login);
router.post('/logout', authController.logout);
router.get('/verify', authController.verifyEmail);
router.get('/forgot', authController.renderForgot);
router.post('/forgot', authController.forgotPassword);
router.get('/reset/:token', authController.renderReset);
router.post('/reset/:token', authController.resetPassword);

module.exports = router;
