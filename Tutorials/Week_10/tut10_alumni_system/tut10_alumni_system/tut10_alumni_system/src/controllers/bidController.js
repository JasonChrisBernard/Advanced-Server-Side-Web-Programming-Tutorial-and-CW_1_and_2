const Bid = require('../models/Bid');
const { selectTodayWinnerForDemo } = require('../services/bidService');

async function showBiddingPage(req, res, next) {
  try {
    const summary = await Bid.getBidSummary(req.user.id);
    res.render('bids/index', {
      title: 'Blind Bidding',
      summary
    });
  } catch (error) {
    next(error);
  }
}

async function placeBid(req, res, next) {
  try {
    const amount = Number(req.body.bid_amount);
    if (!amount || amount <= 0) {
      req.flash('error', 'Please enter a valid bid amount.');
      return res.redirect('/bids');
    }

    const result = await Bid.placeOrIncreaseBid(req.user.id, amount);
    req.flash(
      'success',
      result.mode === 'updated'
        ? 'Your blind bid was increased successfully.'
        : 'Your blind bid was placed successfully.'
    );
    res.redirect('/bids');
  } catch (error) {
    req.flash('error', error.message || 'Unable to place bid.');
    res.redirect('/bids');
  }
}

async function runManualSelection(req, res, next) {
  try {
    const result = await selectTodayWinnerForDemo();
    req.flash('success', result.selected ? 'Winner selection completed.' : result.message);
    res.redirect('/bids');
  } catch (error) {
    next(error);
  }
}

module.exports = { showBiddingPage, placeBid, runManualSelection };
