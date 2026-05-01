const Alumni = require('../models/Alumni');
const Analytics = require('../models/Analytics');

async function showDashboard(req, res, next) {
  try {
    const filters = {
      program: req.query.program || '',
      graduation_year: req.query.graduation_year || '',
      industry_sector: req.query.industry_sector || ''
    };
    const options = await Alumni.getFilterOptions();
    const data = await Analytics.getDashboardData(filters);
    res.render('dashboard/index', {
      title: 'University Analytics Dashboard',
      filters,
      options,
      data
    });
  } catch (error) {
    next(error);
  }
}

module.exports = { showDashboard };
