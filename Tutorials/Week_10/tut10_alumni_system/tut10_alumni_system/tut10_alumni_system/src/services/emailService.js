const { createTransporter, smtpConfigured } = require('../config/mailer');
require('dotenv').config();

async function sendLinkEmail({ to, subject, text, html }) {
  if (!smtpConfigured()) {
    console.log('\n========== DEVELOPMENT EMAIL ==========' );
    console.log(`To: ${to}`);
    console.log(`Subject: ${subject}`);
    console.log(text);
    console.log('=======================================\n');
    return;
  }

  const transporter = createTransporter();
  await transporter.sendMail({
    from: process.env.SMTP_FROM || 'Alumni Platform <no-reply@university.ac.uk>',
    to,
    subject,
    text,
    html
  });
}

async function sendVerificationEmail(email, token) {
  const url = `${process.env.APP_BASE_URL || 'http://localhost:3000'}/auth/verify?token=${token}`;
  await sendLinkEmail({
    to: email,
    subject: 'Verify your alumni account',
    text: `Verify your alumni account using this link: ${url}`,
    html: `<p>Please verify your alumni account by opening this link:</p><p><a href="${url}">${url}</a></p>`
  });
}

async function sendPasswordResetEmail(email, token) {
  const url = `${process.env.APP_BASE_URL || 'http://localhost:3000'}/auth/reset/${token}`;
  await sendLinkEmail({
    to: email,
    subject: 'Reset your alumni platform password',
    text: `Reset your password using this link: ${url}`,
    html: `<p>Reset your password by opening this link:</p><p><a href="${url}">${url}</a></p>`
  });
}

module.exports = { sendVerificationEmail, sendPasswordResetEmail };
