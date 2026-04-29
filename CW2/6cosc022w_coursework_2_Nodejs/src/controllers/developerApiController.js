// Import crypto so raw bearer tokens can be generated securely.
const crypto = require("crypto");
// Import the API client model that stores external clients, tokens, and usage logs.
const ApiClient = require("../models/apiClientModel");
// Import CW2 staff analytics queries exposed through scoped API tokens.
const StaffAnalytics = require("../models/staffAnalyticsModel");
// Import the featured alumnus service used by both the web portal and the JSON API.
const {
  getTodayFeaturedAlumnus,
  getTomorrowLeadingFeaturedAlumnus,
} = require("../services/featuredAlumnusService");
// Import the OpenAPI builder used for the Swagger-style documentation page and JSON spec.
const buildDeveloperApiSpec = require("../docs/developerApiSpec");
// Import text sanitizers so developer-client records stay clean.
const { sanitizeMultilineText, sanitizeSingleLineText } = require("../utils/validators");

// Define the client types supported by this coursework feature.
const CLIENT_TYPE_OPTIONS = [
  {
    value: "ANALYTICS_DASHBOARD",
    label: "University Analytics Dashboard",
    scopeList: ["read:alumni", "read:analytics"],
    cannotAccess: "Mobile AR app endpoints such as /api/v1/alumni-of-day.",
    accessPattern: "Use the bearer token only from the staff dashboard server running on localhost:3001.",
  },
  {
    value: "MOBILE_AR_APP",
    label: "Mobile AR App",
    scopeList: ["read:alumni_of_day"],
    cannotAccess: "Analytics endpoints such as /api/v1/analytics/summary and /api/v1/alumni.",
    accessPattern: "Store the bearer token in a trusted mobile backend and only request the alumnus-of-day feed.",
  },
  {
    value: "WEB_WIDGET",
    label: "Website Widget",
    scopeList: ["read:alumni_of_day"],
    cannotAccess: "Staff analytics endpoints.",
    accessPattern: "Use the bearer token from a trusted server-side widget or edge function.",
  },
];

// Define all API scopes exposed by the coursework API.
const API_SCOPE_DESCRIPTIONS = [
  {
    scope: "read:alumni",
    description: "Read alumni directory records with programme, graduation, and industry filters.",
  },
  {
    scope: "read:analytics",
    description: "Read aggregate analytics for staff dashboards and graph pages.",
  },
  {
    scope: "read:alumni_of_day",
    description: "Read only the selected alumnus-of-day feed for AR/mobile experiences.",
  },
];
// Use the analytics dashboard scope pair as the default helper text in the portal.
const DEFAULT_SCOPE_LIST = CLIENT_TYPE_OPTIONS[0].scopeList;
// Default newly issued bearer tokens to a finite lifetime so they do not remain valid forever.
const API_TOKEN_TTL_DAYS = Number(process.env.API_TOKEN_TTL_DAYS || 30);

// Read and clear the one-time flash data stored for the developer portal.
function consumePortalFlash(req) {
  const flash = req.session.developerPortalFlash || {
    error: null,
    success: null,
    generatedToken: null,
    generatedTokenExpiresAt: null,
  };

  delete req.session.developerPortalFlash;
  return flash;
}

// Save one-time portal feedback in the session so it survives a redirect.
function setPortalFlash(req, flash) {
  req.session.developerPortalFlash = flash;
}

// Convert a client type code into its display metadata.
function getClientTypeOption(clientType) {
  return CLIENT_TYPE_OPTIONS.find((option) => option.value === clientType) || null;
}

// Hash the raw token before storing it so the database never keeps a usable secret.
function hashToken(rawToken) {
  return crypto.createHash("sha256").update(rawToken).digest("hex");
}

// Build a predictable expiry timestamp for a newly issued developer token.
function buildTokenExpiryTimestamp(baseDate = new Date()) {
  const expiresAt = new Date(baseDate);
  expiresAt.setDate(expiresAt.getDate() + API_TOKEN_TTL_DAYS);
  return expiresAt.toISOString();
}

// Build one-time token text that the user can copy into an external client.
function createRawToken() {
  return `alumni_live_${crypto.randomBytes(24).toString("hex")}`;
}

// Build the dynamic Swagger/OpenAPI document using the current request host.
function buildSpecFromRequest(req) {
  const baseUrl = `${req.protocol}://${req.get("host")}`;
  return buildDeveloperApiSpec(baseUrl);
}

// Build the full view model used by the developer portal page.
async function buildPortalViewModel(
  req,
  flash = { error: null, success: null, generatedToken: null, generatedTokenExpiresAt: null }
) {
  ApiClient.synchronizeTokenExpiryStates();
  const clients = ApiClient.getClientsWithTokensByUserId(req.session.user.id);
  const usageSummary = ApiClient.getUsageSummaryByUserId(req.session.user.id);
  const recentUsageLogs = ApiClient.getRecentUsageLogsByUserId(req.session.user.id, 25);
  const todayFeatured = await getTodayFeaturedAlumnus();
  const tomorrowLeadingFeatured = await getTomorrowLeadingFeaturedAlumnus();

  return {
    title: "Developer Portal",
    error: flash.error || null,
    success: flash.success || null,
    generatedToken: flash.generatedToken || null,
    generatedTokenExpiresAt: flash.generatedTokenExpiresAt || null,
    apiTokenTtlDays: API_TOKEN_TTL_DAYS,
    clientTypeOptions: CLIENT_TYPE_OPTIONS,
    defaultScopeList: DEFAULT_SCOPE_LIST,
    apiScopeDescriptions: API_SCOPE_DESCRIPTIONS,
    clients,
    usageSummary,
    recentUsageLogs,
    todayFeatured,
    tomorrowLeadingFeatured,
    docsUrl: "/developer-api/docs",
    swaggerJsonUrl: "/developer-api/swagger.json",
    featuredEndpointUrl: "/api/v1/alumni-of-day",
  };
}

// Render the developer portal used to create, inspect, and revoke bearer tokens.
exports.showDeveloperPortal = async (req, res) => {
  try {
    const flash = consumePortalFlash(req);
    const viewModel = await buildPortalViewModel(req, flash);
    return res.render("developer/portal", viewModel);
  } catch (error) {
    console.error(error);
    return res.render("developer/portal", {
      title: "Developer Portal",
      error: "Something went wrong while loading the developer portal.",
      success: null,
      generatedToken: null,
      generatedTokenExpiresAt: null,
      apiTokenTtlDays: API_TOKEN_TTL_DAYS,
      clientTypeOptions: CLIENT_TYPE_OPTIONS,
      defaultScopeList: DEFAULT_SCOPE_LIST,
      apiScopeDescriptions: API_SCOPE_DESCRIPTIONS,
      clients: [],
      usageSummary: {
        totalRequests: 0,
        successfulRequests: 0,
        failedRequests: 0,
        lastActivityAt: null,
        activeTokens: 0,
        revokedTokens: 0,
        expiredTokens: 0,
      },
      recentUsageLogs: [],
      todayFeatured: null,
      tomorrowLeadingFeatured: null,
      docsUrl: "/developer-api/docs",
      swaggerJsonUrl: "/developer-api/swagger.json",
      featuredEndpointUrl: "/api/v1/alumni-of-day",
    });
  }
};

// Create a new external client and its first bearer token.
exports.createDeveloperClient = async (req, res) => {
  try {
    const clientName = sanitizeSingleLineText(req.body.clientName, 120);
    const clientType = String(req.body.clientType || "").trim();
    const description = sanitizeMultilineText(req.body.description, 600);
    const selectedClientType = getClientTypeOption(clientType);

    if (!clientName) {
      const viewModel = await buildPortalViewModel(req, {
        error: "Client name is required.",
        success: null,
        generatedToken: null,
        generatedTokenExpiresAt: null,
      });
      return res.render("developer/portal", viewModel);
    }

    if (!selectedClientType) {
      const viewModel = await buildPortalViewModel(req, {
        error: "Select a valid client type.",
        success: null,
        generatedToken: null,
        generatedTokenExpiresAt: null,
      });
      return res.render("developer/portal", viewModel);
    }

    const rawToken = createRawToken();
    const tokenPrefix = rawToken.slice(0, 18);
    const expiresAt = buildTokenExpiryTimestamp();

    ApiClient.createClientWithToken({
      userId: req.session.user.id,
      clientName,
      clientType,
      accessStrategy: "BEARER_TOKEN",
      description,
      tokenLabel: `${clientName} ${selectedClientType.label} Token`,
      tokenPrefix,
      tokenHash: hashToken(rawToken),
      scopeList: selectedClientType.scopeList,
      expiresAt,
    });

    setPortalFlash(req, {
      error: null,
      success: `Created the ${clientName} client and issued a bearer token that expires on ${new Date(expiresAt).toLocaleString()}.`,
      generatedToken: rawToken,
      generatedTokenExpiresAt: expiresAt,
    });

    return res.redirect("/developer-portal");
  } catch (error) {
    console.error(error);
    const viewModel = await buildPortalViewModel(req, {
      error: "Something went wrong while creating the developer client.",
      success: null,
      generatedToken: null,
      generatedTokenExpiresAt: null,
    });
    return res.render("developer/portal", viewModel);
  }
};

// Revoke one owned bearer token so it can no longer call the developer API.
exports.revokeDeveloperToken = async (req, res) => {
  try {
    const tokenId = Number(req.params.tokenId);
    const ownedToken = ApiClient.findOwnedTokenById(req.session.user.id, tokenId);

    if (!ownedToken) {
      setPortalFlash(req, {
        error: "That token was not found for your account.",
        success: null,
        generatedToken: null,
        generatedTokenExpiresAt: null,
      });
      return res.redirect("/developer-portal");
    }

    if (ownedToken.status === "REVOKED") {
      setPortalFlash(req, {
        error: "That token has already been revoked.",
        success: null,
        generatedToken: null,
        generatedTokenExpiresAt: null,
      });
      return res.redirect("/developer-portal");
    }

    if (ownedToken.status === "EXPIRED") {
      setPortalFlash(req, {
        error: "That token has already expired.",
        success: null,
        generatedToken: null,
        generatedTokenExpiresAt: null,
      });
      return res.redirect("/developer-portal");
    }

    ApiClient.revokeTokenById(req.session.user.id, tokenId);

    // Record the revocation event so the usage log also reflects token lifecycle actions.
    ApiClient.recordUsageLog({
      tokenId: ownedToken.id,
      clientId: ownedToken.client_id,
      requestMethod: "POST",
      endpointPath: `/developer-portal/tokens/${tokenId}/revoke`,
      usedAt: new Date().toISOString(),
      outcome: "TOKEN_REVOKED",
      httpStatus: 200,
      ipAddress: req.ip,
      userAgent: req.get("user-agent") || "",
      details: `Token revoked from web portal for client ${ownedToken.client_name}.`,
    });

    setPortalFlash(req, {
      error: null,
      success: `Revoked the token for ${ownedToken.client_name}.`,
      generatedToken: null,
      generatedTokenExpiresAt: null,
    });

    return res.redirect("/developer-portal");
  } catch (error) {
    console.error(error);
    setPortalFlash(req, {
      error: "Something went wrong while revoking the token.",
      success: null,
      generatedToken: null,
      generatedTokenExpiresAt: null,
    });
    return res.redirect("/developer-portal");
  }
};

// Return the public JSON API response for today's featured alumnus.
exports.getTodayFeaturedAlumnusApi = async (req, res) => {
  try {
    const todayFeatured = await getTodayFeaturedAlumnus();

    if (!todayFeatured) {
      return res.status(404).json({
        success: false,
        error: "No featured alumnus is available yet because today's featured day has not been resolved.",
      });
    }

    return res.json({
      success: true,
      data: todayFeatured,
    });
  } catch (error) {
    console.error(error);
    return res.status(500).json({
      success: false,
      error: "Something went wrong while reading today's featured alumnus.",
    });
  }
};

// Return the mobile/AR alumnus-of-day feed using its own dedicated scope.
exports.getAlumnusOfDayApi = exports.getTodayFeaturedAlumnusApi;

// Return the scoped alumni directory API for analytics-dashboard clients.
exports.getAlumniDirectoryApi = (req, res) => {
  try {
    const filters = {
      programme: sanitizeSingleLineText(req.query.programme, 140),
      graduationYear: sanitizeSingleLineText(req.query.graduationYear, 4),
      graduationDate: sanitizeSingleLineText(req.query.graduationDate, 10),
      industrySector: sanitizeSingleLineText(req.query.industrySector, 120),
    };
    const alumni = StaffAnalytics.getAlumniDirectory(filters, 200);
    const totalMatches = StaffAnalytics.countAlumniDirectory(filters);

    return res.json({
      success: true,
      filters,
      totalMatches,
      data: alumni.map((profile) => ({
        userId: profile.user_id,
        fullName: profile.full_name,
        email: profile.email,
        isVerified: Boolean(profile.is_verified),
        contactNumber: profile.contact_number || "",
        location: profile.location || "",
        programme: profile.programme || "",
        graduationDate: profile.graduation_date || "",
        industrySector: profile.industry_sector || "",
        professionalHeadline: profile.professional_headline || "",
        linkedinUrl: profile.linkedin_url || "",
        updatedAt: profile.updated_at,
      })),
    });
  } catch (error) {
    console.error(error);
    return res.status(500).json({
      success: false,
      error: "Something went wrong while reading alumni directory data.",
    });
  }
};

// Return aggregate analytics for the CW2 dashboard and graph pages.
exports.getAnalyticsSummaryApi = (req, res) => {
  try {
    return res.json({
      success: true,
      data: {
        summary: StaffAnalytics.getDashboardSummary(),
        graphs: StaffAnalytics.getGraphData(),
        recentApiUsageLogs: StaffAnalytics.getRecentApiUsageLogs(25),
      },
    });
  } catch (error) {
    console.error(error);
    return res.status(500).json({
      success: false,
      error: "Something went wrong while reading analytics summary data.",
    });
  }
};

// Return the OpenAPI/Swagger JSON document for developer tooling.
exports.showSwaggerJson = (req, res) => {
  return res.json(buildSpecFromRequest(req));
};

// Render a human-friendly documentation page using the same OpenAPI information.
exports.showDeveloperDocs = (req, res) => {
  const spec = buildSpecFromRequest(req);
  const todayEndpoint = `${req.protocol}://${req.get("host")}/api/v1/alumni-of-day`;

  return res.render("developer/docs", {
    title: "Developer API Documentation",
    spec,
    swaggerJson: JSON.stringify(spec, null, 2),
    todayEndpoint,
    apiTokenTtlDays: API_TOKEN_TTL_DAYS,
  });
};

// Render an interactive Swagger UI page that loads the OpenAPI JSON from the local app.
exports.showSwaggerUi = (req, res) => {
  return res.render("developer/api-docs", {
    title: "Interactive API Docs",
    swaggerJsonUrl: "/developer-api/swagger.json",
  });
};
