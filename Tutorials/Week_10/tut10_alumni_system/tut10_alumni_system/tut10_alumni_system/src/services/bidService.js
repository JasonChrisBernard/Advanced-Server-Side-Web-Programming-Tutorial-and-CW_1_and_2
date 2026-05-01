const Bid = require('../models/Bid');
const { toISODateInTimeZone, addDaysToISODate } = require('../utils/date');

async function selectWinnerForBidDate(bidDate, featureDate) {
  const highestBid = await Bid.getHighestBidForDate(bidDate);

  if (!highestBid) {
    return { selected: false, message: `No active bids found for ${bidDate}.` };
  }

  const winner = await Bid.markWinner(highestBid.id, bidDate, featureDate);
  return {
    selected: true,
    featureDate,
    bidDate,
    alumniId: winner.alumni_id,
    winningBidId: winner.id,
    winningAmount: winner.bid_amount
  };
}

async function selectMidnightWinner(referenceDate = new Date()) {
  const featureDate = toISODateInTimeZone(referenceDate, 'Asia/Colombo');
  const bidDate = addDaysToISODate(featureDate, -1);
  return selectWinnerForBidDate(bidDate, featureDate);
}

async function selectTodayWinnerForDemo() {
  const today = toISODateInTimeZone(new Date(), 'Asia/Colombo');
  return selectWinnerForBidDate(today, today);
}

module.exports = { selectMidnightWinner, selectTodayWinnerForDemo, selectWinnerForBidDate };
