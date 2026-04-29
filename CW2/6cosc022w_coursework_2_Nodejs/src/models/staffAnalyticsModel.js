// Import the shared SQLite database connection used by the staff dashboard queries.
const db = require("../config/db");

// Normalize nullable grouping values so charts and tables do not show blank labels.
function normalizeLabel(value, fallback = "Not specified") {
  return String(value || "").trim() || fallback;
}

// Convert SQLite aggregate rows into small chart-friendly label/value objects.
function mapCountRows(rows, labelKey = "label") {
  return rows.map((row) => ({
    label: normalizeLabel(row[labelKey]),
    value: Number(row.total || 0),
  }));
}

// Convert a count into a whole percentage while avoiding divide-by-zero errors.
function toPercentage(count, total) {
  if (!total) {
    return 0;
  }

  return Math.round((Number(count || 0) / total) * 100);
}

// Build the filtered alumni directory WHERE clause using only fixed predicates.
function buildAlumniFilterWhere(filters = {}) {
  const where = ["u.role = 'alumnus'", "p.id IS NOT NULL"];
  const params = [];

  if (filters.programme) {
    where.push("p.programme = ?");
    params.push(filters.programme);
  }

  if (filters.industrySector) {
    where.push("p.industry_sector = ?");
    params.push(filters.industrySector);
  }

  if (filters.graduationDate) {
    where.push("p.graduation_date = ?");
    params.push(filters.graduationDate);
  }

  if (filters.graduationYear) {
    where.push("substr(p.graduation_date, 1, 4) = ?");
    params.push(filters.graduationYear);
  }

  return {
    clause: where.join(" AND "),
    params,
  };
}

// Read high-level numbers used by the staff dashboard KPI cards.
exports.getDashboardSummary = () => {
  const alumniSummary = db.prepare(`
    SELECT
      COUNT(DISTINCT u.id) AS registered_alumni,
      SUM(CASE WHEN u.is_verified = 1 THEN 1 ELSE 0 END) AS verified_alumni,
      COUNT(DISTINCT p.id) AS completed_profiles,
      COUNT(DISTINCT NULLIF(TRIM(p.programme), '')) AS programme_count,
      COUNT(DISTINCT NULLIF(TRIM(p.industry_sector), '')) AS industry_count
    FROM users u
    LEFT JOIN alumni_profiles p ON p.user_id = u.id
    WHERE u.role = 'alumnus'
  `).get();

  const biddingSummary = db.prepare(`
    SELECT
      COUNT(*) AS total_bids,
      SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) AS pending_bids,
      SUM(CASE WHEN status = 'WON' THEN 1 ELSE 0 END) AS won_bids,
      SUM(CASE WHEN status = 'LOST' THEN 1 ELSE 0 END) AS lost_bids
    FROM blind_bids
  `).get();

  const winnerSummary = db.prepare(`
    SELECT COUNT(*) AS featured_winners
    FROM featured_profile_winners
  `).get();

  const apiSummary = db.prepare(`
    SELECT
      COUNT(DISTINCT c.id) AS api_clients,
      COUNT(DISTINCT t.id) AS api_tokens,
      SUM(CASE WHEN t.status = 'ACTIVE' THEN 1 ELSE 0 END) AS active_tokens,
      COUNT(l.id) AS total_api_requests,
      MAX(l.used_at) AS last_api_activity_at
    FROM api_clients c
    LEFT JOIN api_tokens t ON t.client_id = c.id
    LEFT JOIN api_usage_logs l ON l.client_id = c.id
  `).get();

  return {
    registeredAlumni: Number(alumniSummary.registered_alumni || 0),
    verifiedAlumni: Number(alumniSummary.verified_alumni || 0),
    completedProfiles: Number(alumniSummary.completed_profiles || 0),
    programmeCount: Number(alumniSummary.programme_count || 0),
    industryCount: Number(alumniSummary.industry_count || 0),
    totalBids: Number(biddingSummary.total_bids || 0),
    pendingBids: Number(biddingSummary.pending_bids || 0),
    wonBids: Number(biddingSummary.won_bids || 0),
    lostBids: Number(biddingSummary.lost_bids || 0),
    featuredWinners: Number(winnerSummary.featured_winners || 0),
    apiClients: Number(apiSummary.api_clients || 0),
    apiTokens: Number(apiSummary.api_tokens || 0),
    activeTokens: Number(apiSummary.active_tokens || 0),
    totalApiRequests: Number(apiSummary.total_api_requests || 0),
    lastApiActivityAt: apiSummary.last_api_activity_at || null,
  };
};

// Read dropdown values used by the alumni directory filters.
exports.getAlumniFilterOptions = () => {
  const programmes = db.prepare(`
    SELECT DISTINCT programme
    FROM alumni_profiles
    WHERE programme IS NOT NULL AND TRIM(programme) <> ''
    ORDER BY programme ASC
  `).all().map((row) => row.programme);

  const graduationYears = db.prepare(`
    SELECT DISTINCT substr(graduation_date, 1, 4) AS graduation_year
    FROM alumni_profiles
    WHERE graduation_date IS NOT NULL AND TRIM(graduation_date) <> ''
    ORDER BY graduation_year DESC
  `).all().map((row) => row.graduation_year);

  const industrySectors = db.prepare(`
    SELECT DISTINCT industry_sector
    FROM alumni_profiles
    WHERE industry_sector IS NOT NULL AND TRIM(industry_sector) <> ''
    ORDER BY industry_sector ASC
  `).all().map((row) => row.industry_sector);

  return {
    programmes,
    graduationYears,
    industrySectors,
  };
};

// Read alumni profile rows for staff filtering by programme, graduation date/year, and industry sector.
exports.getAlumniDirectory = (filters = {}, limit = 200) => {
  const filterQuery = buildAlumniFilterWhere(filters);

  return db.prepare(`
    SELECT
      u.id AS user_id,
      u.full_name,
      u.email,
      u.is_verified,
      p.id AS profile_id,
      p.contact_number,
      p.location,
      p.programme,
      p.graduation_date,
      p.industry_sector,
      p.professional_headline,
      p.linkedin_url,
      p.updated_at
    FROM users u
    INNER JOIN alumni_profiles p ON p.user_id = u.id
    WHERE ${filterQuery.clause}
    ORDER BY
      p.graduation_date DESC,
      p.programme ASC,
      u.full_name ASC
    LIMIT ?
  `).all(...filterQuery.params, limit);
};

// Count filtered alumni records separately so the UI can show how many matched.
exports.countAlumniDirectory = (filters = {}) => {
  const filterQuery = buildAlumniFilterWhere(filters);
  const result = db.prepare(`
    SELECT COUNT(*) AS total
    FROM users u
    INNER JOIN alumni_profiles p ON p.user_id = u.id
    WHERE ${filterQuery.clause}
  `).get(...filterQuery.params);

  return Number(result.total || 0);
};

// Read chart data for programme, graduation year, industry, bidding, and API usage pages.
exports.getGraphData = () => {
  const byProgramme = mapCountRows(db.prepare(`
    SELECT COALESCE(NULLIF(TRIM(programme), ''), 'Not specified') AS label, COUNT(*) AS total
    FROM alumni_profiles
    GROUP BY label
    ORDER BY total DESC, label ASC
  `).all());

  const byGraduationYear = mapCountRows(db.prepare(`
    SELECT COALESCE(NULLIF(substr(graduation_date, 1, 4), ''), 'Not specified') AS label, COUNT(*) AS total
    FROM alumni_profiles
    GROUP BY label
    ORDER BY
      CASE WHEN label = 'Not specified' THEN 1 ELSE 0 END,
      label ASC
  `).all());

  const byIndustrySector = mapCountRows(db.prepare(`
    SELECT COALESCE(NULLIF(TRIM(industry_sector), ''), 'Not specified') AS label, COUNT(*) AS total
    FROM alumni_profiles
    GROUP BY label
    ORDER BY total DESC, label ASC
  `).all());

  const byBidStatus = mapCountRows(db.prepare(`
    SELECT status AS label, COUNT(*) AS total
    FROM blind_bids
    GROUP BY status
    ORDER BY total DESC, label ASC
  `).all());

  const byApiEndpoint = mapCountRows(db.prepare(`
    SELECT endpoint_path AS label, COUNT(*) AS total
    FROM api_usage_logs
    GROUP BY endpoint_path
    ORDER BY total DESC, label ASC
    LIMIT 8
  `).all());

  const byApiOutcome = mapCountRows(db.prepare(`
    SELECT outcome AS label, COUNT(*) AS total
    FROM api_usage_logs
    GROUP BY outcome
    ORDER BY total DESC, label ASC
  `).all());

  const byApiDailyUsage = mapCountRows(db.prepare(`
    SELECT label, total
    FROM (
      SELECT substr(used_at, 1, 10) AS label, COUNT(*) AS total
      FROM api_usage_logs
      WHERE used_at IS NOT NULL
      GROUP BY label
      ORDER BY label DESC
      LIMIT 14
    )
    ORDER BY label ASC
  `).all());

  const byTokenStatus = mapCountRows(db.prepare(`
    SELECT status AS label, COUNT(*) AS total
    FROM api_tokens
    GROUP BY status
    ORDER BY total DESC, label ASC
  `).all());

  const profileCompleteness = db.prepare(`
    SELECT
      COUNT(*) AS total_profiles,
      SUM(CASE WHEN contact_number IS NOT NULL AND TRIM(contact_number) <> '' THEN 1 ELSE 0 END) AS contact_count,
      SUM(CASE WHEN programme IS NOT NULL AND TRIM(programme) <> '' THEN 1 ELSE 0 END) AS programme_count,
      SUM(CASE WHEN graduation_date IS NOT NULL AND TRIM(graduation_date) <> '' THEN 1 ELSE 0 END) AS graduation_count,
      SUM(CASE WHEN industry_sector IS NOT NULL AND TRIM(industry_sector) <> '' THEN 1 ELSE 0 END) AS industry_count,
      SUM(CASE WHEN linkedin_url IS NOT NULL AND TRIM(linkedin_url) <> '' THEN 1 ELSE 0 END) AS linkedin_count,
      SUM(CASE WHEN EXISTS (
        SELECT 1
        FROM alumni_profile_degrees d
        WHERE d.profile_id = alumni_profiles.id
      ) THEN 1 ELSE 0 END) AS education_count,
      SUM(CASE WHEN EXISTS (
        SELECT 1
        FROM alumni_profile_employment e
        WHERE e.profile_id = alumni_profiles.id
      ) THEN 1 ELSE 0 END) AS employment_count
    FROM alumni_profiles
  `).get();
  const totalProfiles = Number(profileCompleteness.total_profiles || 0);
  const profileCompletenessRadar = [
    {
      label: "Contact",
      value: toPercentage(profileCompleteness.contact_count, totalProfiles),
    },
    {
      label: "Programme",
      value: toPercentage(profileCompleteness.programme_count, totalProfiles),
    },
    {
      label: "Graduation",
      value: toPercentage(profileCompleteness.graduation_count, totalProfiles),
    },
    {
      label: "Industry",
      value: toPercentage(profileCompleteness.industry_count, totalProfiles),
    },
    {
      label: "LinkedIn",
      value: toPercentage(profileCompleteness.linkedin_count, totalProfiles),
    },
    {
      label: "Education",
      value: toPercentage(profileCompleteness.education_count, totalProfiles),
    },
    {
      label: "Employment",
      value: toPercentage(profileCompleteness.employment_count, totalProfiles),
    },
  ];

  return {
    byProgramme,
    byGraduationYear,
    byIndustrySector,
    byBidStatus,
    byApiEndpoint,
    byApiOutcome,
    byApiDailyUsage,
    byTokenStatus,
    profileCompletenessRadar,
  };
};

// Read recent API key/token activity across all clients for staff monitoring.
exports.getRecentApiUsageLogs = (limit = 20) => {
  return db.prepare(`
    SELECT
      l.id,
      l.request_method,
      l.endpoint_path,
      l.used_at,
      l.outcome,
      l.http_status,
      l.ip_address,
      l.details,
      c.client_name,
      c.client_type,
      t.token_prefix,
      t.scope_list,
      t.status AS token_status
    FROM api_usage_logs l
    LEFT JOIN api_clients c ON c.id = l.client_id
    LEFT JOIN api_tokens t ON t.id = l.token_id
    ORDER BY l.used_at DESC, l.id DESC
    LIMIT ?
  `).all(limit);
};
