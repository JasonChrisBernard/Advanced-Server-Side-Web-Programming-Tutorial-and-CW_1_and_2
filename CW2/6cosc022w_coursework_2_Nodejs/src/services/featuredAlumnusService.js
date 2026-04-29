// Import the blind bidding model so today's featured alumnus can come from the resolved daily winner.
const BlindBid = require("../models/blindBidModel");
// Import the alumni profile model so the API can return the winner's saved public profile details.
const AlumniProfile = require("../models/alumniProfileModel");
// Import helpers that resolve due featured days and format month labels.
const {
  getFeatureDate,
  getTomorrowFeatureDate,
  getMonthKey,
  getMonthLabel,
  getMonthlyAppearanceAllowance,
  resolveDueFeaturedDays,
} = require("./blindBidService");

// Convert one repeatable profile section into a stable JSON-friendly shape for the public API.
function mapProfileSectionRows(rows, fieldMap) {
  return (rows || []).map((row) =>
    Object.entries(fieldMap).reduce((mappedRow, [targetKey, sourceKey]) => {
      mappedRow[targetKey] = row[sourceKey] || "";
      return mappedRow;
    }, {})
  );
}

// Build one consistent public-profile payload from the winning or currently leading user's saved alumni profile.
function buildFeaturedProfilePayload({
  featureDate,
  userSummary,
  selectedAt = null,
  previewStatus = null,
}) {
  const featureMonthKey = getMonthKey(featureDate);
  const savedProfile = userSummary?.user_id
    ? AlumniProfile.getProfileByUserId(userSummary.user_id)
    : null;

  return {
    featuredDate: featureDate,
    featureMonthKey,
    featureMonthLabel: getMonthLabel(featureMonthKey),
    previewStatus,
    alumni: {
      fullName: userSummary?.full_name || "",
      email: userSummary?.email || "",
      contactNumber: savedProfile?.contact_number || userSummary?.contact_number || "",
      programme: savedProfile?.programme || userSummary?.programme || "",
      graduationDate: savedProfile?.graduation_date || userSummary?.graduation_date || "",
      industrySector: savedProfile?.industry_sector || userSummary?.industry_sector || "",
      professionalHeadline:
        savedProfile?.professional_headline || userSummary?.professional_headline || "",
      biography: savedProfile?.biography || userSummary?.biography || "",
      linkedinUrl: savedProfile?.linkedin_url || userSummary?.linkedin_url || "",
      location: savedProfile?.location || userSummary?.location || "",
      profileImageUrl:
        savedProfile?.profile_image_path || userSummary?.profile_image_path || "",
      degrees: mapProfileSectionRows(savedProfile?.degrees, {
        title: "title",
        institutionName: "institution_name",
        officialUrl: "official_url",
        completionDate: "completion_date",
      }),
      certifications: mapProfileSectionRows(savedProfile?.certifications, {
        title: "title",
        providerName: "provider_name",
        officialUrl: "official_url",
        completionDate: "completion_date",
      }),
      licenses: mapProfileSectionRows(savedProfile?.licenses, {
        title: "title",
        awardingBody: "awarding_body",
        officialUrl: "official_url",
        completionDate: "completion_date",
      }),
      courses: mapProfileSectionRows(savedProfile?.courses, {
        title: "title",
        providerName: "provider_name",
        officialUrl: "official_url",
        completionDate: "completion_date",
      }),
      employmentHistory: mapProfileSectionRows(savedProfile?.employmentHistory, {
        employerName: "employer_name",
        jobTitle: "job_title",
        startDate: "start_date",
        endDate: "end_date",
      }),
      selectedAt,
    },
  };
}

// Read today's winning featured alumnus for the public API and developer portal preview.
exports.getTodayFeaturedAlumnus = async (date = new Date()) => {
  await resolveDueFeaturedDays(date);

  const todayFeatureDate = getFeatureDate(date);
  const winner = BlindBid.getWinnerForDate(todayFeatureDate);

  if (!winner) {
    return null;
  }

  return buildFeaturedProfilePayload({
    featureDate: todayFeatureDate,
    userSummary: winner,
    selectedAt: winner.selected_at,
  });
};

// Read the profile that is currently leading tomorrow's blind bidding slot without exposing bid amounts.
exports.getTomorrowLeadingFeaturedAlumnus = async (date = new Date()) => {
  await resolveDueFeaturedDays(date);

  const tomorrowFeatureDate = getTomorrowFeatureDate(date);
  const orderedPendingBids = BlindBid.getPendingBidsForDate(tomorrowFeatureDate);
  const monthKey = getMonthKey(tomorrowFeatureDate);
  let leadingBid = null;

  for (const bid of orderedPendingBids) {
    const monthlyWinsUsed = BlindBid.getMonthlyWinCountByUser(bid.user_id, monthKey);
    const monthlyAppearanceAllowance = getMonthlyAppearanceAllowance(
      bid.user_id,
      monthKey
    );

    if (monthlyWinsUsed < monthlyAppearanceAllowance) {
      leadingBid = bid;
      break;
    }
  }

  if (!leadingBid) {
    return null;
  }

  const leadingBidder = BlindBid.getBidderProfileByUserId(leadingBid.user_id);

  if (!leadingBidder) {
    return null;
  }

  return buildFeaturedProfilePayload({
    featureDate: tomorrowFeatureDate,
    userSummary: leadingBidder,
    previewStatus: "CURRENTLY WINNING",
  });
};
