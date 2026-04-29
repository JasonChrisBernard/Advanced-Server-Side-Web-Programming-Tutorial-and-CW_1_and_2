// Import the blind bid model so the service can reuse the lower-level database helpers.
const BlindBid = require("../models/blindBidModel");
// Import the email service so winners and losers can receive automatic notifications.
const EmailService = require("./emailService");

// Keep the coursework's monthly appearance cap in one place for both UI and resolution logic.
const MAX_FEATURED_APPEARANCES_PER_MONTH = 3;
// One recorded alumni-event participation can unlock one extra featured appearance in that month.
const EVENT_BONUS_FEATURED_APPEARANCES = 1;
// Keep a module-level flag so the midnight scheduler starts only once.
let schedulerStarted = false;
// Keep the recovery interval handle so it is only registered once.
let recoveryIntervalHandle = null;
// Prevent overlapping background resolution jobs when midnight and recovery polling happen together.
let resolutionJobInProgress = false;

// Convert a Date object into a local YYYY-MM-DD key used for one featured day.
function getFeatureDate(date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

// Convert a Date object into tomorrow's YYYY-MM-DD featured-day key.
function getTomorrowFeatureDate(date = new Date()) {
  const tomorrow = new Date(date);
  tomorrow.setDate(tomorrow.getDate() + 1);
  return getFeatureDate(tomorrow);
}

// Convert a Date object or YYYY-MM-DD string into a YYYY-MM key for monthly limit checks.
function getMonthKey(dateOrFeatureDate = new Date()) {
  if (typeof dateOrFeatureDate === "string") {
    return String(dateOrFeatureDate).slice(0, 7);
  }

  const year = dateOrFeatureDate.getFullYear();
  const month = String(dateOrFeatureDate.getMonth() + 1).padStart(2, "0");
  return `${year}-${month}`;
}

// Convert a YYYY-MM key into a friendlier label for page headings.
function getMonthLabel(monthKey) {
  const [year, month] = monthKey.split("-").map(Number);
  const date = new Date(year, month - 1, 1);

  return date.toLocaleString("en-US", {
    month: "long",
    year: "numeric",
  });
}

// Convert a YYYY-MM-DD feature date into a readable label for emails and dashboards.
function getFeatureDateLabel(featureDate) {
  const [year, month, day] = String(featureDate).split("-").map(Number);
  const date = new Date(year, month - 1, day);

  return date.toLocaleString("en-US", {
    weekday: "long",
    month: "long",
    day: "numeric",
    year: "numeric",
  });
}

// Read the effective monthly appearance allowance for one bidder, including any alumni-event credit.
function getMonthlyAppearanceAllowance(userId, monthKey) {
  return BlindBid.getMonthlyAppearanceAllowance(
    userId,
    monthKey,
    MAX_FEATURED_APPEARANCES_PER_MONTH,
    EVENT_BONUS_FEATURED_APPEARANCES
  );
}

// Resolve every pending featured day up to and including the current local date.
async function resolveDueFeaturedDays(date = new Date()) {
  const todayFeatureDate = getFeatureDate(date);
  const pendingFeatureDates = BlindBid.getPendingFeatureDatesThrough(todayFeatureDate);

  return pendingFeatureDates.map((featureDate) => ({
    featureDate,
    ...BlindBid.resolveFeatureDate(
      featureDate,
      MAX_FEATURED_APPEARANCES_PER_MONTH,
      EVENT_BONUS_FEATURED_APPEARANCES
    ),
  }));
}

// Send outcome emails for resolved bids that have not been notified yet.
async function sendPendingBidOutcomeNotifications() {
  const pendingNotifications = BlindBid.getResolvedBidsPendingNotification();

  for (const bid of pendingNotifications) {
    const emailResult = await EmailService.sendBlindBidOutcomeEmail({
      to: bid.email,
      fullName: bid.full_name,
      featureDateLabel: getFeatureDateLabel(bid.feature_date),
      status: bid.status,
      amount: Number(bid.amount).toFixed(2),
    });

    if (emailResult.delivered) {
      BlindBid.markOutcomeNotificationSent(bid.id);
    }
  }

  return pendingNotifications.length;
}

// Calculate how many milliseconds remain until the next local midnight.
function getMillisecondsUntilNextMidnight() {
  const now = new Date();
  const nextMidnight = new Date(now);

  nextMidnight.setHours(24, 0, 0, 0);

  return nextMidnight.getTime() - now.getTime();
}

// Read the configured recovery polling interval so overdue featured days are finalized even after restarts or missed timers.
function getRecoveryIntervalMilliseconds() {
  const configuredMinutes = Number(process.env.BLIND_BID_RECOVERY_INTERVAL_MINUTES || 5);
  const safeMinutes = Number.isFinite(configuredMinutes) && configuredMinutes > 0
    ? configuredMinutes
    : 5;

  return safeMinutes * 60 * 1000;
}

// Run the blind bid resolver and print a short message when featured days were finalized.
async function runBlindBidResolutionJob(triggerLabel = "manual trigger") {
  if (resolutionJobInProgress) {
    return {
      summary: [],
      notificationsSent: 0,
      skipped: true,
    };
  }

  resolutionJobInProgress = true;

  try {
    const summary = await resolveDueFeaturedDays();
    const notificationsSent = await sendPendingBidOutcomeNotifications();

    if (summary.length) {
      console.log(
        `[BlindBidding] Resolved featured day results after ${triggerLabel}: ${summary
          .map((item) => `${item.featureDate} (${item.hadEligibleWinner ? "winner selected" : "no eligible winner"})`)
          .join(", ")}`
      );
    }

    if (notificationsSent) {
      console.log(
        `[BlindBidding] Sent ${notificationsSent} bid outcome notification(s) after ${triggerLabel}.`
      );
    }

    return {
      summary,
      notificationsSent,
    };
  } finally {
    resolutionJobInProgress = false;
  }
}

// Start one in-process scheduler that checks blind bidding at every local midnight.
function startBlindBidResolutionScheduler() {
  if (schedulerStarted) {
    return;
  }

  schedulerStarted = true;

  const scheduleNextRun = () => {
    const delay = getMillisecondsUntilNextMidnight();

    setTimeout(async () => {
      try {
        await runBlindBidResolutionJob("midnight scheduler");
      } catch (error) {
        console.error("[BlindBidding] Midnight resolver failed.", error);
      }

      scheduleNextRun();
    }, delay);
  };

  scheduleNextRun();

  recoveryIntervalHandle = setInterval(async () => {
    try {
      await runBlindBidResolutionJob("recovery poll");
    } catch (error) {
      console.error("[BlindBidding] Recovery poll failed.", error);
    }
  }, getRecoveryIntervalMilliseconds());
}

// Export helpers for controllers and server startup.
module.exports = {
  EVENT_BONUS_FEATURED_APPEARANCES,
  MAX_FEATURED_APPEARANCES_PER_MONTH,
  getFeatureDate,
  getFeatureDateLabel,
  getMonthKey,
  getMonthLabel,
  getMonthlyAppearanceAllowance,
  getTomorrowFeatureDate,
  resolveDueFeaturedDays,
  sendPendingBidOutcomeNotifications,
  runBlindBidResolutionJob,
  startBlindBidResolutionScheduler,
};
