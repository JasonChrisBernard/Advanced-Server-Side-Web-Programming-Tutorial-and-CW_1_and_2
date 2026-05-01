const pool = require('../config/db');

async function create(data) {
  const [result] = await pool.execute(
    `INSERT INTO alumni
      (first_name, last_name, email, password_hash, verification_token, verification_expires, program, graduation_year, industry_sector, current_job_title, company, linkedin_url)
     VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), ?, ?, ?, ?, ?, ?)`,
    [
      data.first_name,
      data.last_name,
      data.email,
      data.password_hash,
      data.verification_token,
      data.program || null,
      data.graduation_year || null,
      data.industry_sector || null,
      data.current_job_title || null,
      data.company || null,
      data.linkedin_url || null
    ]
  );
  return result.insertId;
}

async function findByEmail(email) {
  const [rows] = await pool.execute('SELECT * FROM alumni WHERE email = ?', [email]);
  return rows[0];
}

async function findById(id) {
  const [rows] = await pool.execute('SELECT * FROM alumni WHERE id = ?', [id]);
  return rows[0];
}

async function findByVerificationToken(token) {
  const [rows] = await pool.execute(
    'SELECT * FROM alumni WHERE verification_token = ? AND verification_expires > NOW()',
    [token]
  );
  return rows[0];
}

async function verifyEmail(id) {
  await pool.execute(
    'UPDATE alumni SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE id = ?',
    [id]
  );
}

async function setResetToken(email, token) {
  const [result] = await pool.execute(
    'UPDATE alumni SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?',
    [token, email]
  );
  return result.affectedRows > 0;
}

async function findByResetToken(token) {
  const [rows] = await pool.execute(
    'SELECT * FROM alumni WHERE reset_token = ? AND reset_expires > NOW()',
    [token]
  );
  return rows[0];
}

async function updatePassword(id, passwordHash) {
  await pool.execute(
    'UPDATE alumni SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?',
    [passwordHash, id]
  );
}

async function updateProfile(id, data) {
  await pool.execute(
    `UPDATE alumni SET
      first_name = ?, last_name = ?, program = ?, graduation_year = ?, industry_sector = ?,
      current_job_title = ?, company = ?, linkedin_url = ?
     WHERE id = ?`,
    [
      data.first_name,
      data.last_name,
      data.program || null,
      data.graduation_year || null,
      data.industry_sector || null,
      data.current_job_title || null,
      data.company || null,
      data.linkedin_url || null,
      id
    ]
  );
}

async function updateProfileImage(id, filename) {
  await pool.execute('UPDATE alumni SET profile_image = ? WHERE id = ?', [filename, id]);
}

async function listProfiles(filters = {}) {
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

  const where = conditions.length ? `WHERE ${conditions.join(' AND ')}` : '';
  const [rows] = await pool.execute(
    `SELECT id, first_name, last_name, email, program, graduation_year, industry_sector,
            current_job_title, company, linkedin_url, profile_image, created_at
     FROM alumni ${where}
     ORDER BY created_at DESC`,
    values
  );
  return rows;
}

async function getFilterOptions() {
  const [programs] = await pool.execute('SELECT DISTINCT program FROM alumni WHERE program IS NOT NULL AND program <> "" ORDER BY program');
  const [years] = await pool.execute('SELECT DISTINCT graduation_year FROM alumni WHERE graduation_year IS NOT NULL ORDER BY graduation_year DESC');
  const [industries] = await pool.execute('SELECT DISTINCT industry_sector FROM alumni WHERE industry_sector IS NOT NULL AND industry_sector <> "" ORDER BY industry_sector');
  return { programs, years, industries };
}

module.exports = {
  create,
  findByEmail,
  findById,
  findByVerificationToken,
  verifyEmail,
  setResetToken,
  findByResetToken,
  updatePassword,
  updateProfile,
  updateProfileImage,
  listProfiles,
  getFilterOptions
};
