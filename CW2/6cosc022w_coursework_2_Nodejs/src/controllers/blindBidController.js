// Import the alumni profile model so bidding can be tied to the user's saved profile.
const AlumniProfile = require("../models/alumniProfileModel");
// Import the blind bid model so this controller can read and write bid records.
const BlindBid = require("../models/blindBidModel");
// Import the featured alumnus service so the dashboard can preview today's public winner.
const { getTodayFeaturedAlumnus } = require("../services/featuredAlumnusService");
// Import day and month helpers used by the updated bidding workflow.
const {
  EVENT_BONUS_FEATURED_APPEARANCES,
  MAX_FEATURED_APPEARANCES_PER_MONTH,
  getFeatureDateLabel,
  getMonthKey,
  getMonthLabel,
  getMonthlyAppearanceAllowance,
  getTomorrowFeatureDate,
  resolveDueFeaturedDays,
} = require("../services/blindBidService");
// Import the amount parser so bidding inputs get normalized consistently.
const {
  parsePositiveAmount,
  sanitizeSingleLineText,
} = require("../utils/validators");

// Define a minimum bid so zero-value or negative bids are rejected.
const MINIMUM_BID_AMOUNT = 1;

// Read and immediately clear the one-time blind bidding message stored in the session.
function consumeFlashMessage(req) {
  const flash = req.session.blindBidFlash || { error: null, success: null };
  delete req.session.blindBidFlash;
  return flash;
}

// Store a one-time message in the session so it can be shown after a redirect.
function setFlashMessage(req, flash) {
  req.session.blindBidFlash = flash;
}

// Convert the posted amount into a clean number with two decimal places.
function normalizeBidAmount(rawValue) {
  return parsePositiveAmount(rawValue);
}

// Work out whether the current user's pending bid is provisionally winning without revealing any amounts.
function buildCurrentStanding(currentBid, featureDate) {
  if (!currentBid || currentBid.status !== "PENDING") {
    return {
      label: null,
      detail: null,
      isWinning: false,
    };
  }

  const orderedPendingBids = BlindBid.getPendingBidsForDate(featureDate);
  const monthKey = getMonthKey(featureDate);
  let provisionalWinningBid = null;

  for (const bid of orderedPendingBids) {
    const monthlyWinsUsed = BlindBid.getMonthlyWinCountByUser(bid.user_id, monthKey);
    const monthlyAppearanceAllowance = getMonthlyAppearanceAllowance(
      bid.user_id,
      monthKey
    );

    if (monthlyWinsUsed < monthlyAppearanceAllowance) {
      provisionalWinningBid = bid;
      break;
    }
  }

  if (!provisionalWinningBid) {
    return {
      label: "NOT WINNING",
      detail:
        "No eligible winner can be selected from the current bids because the monthly appearance limit blocks every pending bidder.",
      isWinning: false,
    };
  }

  const isWinning = provisionalWinningBid.id === currentBid.id;

  return {
    label: isWinning ? "WINNING" : "NOT WINNING",
    detail: isWinning
      ? "Your bid is currently the highest eligible bid for tomorrow's slot."
      : "Another eligible bid is currently ahead of yours, but the highest amount remains hidden.",
    isWinning,
  };
}

// Build all values needed by the blind bidding page in one place.
async function buildDashboardViewModel(req, flash = { error: null, success: null }) {
  await resolveDueFeaturedDays();

  const profile = AlumniProfile.getProfileByUserId(req.session.user.id);
  const tomorrowFeatureDate = getTomorrowFeatureDate();
  const tomorrowFeatureDateLabel = getFeatureDateLabel(tomorrowFeatureDate);
  const targetMonthKey = getMonthKey(tomorrowFeatureDate);
  const targetMonthLabel = getMonthLabel(targetMonthKey);
  const currentBid = BlindBid.getBidByUserAndFeatureDate(
    req.session.user.id,
    tomorrowFeatureDate
  );
  const bidHistory = BlindBid.getBidHistoryByUserId(req.session.user.id);
  const tomorrowBidCount = BlindBid.countBidsForDate(tomorrowFeatureDate);
  const monthlyWinsUsed = BlindBid.getMonthlyWinCountByUser(
    req.session.user.id,
    targetMonthKey
  );
  const eventCredit = BlindBid.getEventCreditByUserAndMonth(
    req.session.user.id,
    targetMonthKey
  );
  const verifiedEventsForMonth = BlindBid.getVerifiedEventsByMonth(targetMonthKey);
  const monthlyAppearanceAllowance = getMonthlyAppearanceAllowance(
    req.session.user.id,
    targetMonthKey
  );
  const todayFeatured = await getTodayFeaturedAlumnus();
  const recentResolvedWinners = BlindBid.getRecentResolvedWinners(7);
  const currentStanding = buildCurrentStanding(currentBid, tomorrowFeatureDate);
  const bidHistoryWithDisplay = bidHistory.map((bid) => {
    if (bid.status === "CANCELLED") {
      return {
        ...bid,
        statusDisplay: "CANCELLED",
      };
    }

    if (bid.status === "PENDING" && bid.feature_date === tomorrowFeatureDate) {
      return {
        ...bid,
        statusDisplay: currentStanding.label
          ? `${currentStanding.label} (not yet resolved)`
          : "PENDING",
      };
    }

    return {
      ...bid,
      statusDisplay: bid.status,
    };
  });

  return {
    title: "Blind Bidding Dashboard",
    error: flash.error || null,
    success: flash.success || null,
    hasProfile: Boolean(profile),
    profile,
    tomorrowFeatureDate,
    tomorrowFeatureDateLabel,
    targetMonthLabel,
    currentBid,
    currentStanding,
    tomorrowBidCount,
    monthlyWinsUsed,
    remainingMonthlyWins: Math.max(
      monthlyAppearanceAllowance - monthlyWinsUsed,
      0
    ),
    hasReachedMonthlyLimit: monthlyWinsUsed >= monthlyAppearanceAllowance,
    eventCredit,
    hasEventCredit: Boolean(eventCredit),
    verifiedEventsForMonth,
    monthlyAppearanceAllowance,
    minimumNextBid: currentBid
      ? (Number(currentBid.amount) + 0.01).toFixed(2)
      : MINIMUM_BID_AMOUNT.toFixed(2),
    canCancelBid: Boolean(currentBid && currentBid.status === "PENDING"),
    bidHistory: bidHistoryWithDisplay,
    todayFeatured,
    recentResolvedWinners,
    eventBonusFeaturedAppearances: EVENT_BONUS_FEATURED_APPEARANCES,
    maxFeaturedAppearancesPerMonth: MAX_FEATURED_APPEARANCES_PER_MONTH,
  };
}

// Render the blind bidding dashboard page.
exports.showBlindBiddingPage = async (req, res) => {
  try {
    const flash = consumeFlashMessage(req);
    const viewModel = await buildDashboardViewModel(req, flash);
    return res.render("bidding/dashboard", viewModel);
  } catch (error) {
    console.error(error);
    return res.render("bidding/dashboard", {
      title: "Blind Bidding Dashboard",
      error: "Something went wrong while loading the blind bidding page.",
      success: null,
      hasProfile: false,
      profile: null,
      tomorrowFeatureDate: getTomorrowFeatureDate(),
      tomorrowFeatureDateLabel: getFeatureDateLabel(getTomorrowFeatureDate()),
      targetMonthLabel: getMonthLabel(getMonthKey(getTomorrowFeatureDate())),
      currentBid: null,
      currentStanding: {
        label: null,
        detail: null,
        isWinning: false,
      },
      tomorrowBidCount: 0,
      monthlyWinsUsed: 0,
      remainingMonthlyWins: MAX_FEATURED_APPEARANCES_PER_MONTH,
      hasReachedMonthlyLimit: false,
      eventCredit: null,
      hasEventCredit: false,
      verifiedEventsForMonth: [],
      monthlyAppearanceAllowance: MAX_FEATURED_APPEARANCES_PER_MONTH,
      minimumNextBid: MINIMUM_BID_AMOUNT.toFixed(2),
      canCancelBid: false,
      bidHistory: [],
      todayFeatured: null,
      recentResolvedWinners: [],
      eventBonusFeaturedAppearances: EVENT_BONUS_FEATURED_APPEARANCES,
      maxFeaturedAppearancesPerMonth: MAX_FEATURED_APPEARANCES_PER_MONTH,
    });
  }
};

// Create a new bid or increase the user's bid for tomorrow's featured slot.
exports.submitBlindBid = async (req, res) => {
  try {
    await resolveDueFeaturedDays();

    const profile = AlumniProfile.getProfileByUserId(req.session.user.id);

    if (!profile) {
      const viewModel = await buildDashboardViewModel(req, {
        error: "Create your alumni profile before placing a blind bid.",
        success: null,
      });
      return res.render("bidding/dashboard", viewModel);
    }

    const bidAmount = normalizeBidAmount(req.body.amount);

    if (!bidAmount || bidAmount < MINIMUM_BID_AMOUNT) {
      const viewModel = await buildDashboardViewModel(req, {
        error: `Enter a valid bid amount of at least ${MINIMUM_BID_AMOUNT.toFixed(2)}.`,
        success: null,
      });
      return res.render("bidding/dashboard", viewModel);
    }

    const tomorrowFeatureDate = getTomorrowFeatureDate();
    const tomorrowFeatureDateLabel = getFeatureDateLabel(tomorrowFeatureDate);
    const targetMonthKey = getMonthKey(tomorrowFeatureDate);
    const targetMonthLabel = getMonthLabel(targetMonthKey);
    const monthlyWinsUsed = BlindBid.getMonthlyWinCountByUser(
      req.session.user.id,
      targetMonthKey
    );
    const monthlyAppearanceAllowance = getMonthlyAppearanceAllowance(
      req.session.user.id,
      targetMonthKey
    );
    const existingBid = BlindBid.getBidByUserAndFeatureDate(
      req.session.user.id,
      tomorrowFeatureDate
    );
    const existingAnyBid = BlindBid.getAnyBidByUserAndFeatureDate(
      req.session.user.id,
      tomorrowFeatureDate
    );

    if (monthlyWinsUsed >= monthlyAppearanceAllowance) {
      const viewModel = await buildDashboardViewModel(req, {
        error: `You have already used all ${monthlyAppearanceAllowance} featured appearances for ${targetMonthLabel}.`,
        success: null,
      });
      return res.render("bidding/dashboard", viewModel);
    }

    if (existingBid && bidAmount <= Number(existingBid.amount)) {
      const viewModel = await buildDashboardViewModel(req, {
        error: `Your updated bid must be higher than ${Number(existingBid.amount).toFixed(2)}.`,
        success: null,
      });
      return res.render("bidding/dashboard", viewModel);
    }

    if (existingBid) {
      BlindBid.updateBidAmount(existingBid.id, bidAmount);
      setFlashMessage(req, {
        error: null,
        success: `Your blind bid for ${tomorrowFeatureDateLabel} was increased to ${bidAmount.toFixed(2)}.`,
      });
    } else if (existingAnyBid && existingAnyBid.status === "CANCELLED") {
      BlindBid.reactivateBid(existingAnyBid.id, bidAmount);
      setFlashMessage(req, {
        error: null,
        success: `Your blind bid for ${tomorrowFeatureDateLabel} was placed again at ${bidAmount.toFixed(2)}.`,
      });
    } else {
      BlindBid.createBid({
        userId: req.session.user.id,
        profileId: profile.id,
        featureDate: tomorrowFeatureDate,
        amount: bidAmount,
      });
      setFlashMessage(req, {
        error: null,
        success: `Your blind bid for ${tomorrowFeatureDateLabel} was placed successfully.`,
      });
    }

    return res.redirect("/blind-bidding");
  } catch (error) {
    console.error(error);
    const viewModel = await buildDashboardViewModel(req, {
      error: "Something went wrong while saving your blind bid.",
      success: null,
    });
    return res.render("bidding/dashboard", viewModel);
  }
};

// Record one alumni-event participation credit so the user can unlock a fourth appearance in the target month.
exports.claimAlumniEventCredit = async (req, res) => {
  try {
    await resolveDueFeaturedDays();

    const tomorrowFeatureDate = getTomorrowFeatureDate();
    const targetMonthKey = getMonthKey(tomorrowFeatureDate);
    const targetMonthLabel = getMonthLabel(targetMonthKey);
    const eventName = sanitizeSingleLineText(req.body.eventName, 120);
    const eventDate = String(req.body.eventDate || "").trim();
    const attendanceCode = String(req.body.attendanceCode || "").trim();
    const existingEventCredit = BlindBid.getEventCreditByUserAndMonth(
      req.session.user.id,
      targetMonthKey
    );
    const verifiedEventsForMonth = BlindBid.getVerifiedEventsByMonth(targetMonthKey);

    if (existingEventCredit) {
      setFlashMessage(req, {
        error: `An alumni-event credit has already been recorded for ${targetMonthLabel}.`,
        success: null,
      });
      return res.redirect("/blind-bidding");
    }

    if (!verifiedEventsForMonth.length) {
      const viewModel = await buildDashboardViewModel(req, {
        error: `No verified university alumni events have been configured for ${targetMonthLabel} yet.`,
        success: null,
      });
      return res.render("bidding/dashboard", viewModel);
    }

    if (!eventName || !eventDate || !attendanceCode) {
      const viewModel = await buildDashboardViewModel(req, {
        error: "Enter the alumni event name, event date, and attendance code to unlock the extra monthly appearance.",
        success: null,
      });
      return res.render("bidding/dashboard", viewModel);
    }

    const verifiedEvent = BlindBid.findVerifiedEventByClaim({
      monthKey: targetMonthKey,
      eventName,
      eventDate,
      attendanceCode,
    });

    if (!verifiedEvent) {
      const viewModel = await buildDashboardViewModel(req, {
        error: "That event name, date, and attendance code did not match a verified university alumni event.",
        success: null,
      });
      return res.render("bidding/dashboard", viewModel);
    }

    BlindBid.claimEventCredit({
      userId: req.session.user.id,
      eventId: verifiedEvent.id,
      monthKey: targetMonthKey,
      eventName: verifiedEvent.event_name,
      eventDate: verifiedEvent.event_date,
    });

    setFlashMessage(req, {
      error: null,
      success: `Verified your attendance for ${verifiedEvent.event_name}. You can now compete for up to ${MAX_FEATURED_APPEARANCES_PER_MONTH + EVENT_BONUS_FEATURED_APPEARANCES} featured appearances in ${targetMonthLabel}.`,
    });
    return res.redirect("/blind-bidding");
  } catch (error) {
    console.error(error);

    const viewModel = await buildDashboardViewModel(req, {
      error: "Something went wrong while recording your alumni-event participation.",
      success: null,
    });
    return res.render("bidding/dashboard", viewModel);
  }
};

// Cancel the current user's pending bid for tomorrow's featured slot.
exports.cancelBlindBid = async (req, res) => {
  try {
    await resolveDueFeaturedDays();

    const tomorrowFeatureDate = getTomorrowFeatureDate();
    const tomorrowFeatureDateLabel = getFeatureDateLabel(tomorrowFeatureDate);
    const currentBid = BlindBid.getBidByUserAndFeatureDate(
      req.session.user.id,
      tomorrowFeatureDate
    );

    if (!currentBid || currentBid.status !== "PENDING") {
      setFlashMessage(req, {
        error: "You do not have a pending bid to cancel for tomorrow's slot.",
        success: null,
      });
      return res.redirect("/blind-bidding");
    }

    BlindBid.cancelBid(currentBid.id);
    setFlashMessage(req, {
      error: null,
      success: `Your blind bid for ${tomorrowFeatureDateLabel} was cancelled.`,
    });
    return res.redirect("/blind-bidding");
  } catch (error) {
    console.error(error);
    setFlashMessage(req, {
      error: "Something went wrong while cancelling your blind bid.",
      success: null,
    });
    return res.redirect("/blind-bidding");
  }
};
