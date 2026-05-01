const Alumni = require('../models/Alumni');
const Analytics = require('../models/Analytics');
const Bid = require('../models/Bid');

async function analytics(req, res, next) {
  try {
    const data = await Analytics.getDashboardData(req.query);
    res.json({ success: true, data });
  } catch (error) {
    next(error);
  }
}

async function profiles(req, res, next) {
  try {
    const profiles = await Alumni.listProfiles(req.query);
    res.json({ success: true, data: profiles });
  } catch (error) {
    next(error);
  }
}

async function bids(req, res, next) {
  try {
    const bidsData = await Bid.listAllBids(100);
    res.json({ success: true, data: bidsData });
  } catch (error) {
    next(error);
  }
}

module.exports = { analytics, profiles, bids };
