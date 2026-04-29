// Import the shared SQLite connection so blind bidding can use the same database.
const db = require("../config/db");
// Import crypto so event attendance codes can be matched using hashes instead of raw values.
const crypto = require("crypto");

// Hash one attendance code before it is compared with the verified event catalog.
function hashAttendanceCode(rawCode) {
  return crypto.createHash("sha256").update(String(rawCode || "")).digest("hex");
}

// Work out the effective monthly appearance allowance for one user in one month.
function getMonthlyAppearanceAllowanceForUser(
  userId,
  monthKey,
  baseMaxMonthlyWins = 3,
  eventBonusWins = 1
) {
  const eventCredit = db
    .prepare(`
      SELECT c.id
      FROM alumni_event_credits c
      INNER JOIN university_alumni_events e
        ON e.id = c.event_id
      WHERE c.user_id = ? AND c.month_key = ?
        AND e.is_active = 1
      LIMIT 1
    `)
    .get(userId, monthKey);

  return baseMaxMonthlyWins + (eventCredit ? eventBonusWins : 0);
}

// Read one user's bid for one featured day.
exports.getBidByUserAndFeatureDate = (userId, featureDate) => {
  return db
    .prepare(`
      SELECT *
      FROM blind_bids
      WHERE user_id = ? AND feature_date = ? AND status <> 'CANCELLED'
    `)
    .get(userId, featureDate);
};

// Read one user's bid row for one featured day, including cancelled bids kept for history.
exports.getAnyBidByUserAndFeatureDate = (userId, featureDate) => {
  return db
    .prepare(`
      SELECT *
      FROM blind_bids
      WHERE user_id = ? AND feature_date = ?
    `)
    .get(userId, featureDate);
};

// Create a new blind bid for the requested featured day.
exports.createBid = ({ userId, profileId, featureDate, amount }) => {
  const now = new Date().toISOString();

  return db.prepare(`
    INSERT INTO blind_bids (
      user_id,
      profile_id,
      feature_date,
      amount,
      status,
      created_at,
      updated_at
    )
    VALUES (?, ?, ?, ?, 'PENDING', ?, ?)
  `).run(userId, profileId, featureDate, amount, now, now);
};

// Update an existing bid to a higher amount while keeping it pending.
exports.updateBidAmount = (bidId, amount) => {
  const now = new Date().toISOString();

  return db.prepare(`
    UPDATE blind_bids
    SET amount = ?,
        status = 'PENDING',
        updated_at = ?,
        resolved_at = NULL
    WHERE id = ?
  `).run(amount, now, bidId);
};

// Reactivate a cancelled bid row so the user can bid again for the same future slot.
exports.reactivateBid = (bidId, amount) => {
  const now = new Date().toISOString();

  return db.prepare(`
    UPDATE blind_bids
    SET amount = ?,
        status = 'PENDING',
        updated_at = ?,
        resolved_at = NULL,
        outcome_notification_sent_at = NULL
    WHERE id = ?
  `).run(amount, now, bidId);
};

// Cancel one still-pending bid so the user gives up tomorrow's slot.
exports.cancelBid = (bidId) => {
  const now = new Date().toISOString();

  return db.prepare(`
    UPDATE blind_bids
    SET status = 'CANCELLED',
        updated_at = ?,
        resolved_at = ?
    WHERE id = ?
      AND status = 'PENDING'
  `).run(now, now, bidId);
};

// Count how many blind bids currently exist for the given featured day.
exports.countBidsForDate = (featureDate) => {
  const result = db
    .prepare(`
      SELECT COUNT(*) AS total
      FROM blind_bids
      WHERE feature_date = ? AND status = 'PENDING'
    `)
    .get(featureDate);

  return result.total;
};

// Read all still-pending bids for one featured day ordered exactly like winner resolution.
exports.getPendingBidsForDate = (featureDate) => {
  return db
    .prepare(`
      SELECT *
      FROM blind_bids
      WHERE feature_date = ? AND status = 'PENDING'
      ORDER BY amount DESC, updated_at ASC, created_at ASC
    `)
    .all(featureDate);
};

// Count how many times one user has already been featured in the requested calendar month.
exports.getMonthlyWinCountByUser = (userId, monthKey) => {
  const result = db
    .prepare(`
      SELECT COUNT(*) AS total
      FROM featured_profile_winners
      WHERE user_id = ?
        AND substr(feature_date, 1, 7) = ?
    `)
    .get(userId, monthKey);

  return result.total;
};

// Read the alumni-event participation credit that can unlock one extra appearance in a month.
exports.getEventCreditByUserAndMonth = (userId, monthKey) => {
  return db
    .prepare(`
      SELECT
        c.*,
        e.event_name AS verified_event_name,
        e.event_date AS verified_event_date
      FROM alumni_event_credits c
      INNER JOIN university_alumni_events e
        ON e.id = c.event_id
      WHERE c.user_id = ? AND c.month_key = ?
        AND e.is_active = 1
      LIMIT 1
    `)
    .get(userId, monthKey);
};

// Read the verified university alumni events configured for one month so the UI can guide the user.
exports.getVerifiedEventsByMonth = (monthKey) => {
  return db
    .prepare(`
      SELECT
        id,
        month_key,
        event_name,
        event_date
      FROM university_alumni_events
      WHERE month_key = ?
        AND is_active = 1
      ORDER BY event_date ASC, event_name ASC
    `)
    .all(monthKey);
};

// Find one verified university alumni event by its name, date, month, and attendance code.
exports.findVerifiedEventByClaim = ({ monthKey, eventName, eventDate, attendanceCode }) => {
  return db
    .prepare(`
      SELECT
        id,
        month_key,
        event_name,
        event_date
      FROM university_alumni_events
      WHERE month_key = ?
        AND event_name = ?
        AND event_date = ?
        AND attendance_code_hash = ?
        AND is_active = 1
      LIMIT 1
    `)
    .get(monthKey, eventName, eventDate, hashAttendanceCode(attendanceCode));
};

// Record one alumni-event participation credit for the requested month.
exports.claimEventCredit = ({ userId, eventId, monthKey, eventName, eventDate }) => {
  const now = new Date().toISOString();

  return db.prepare(`
    INSERT INTO alumni_event_credits (
      user_id,
      event_id,
      month_key,
      event_name,
      event_date,
      created_at,
      updated_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?)
  `).run(userId, eventId, monthKey, eventName, eventDate, now, now);
};

// Read the maximum number of featured appearances this user may have in the target month.
exports.getMonthlyAppearanceAllowance = (
  userId,
  monthKey,
  baseMaxMonthlyWins = 3,
  eventBonusWins = 1
) => {
  return getMonthlyAppearanceAllowanceForUser(
    userId,
    monthKey,
    baseMaxMonthlyWins,
    eventBonusWins
  );
};

// Read unresolved featured days that should already have been finalized.
exports.getPendingFeatureDatesThrough = (featureDate) => {
  return db
    .prepare(`
      SELECT DISTINCT feature_date
      FROM blind_bids
      WHERE status = 'PENDING' AND feature_date <= ?
      ORDER BY feature_date ASC
    `)
    .all(featureDate)
    .map((row) => row.feature_date);
};

// Resolve one featured day by selecting the highest eligible bid and marking win/lose results.
exports.resolveFeatureDate = db.transaction((
  featureDate,
  baseMaxMonthlyWins = 3,
  eventBonusWins = 1
) => {
  const existingWinner = db
    .prepare(`
      SELECT id, bid_id, user_id, profile_id, feature_date, selected_at
      FROM featured_profile_winners
      WHERE feature_date = ?
    `)
    .get(featureDate);

  if (existingWinner) {
    return {
      resolved: false,
      hadExistingWinner: true,
      hadEligibleWinner: true,
      winningBidId: existingWinner.bid_id,
      winnersSelected: 1,
    };
  }

  const pendingBids = db
    .prepare(`
      SELECT *
      FROM blind_bids
      WHERE feature_date = ? AND status = 'PENDING'
      ORDER BY amount DESC, updated_at ASC, created_at ASC
    `)
    .all(featureDate);

  if (!pendingBids.length) {
    return {
      resolved: false,
      hadExistingWinner: false,
      hadEligibleWinner: false,
      winningBidId: null,
      winnersSelected: 0,
    };
  }

  const now = new Date().toISOString();
  const monthKey = String(featureDate).slice(0, 7);
  let winningBid = null;

  for (const bid of pendingBids) {
    const winCount = db
      .prepare(`
        SELECT COUNT(*) AS total
        FROM featured_profile_winners
        WHERE user_id = ?
          AND substr(feature_date, 1, 7) = ?
      `)
      .get(bid.user_id, monthKey).total;
    const monthlyAppearanceAllowance = getMonthlyAppearanceAllowanceForUser(
      bid.user_id,
      monthKey,
      baseMaxMonthlyWins,
      eventBonusWins
    );

    if (winCount < monthlyAppearanceAllowance) {
      winningBid = bid;
      break;
    }
  }

  if (winningBid) {
    db.prepare(`
      INSERT INTO featured_profile_winners (
        feature_date,
        bid_id,
        user_id,
        profile_id,
        selected_at
      )
      VALUES (?, ?, ?, ?, ?)
    `).run(
      featureDate,
      winningBid.id,
      winningBid.user_id,
      winningBid.profile_id,
      now
    );
  }

  const markBidResolved = db.prepare(`
    UPDATE blind_bids
    SET status = ?,
        resolved_at = ?,
        updated_at = ?
    WHERE id = ?
  `);

  pendingBids.forEach((bid) => {
    markBidResolved.run(
      winningBid && bid.id === winningBid.id ? "WON" : "LOST",
      now,
      now,
      bid.id
    );
  });

  return {
    resolved: true,
    hadExistingWinner: false,
    hadEligibleWinner: Boolean(winningBid),
    winningBidId: winningBid ? winningBid.id : null,
    winnersSelected: winningBid ? 1 : 0,
  };
});

// Read a user's bidding history so the UI can show win/lose feedback by featured day.
exports.getBidHistoryByUserId = (userId) => {
  return db
    .prepare(`
      SELECT
        b.id,
        b.feature_date,
        b.amount,
        b.status,
        b.created_at,
        b.updated_at,
        b.resolved_at,
        w.selected_at
      FROM blind_bids b
      LEFT JOIN featured_profile_winners w ON w.bid_id = b.id
      WHERE b.user_id = ?
      ORDER BY b.feature_date DESC, b.updated_at DESC
    `)
    .all(userId);
};

// Read the winner for one featured day for the public API and dashboard.
exports.getWinnerForDate = (featureDate) => {
  return db
    .prepare(`
      SELECT
        w.feature_date,
        w.selected_at,
        w.user_id,
        w.profile_id,
        u.full_name,
        u.email,
        p.contact_number,
        p.location,
        p.programme,
        p.graduation_date,
        p.industry_sector,
        p.professional_headline,
        p.biography,
        p.linkedin_url,
        p.profile_image_path
      FROM featured_profile_winners w
      INNER JOIN users u ON u.id = w.user_id
      LEFT JOIN alumni_profiles p ON p.user_id = w.user_id
      WHERE w.feature_date = ?
      LIMIT 1
    `)
    .get(featureDate);
};

// Read the most recently resolved featured days for dashboard history cards.
exports.getRecentResolvedWinners = (limit = 7) => {
  return db
    .prepare(`
      SELECT
        w.feature_date,
        w.selected_at,
        w.user_id,
        w.profile_id,
        u.full_name,
        u.email,
        p.contact_number,
        p.location,
        p.programme,
        p.graduation_date,
        p.industry_sector,
        p.professional_headline,
        p.biography,
        p.linkedin_url,
        p.profile_image_path
      FROM featured_profile_winners w
      INNER JOIN users u ON u.id = w.user_id
      LEFT JOIN alumni_profiles p ON p.user_id = w.user_id
      ORDER BY w.feature_date DESC, w.selected_at DESC
      LIMIT ?
    `)
    .all(limit);
};

// Read one bidder's current user + profile details so live leader previews can show the right public profile.
exports.getBidderProfileByUserId = (userId) => {
  return db
    .prepare(`
      SELECT
        u.id AS user_id,
        u.full_name,
        u.email,
        p.id AS profile_id,
        p.contact_number,
        p.location,
        p.programme,
        p.graduation_date,
        p.industry_sector,
        p.professional_headline,
        p.biography,
        p.linkedin_url,
        p.profile_image_path
      FROM users u
      LEFT JOIN alumni_profiles p ON p.user_id = u.id
      WHERE u.id = ?
      LIMIT 1
    `)
    .get(userId);
};

// Read resolved bid rows whose outcome emails have not been sent yet.
exports.getResolvedBidsPendingNotification = () => {
  return db
    .prepare(`
      SELECT
        b.id,
        b.feature_date,
        b.amount,
        b.status,
        b.resolved_at,
        u.email,
        u.full_name
      FROM blind_bids b
      INNER JOIN users u ON u.id = b.user_id
      WHERE b.status IN ('WON', 'LOST')
        AND b.outcome_notification_sent_at IS NULL
      ORDER BY b.resolved_at ASC, b.id ASC
    `)
    .all();
};

// Mark one resolved bid as having its outcome notification sent.
exports.markOutcomeNotificationSent = (bidId) => {
  const now = new Date().toISOString();

  return db.prepare(`
    UPDATE blind_bids
    SET outcome_notification_sent_at = ?,
        updated_at = ?
    WHERE id = ?
  `).run(now, now, bidId);
};
