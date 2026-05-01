const pool = require('../config/db');

function buildFilters(filters) {
  const conditions = [];
  const values = [];
  if (filters.program) {
    conditions.push('program = ?');
    values.push(filters.program);
  }
  if (filters.graduation_year) {
    conditions.push('graduation_year = ?');
    values.push(filters.graduation_year);
  }
  if (filters.industry_sector) {
    conditions.push('industry_sector = ?');
    values.push(filters.industry_sector);
  }
  return {
    where: conditions.length ? `WHERE ${conditions.join(' AND ')}` : '',
    values
  };
}

async function getDashboardData(filters = {}) {
  const { where, values } = buildFilters(filters);

  const [programs] = await pool.execute(
    `SELECT COALESCE(program, 'Not specified') AS label, COUNT(*) AS total
     FROM alumni ${where}
     GROUP BY COALESCE(program, 'Not specified')
     ORDER BY total DESC`,
    values
  );

  const [industries] = await pool.execute(
    `SELECT COALESCE(industry_sector, 'Not specified') AS label, COUNT(*) AS total
     FROM alumni ${where}
     GROUP BY COALESCE(industry_sector, 'Not specified')
     ORDER BY total DESC`,
    values
  );

  const [careerPaths] = await pool.execute(
    `SELECT COALESCE(current_job_title, 'Not specified') AS label, COUNT(*) AS total
     FROM alumni ${where}
     GROUP BY COALESCE(current_job_title, 'Not specified')
     ORDER BY total DESC
     LIMIT 10`,
    values
  );

  const [graduationYears] = await pool.execute(
    `SELECT COALESCE(CAST(graduation_year AS CHAR), 'Not specified') AS label, COUNT(*) AS total
     FROM alumni ${where}
     GROUP BY graduation_year
     ORDER BY graduation_year DESC`,
    values
  );

  const [professionalDevelopment] = await pool.execute(
    `SELECT item_type AS label, COUNT(*) AS total
     FROM alumni_profile_items
     GROUP BY item_type
     ORDER BY total DESC`
  );

  const [monthlyBids] = await pool.execute(
    `SELECT month_key AS label, COUNT(*) AS total, ROUND(SUM(bid_amount), 2) AS amount
     FROM bids
     GROUP BY month_key
     ORDER BY month_key DESC
     LIMIT 12`
  );

  const [skillGaps] = await pool.execute(
    `SELECT COALESCE(field_of_study, title, 'Not specified') AS label, COUNT(*) AS total
     FROM alumni_profile_items
     WHERE item_type IN ('certification', 'course', 'license')
     GROUP BY COALESCE(field_of_study, title, 'Not specified')
     ORDER BY total ASC
     LIMIT 10`
  );

  return {
    programs,
    industries,
    careerPaths,
    graduationYears,
    professionalDevelopment,
    monthlyBids,
    skillGaps
  };
}

module.exports = { getDashboardData };
