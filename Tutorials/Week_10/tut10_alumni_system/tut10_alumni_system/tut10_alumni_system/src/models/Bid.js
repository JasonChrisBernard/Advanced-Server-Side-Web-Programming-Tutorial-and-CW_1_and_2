const pool = require('../config/db');

const { toISODateInTimeZone } = require('../utils/date');

function todayISO() {
  return toISODateInTimeZone(new Date(), 'Asia/Colombo');
}

function monthKeyFromDate(dateString) {
  return dateString.slice(0, 7);
}

async function findTodayBid(alumniId, bidDate = todayISO()) {
  const [rows] = await pool.execute(
    'SELECT * FROM bids WHERE alumni_id = ? AND bid_date = ?',
    [alumniId, bidDate]
  );
  return rows[0];
}

async function countMonthlyBidDays(alumniId, monthKey) {
  const [rows] = await pool.execute(
    'SELECT COUNT(*) AS total FROM bids WHERE alumni_id = ? AND month_key = ?',
    [alumniId, monthKey]
  );
  return rows[0].total;
}

async function placeOrIncreaseBid(alumniId, amount, bidDate = todayISO()) {
  const monthKey = monthKeyFromDate(bidDate);
  const existing = await findTodayBid(alumniId, bidDate);

  if (existing) {
    if (Number(amount) <= Number(existing.bid_amount)) {
      const error = new Error('New bid must be higher than your current bid for today.');
      error.statusCode = 400;
      throw error;
    }
    await pool.execute(
      'UPDATE bids SET bid_amount = ?, status = "active" WHERE id = ?',
      [amount, existing.id]
    );
    return { mode: 'updated', bidId: existing.id };
  }

  const monthlyCount = await countMonthlyBidDays(alumniId, monthKey);
  if (monthlyCount >= 3) {
    const error = new Error('Monthly bidding limit reached. Alumni can bid on a maximum of 3 days per month.');
    error.statusCode = 400;
    throw error;
  }

  const [result] = await pool.execute(
    'INSERT INTO bids (alumni_id, bid_amount, bid_date, month_key) VALUES (?, ?, ?, ?)',
    [alumniId, amount, bidDate, monthKey]
  );
  return { mode: 'created', bidId: result.insertId };
}

async function getBidSummary(alumniId, dateString = todayISO()) {
  const monthKey = monthKeyFromDate(dateString);
  const todayBid = await findTodayBid(alumniId, dateString);
  const monthlyCount = await countMonthlyBidDays(alumniId, monthKey);
  const [history] = await pool.execute(
    `SELECT b.*, aod.feature_date
     FROM bids b
     LEFT JOIN alumni_of_day aod ON aod.winning_bid_id = b.id
     WHERE b.alumni_id = ?
     ORDER BY b.bid_date DESC, b.updated_at DESC
     LIMIT 20`,
    [alumniId]
  );
  const [todayWinner] = await pool.execute(
    `SELECT aod.*, a.first_name, a.last_name, a.program, a.current_job_title, a.company, a.profile_image
     FROM alumni_of_day aod
     JOIN alumni a ON a.id = aod.alumni_id
     WHERE aod.feature_date = ?`,
    [dateString]
  );
  return { todayBid, monthlyCount, remainingMonthlyBids: Math.max(0, 3 - monthlyCount), history, todayWinner: todayWinner[0] };
}

async function getHighestBidForDate(bidDate) {
  const [rows] = await pool.execute(
    `SELECT b.*, a.first_name, a.last_name
     FROM bids b
     JOIN alumni a ON a.id = b.alumni_id
     WHERE b.bid_date = ? AND b.status = 'active'
     ORDER BY b.bid_amount DESC, b.updated_at ASC
     LIMIT 1`,
    [bidDate]
  );
  return rows[0];
}

async function markWinner(winningBidId, bidDate, featureDate) {
  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();
    const [winnerRows] = await connection.execute('SELECT * FROM bids WHERE id = ? FOR UPDATE', [winningBidId]);
    const winner = winnerRows[0];
    if (!winner) throw new Error('Winning bid was not found.');

    await connection.execute('UPDATE bids SET status = "lost" WHERE bid_date = ? AND id <> ?', [bidDate, winningBidId]);
    await connection.execute('UPDATE bids SET status = "won" WHERE id = ?', [winningBidId]);
    await connection.execute(
      `INSERT INTO alumni_of_day (alumni_id, winning_bid_id, feature_date, winning_amount)
       VALUES (?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE
         alumni_id = VALUES(alumni_id),
         winning_bid_id = VALUES(winning_bid_id),
         winning_amount = VALUES(winning_amount),
         selected_at = CURRENT_TIMESTAMP`,
      [winner.alumni_id, winningBidId, featureDate, winner.bid_amount]
    );
    await connection.commit();
    return winner;
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

async function listAllBids(limit = 100) {
  const [rows] = await pool.execute(
    `SELECT b.id, b.bid_amount, b.bid_date, b.month_key, b.status, b.created_at, b.updated_at,
            a.first_name, a.last_name, a.email, a.program, a.industry_sector
     FROM bids b
     JOIN alumni a ON a.id = b.alumni_id
     ORDER BY b.bid_date DESC, b.updated_at DESC
     LIMIT ?`,
    [Number(limit)]
  );
  return rows;
}

module.exports = {
  todayISO,
  monthKeyFromDate,
  findTodayBid,
  countMonthlyBidDays,
  placeOrIncreaseBid,
  getBidSummary,
  getHighestBidForDate,
  markWinner,
  listAllBids
};
