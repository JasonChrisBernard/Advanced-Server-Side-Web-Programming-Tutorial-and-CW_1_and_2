const path = require('path');
const express = require('express');
const session = require('express-session');
const flash = require('connect-flash');
const helmet = require('helmet');
const methodOverride = require('method-override');
require('dotenv').config();

const authRoutes = require('./routes/authRoutes');
const profileRoutes = require('./routes/profileRoutes');
const bidRoutes = require('./routes/bidRoutes');
const dashboardRoutes = require('./routes/dashboardRoutes');
const apiRoutes = require('./routes/apiRoutes');
const developerRoutes = require('./routes/developerRoutes');
const { attachUser } = require('./middleware/authMiddleware');

const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, '../views'));

app.use(
  helmet({
    contentSecurityPolicy: false
  })
);
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(methodOverride('_method'));
app.use('/uploads', express.static(path.join(__dirname, '../public/uploads')));
app.use('/css', express.static(path.join(__dirname, '../public/css')));
app.use('/chartjs', express.static(path.join(__dirname, '../node_modules/chart.js/dist')));

app.use(
  session({
    secret: process.env.SESSION_SECRET || 'development_secret_change_this',
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      sameSite: 'lax',
      secure: false,
      maxAge: 1000 * 60 * 60 * 2
    }
  })
);
app.use(flash());
app.use((req, res, next) => {
  res.locals.successMessages = req.flash('success');
  res.locals.errorMessages = req.flash('error');
  next();
});
app.use(attachUser);

app.get('/', (req, res) => {
  if (req.user) return res.redirect('/profile');
  res.redirect('/auth/login');
});

app.use('/auth', authRoutes);
app.use('/profile', profileRoutes);
app.use('/bids', bidRoutes);
app.use('/dashboard', dashboardRoutes);
app.use('/api', apiRoutes);
app.use('/developer', developerRoutes);

app.use((req, res) => {
  res.status(404).render('errors/404', { title: 'Page Not Found' });
});

app.use((error, req, res, next) => {
  console.error(error);
  const status = error.statusCode || 500;
  if (req.path.startsWith('/api')) {
    return res.status(status).json({ success: false, message: error.message || 'Server error' });
  }
  res.status(status).render('errors/500', { title: 'Server Error', error });
});

module.exports = app;
