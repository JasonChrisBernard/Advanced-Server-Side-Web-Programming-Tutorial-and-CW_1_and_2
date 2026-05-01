const crypto = require('crypto');

function randomToken(bytes = 32) {
  return crypto.randomBytes(bytes).toString('hex');
}

function hashApiKey(rawKey) {
  return crypto.createHash('sha256').update(rawKey).digest('hex');
}

module.exports = { randomToken, hashApiKey };
