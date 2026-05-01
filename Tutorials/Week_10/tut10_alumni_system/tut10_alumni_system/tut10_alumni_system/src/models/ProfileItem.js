const pool = require('../config/db');

const allowedTypes = ['degree', 'certification', 'license', 'course'];

function normalizeType(type) {
  return allowedTypes.includes(type) ? type : 'degree';
}

async function findByAlumniId(alumniId) {
  const [rows] = await pool.execute(
    'SELECT * FROM alumni_profile_items WHERE alumni_id = ? ORDER BY item_type, end_date DESC, created_at DESC',
    [alumniId]
  );
  return rows;
}

async function findByIdAndAlumni(id, alumniId) {
  const [rows] = await pool.execute(
    'SELECT * FROM alumni_profile_items WHERE id = ? AND alumni_id = ?',
    [id, alumniId]
  );
  return rows[0];
}

async function create(alumniId, data) {
  const [result] = await pool.execute(
    `INSERT INTO alumni_profile_items
      (alumni_id, item_type, title, institution, field_of_study, start_date, end_date, description)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      alumniId,
      normalizeType(data.item_type),
      data.title,
      data.institution || null,
      data.field_of_study || null,
      data.start_date || null,
      data.end_date || null,
      data.description || null
    ]
  );
  return result.insertId;
}

async function update(id, alumniId, data) {
  await pool.execute(
    `UPDATE alumni_profile_items SET
      item_type = ?, title = ?, institution = ?, field_of_study = ?, start_date = ?, end_date = ?, description = ?
     WHERE id = ? AND alumni_id = ?`,
    [
      normalizeType(data.item_type),
      data.title,
      data.institution || null,
      data.field_of_study || null,
      data.start_date || null,
      data.end_date || null,
      data.description || null,
      id,
      alumniId
    ]
  );
}

async function remove(id, alumniId) {
  await pool.execute('DELETE FROM alumni_profile_items WHERE id = ? AND alumni_id = ?', [id, alumniId]);
}

module.exports = { allowedTypes, normalizeType, findByAlumniId, findByIdAndAlumni, create, update, remove };
