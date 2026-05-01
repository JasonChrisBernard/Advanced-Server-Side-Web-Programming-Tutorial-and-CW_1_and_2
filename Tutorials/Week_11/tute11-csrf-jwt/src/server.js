require("dotenv").config();

const express = require("express");
const session = require("express-session");
const path = require("path");
const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");

const {
  attachCsrfToken,
  validateCsrfToken
} = require("./middleware/csrf");

const app = express();

const PORT = process.env.PORT || 3000;
const ENABLE_CSRF = process.env.ENABLE_CSRF === "true";

// temporary in-memory users array
const users = [];

// default user for testing
users.push({
  id: 1,
  name: "Jason",
  email: "jason@test.com",
  password: bcrypt.hashSync("Password123", 10)
});

app.set("view engine", "ejs");
app.set("views", path.join(__dirname, "views"));

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static(path.join(__dirname, "../public")));

app.use(
  session({
    secret: process.env.SESSION_SECRET || "fallback_secret",
    resave: false,
    saveUninitialized: false,
    cookie: {
    httpOnly: true,
    sameSite: false
    }
  })
);

app.use((req, res, next) => {
  res.locals.currentUser = req.session.user || null;
  res.locals.csrfEnabled = ENABLE_CSRF;
  next();
});

if (ENABLE_CSRF) {
  app.use(attachCsrfToken);
  app.use(validateCsrfToken);
}

function requireLogin(req, res, next) {
  if (!req.session.user) {
    return res.redirect("/login");
  }
  next();
}

// Home
app.get("/", (req, res) => {
  res.redirect("/profile");
});

// Register page
app.get("/register", (req, res) => {
  res.render("register", { error: null });
});

// Register action
app.post("/register", async (req, res) => {
  const { name, email, password } = req.body;

  const existingUser = users.find((u) => u.email === email);

  if (existingUser) {
    return res.render("register", {
      error: "Email already registered."
    });
  }

  const hashedPassword = await bcrypt.hash(password, 10);

  const user = {
    id: users.length + 1,
    name,
    email,
    password: hashedPassword
  };

  users.push(user);

  res.redirect("/login");
});

// Login page
app.get("/login", (req, res) => {
  res.render("login", { error: null });
});

// Login action
app.post("/login", async (req, res) => {
  const { email, password } = req.body;

  const user = users.find((u) => u.email === email);

  if (!user) {
    return res.render("login", {
      error: "Invalid email or password."
    });
  }

  const isMatch = await bcrypt.compare(password, user.password);

  if (!isMatch) {
    return res.render("login", {
      error: "Invalid email or password."
    });
  }

  req.session.user = {
    id: user.id,
    name: user.name,
    email: user.email
  };

  res.redirect("/profile");
});

// Profile page
app.get("/profile", requireLogin, (req, res) => {
  res.render("profile", {
    message: null
  });
});

// Vulnerable state-changing action
app.post("/change-email", requireLogin, (req, res) => {
  const { email } = req.body;

  const user = users.find((u) => u.id === req.session.user.id);

  if (!user) {
    return res.redirect("/login");
  }

  user.email = email;
  req.session.user.email = email;

  res.render("profile", {
    message: "Email changed successfully."
  });
});

// Logout
app.post("/logout", requireLogin, (req, res) => {
  req.session.destroy(() => {
    res.redirect("/login");
  });
});

// JWT login route for Task 2
app.post("/api/login", async (req, res) => {
  const { email, password } = req.body;

  const user = users.find((u) => u.email === email);

  if (!user) {
    return res.status(401).json({ message: "Invalid credentials" });
  }

  const isMatch = await bcrypt.compare(password, user.password);

  if (!isMatch) {
    return res.status(401).json({ message: "Invalid credentials" });
  }

  const token = jwt.sign(
    {
      id: user.id,
      email: user.email
    },
    process.env.JWT_SECRET,
    {
      expiresIn: "1h"
    }
  );

  res.json({
    message: "JWT created successfully",
    token
  });
});

// JWT middleware
function verifyJwt(req, res, next) {
  const authHeader = req.headers.authorization;

  if (!authHeader) {
    return res.status(403).json({ message: "Token missing" });
  }

  const token = authHeader.split(" ")[1];

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    req.jwtUser = decoded;
    next();
  } catch (error) {
    return res.status(403).json({ message: "Invalid token" });
  }
}

// JWT protected route
app.get("/api/profile", verifyJwt, (req, res) => {
  res.json({
    message: "This is protected JWT profile data",
    user: req.jwtUser
  });
});

app.get("/api/public", (req, res) => {
  res.json({
    message: "This is public API data"
  });
});

app.get("/csrf-status", (req, res) => {
  res.send(`CSRF enabled: ${ENABLE_CSRF}`);
});

app.use((req, res) => {
  res.status(404).render("error", {
    message: "Page not found."
  });
});

app.listen(PORT, () => {
  console.log(`Server running at http://localhost:${PORT}`);
  console.log(`CSRF enabled: ${ENABLE_CSRF}`);
});