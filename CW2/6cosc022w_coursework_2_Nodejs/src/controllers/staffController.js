// Import the staff analytics model that powers the CW2 dashboard and graph pages.
const StaffAnalytics = require("../models/staffAnalyticsModel");
// Import featured alumnus helpers so staff can see the selected/leading profiles.
const {
  getTodayFeaturedAlumnus,
  getTomorrowLeadingFeaturedAlumnus,
} = require("../services/featuredAlumnusService");
// Import text sanitizer for filter query parameters.
const { sanitizeSingleLineText } = require("../utils/validators");

// Convert staff directory query parameters into safe fixed filters.
function buildDirectoryFilters(query) {
  return {
    programme: sanitizeSingleLineText(query.programme, 140),
    graduationYear: sanitizeSingleLineText(query.graduationYear, 4),
    graduationDate: sanitizeSingleLineText(query.graduationDate, 10),
    industrySector: sanitizeSingleLineText(query.industrySector, 120),
  };
}

// Render the main CW2 staff analytics dashboard.
exports.showDashboard = async (req, res) => {
  const summary = StaffAnalytics.getDashboardSummary();
  const graphData = StaffAnalytics.getGraphData();
  const recentApiUsageLogs = StaffAnalytics.getRecentApiUsageLogs(10);
  const todayFeatured = await getTodayFeaturedAlumnus();
  const tomorrowLeadingFeatured = await getTomorrowLeadingFeaturedAlumnus();

  return res.render("staff/dashboard", {
    title: "CW2 Staff Analytics Dashboard",
    summary,
    graphData,
    recentApiUsageLogs,
    todayFeatured,
    tomorrowLeadingFeatured,
  });
};

// Render the dedicated graphs page for programme, graduation year, industry, and API usage.
exports.showGraphs = (req, res) => {
  const summary = StaffAnalytics.getDashboardSummary();
  const graphData = StaffAnalytics.getGraphData();

  return res.render("staff/graphs", {
    title: "CW2 Analytics Graphs",
    summary,
    graphData,
  });
};

// Render the searchable alumni directory required by the CW2 staff dashboard.
exports.showAlumniDirectory = (req, res) => {
  const filters = buildDirectoryFilters(req.query);
  const filterOptions = StaffAnalytics.getAlumniFilterOptions();
  const alumni = StaffAnalytics.getAlumniDirectory(filters, 200);
  const totalMatches = StaffAnalytics.countAlumniDirectory(filters);

  return res.render("staff/alumni", {
    title: "View Records",
    filters,
    filterOptions,
    alumni,
    totalMatches,
  });
};
