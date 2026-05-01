const pool = require('../config/db');

async function findByAlumniId(alumniId) {
  const [rows] = await pool.execute(
    'SELECT * FROM employment_history WHERE alumni_id = ? ORDER BY is_current DESC, end_date DESC, start_date DESC',
    [alumniId]
  );
  return rows;
}

async function findByIdAndAlumni(id, alumniId) {
  const [rows] = await pool.execute(
    'SELECT * FROM employment_history WHERE id = ? AND alumni_id = ?',
    [id, alumniId]
  );
  return rows[0];
}

async function create(alumniId, data) {
  const [result] = await pool.execute(
    `INSERT INTO employment_history
      (alumni_id, job_title, company, industry_sector, start_date, end_date, is_current, description)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      alumniId,
      data.job_title,
      data.company,
      data.industry_sector || null,
      data.start_date || null,
      data.end_date || null,
      data.is_current ? 1 : 0,
      data.description || null
    ]
  );
  return result.insertId;
}

async function update(id, alumniId, data) {
  await pool.execute(
    `UPDATE employment_history SET
      job_title = ?, company = ?, industry_sector = ?, start_date = ?, end_date = ?, is_current = ?, description = ?
     WHERE id = ? AND alumni_id = ?`,
    [
      data.job_title,
      data.company,
      data.industry_sector || null,
      data.start_date || null,
      data.end_date || null,
      data.is_current ? 1 : 0,
      data.description || null,
      id,
      alumniId
    ]
  );
}

async function remove(id, alumniId) {
  await pool.execute('DELETE FROM employment_history WHERE id = ? AND alumni_id = ?', [id, alumniId]);
}

module.exports = { findByAlumniId, findByIdAndAlumni, create, update, remove };
