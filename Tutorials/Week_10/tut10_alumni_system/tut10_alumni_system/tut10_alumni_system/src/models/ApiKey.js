const pool = require('../config/db');
const { hashApiKey } = require('../utils/token');

async function create({ name, rawKey, permissions, expiresAt = null }) {
  const hash = hashApiKey(rawKey);
  const [result] = await pool.execute(
    'INSERT INTO api_keys (name, api_key_hash, permissions, expires_at) VALUES (?, ?, ?, ?)',
    [name, hash, JSON.stringify(permissions), expiresAt]
  );
  return result.insertId;
}

async function findActiveByRawKey(rawKey) {
  const hash = hashApiKey(rawKey);
  const [rows] = await pool.execute(
    `SELECT * FROM api_keys
     WHERE api_key_hash = ?
       AND is_active = 1
       AND (expires_at IS NULL OR expires_at > NOW())`,
    [hash]
  );
  return rows[0];
}

async function touch(id) {
  await pool.execute('UPDATE api_keys SET last_used_at = NOW() WHERE id = ?', [id]);
}

async function listAll() {
  const [rows] = await pool.execute(
    `SELECT id, name, permissions, is_active, expires_at, created_at, last_used_at
     FROM api_keys
     ORDER BY created_at DESC`
  );

  return rows;
}

async function revoke(id) {
  await pool.execute(
    'UPDATE api_keys SET is_active = 0 WHERE id = ?',
    [id]
  );
}

module.exports = {
  create,
  findActiveByRawKey,
  touch,
  listAll,
  revoke
};