const crypto = require('crypto');
require('dotenv').config();
const ApiKey = require('../src/models/ApiKey');
const pool = require('../src/config/db');

async function main() {
  const name = process.argv[2] || 'Coursework Client';
  const permissionsArg = process.argv[3] || 'read:analytics,read:profiles,read:bids';
  const permissions = permissionsArg.split(',').map((permission) => permission.trim()).filter(Boolean);
  const rawKey = `ak_${crypto.randomBytes(24).toString('hex')}`;

  await ApiKey.create({ name, rawKey, permissions });

  console.log('\nAPI key created successfully. Copy it now because only the hash is stored.');
  console.log(`Name: ${name}`);
  console.log(`Permissions: ${permissions.join(', ')}`);
  console.log(`API Key: ${rawKey}\n`);
  await pool.end();
}

main().catch(async (error) => {
  console.error(error);
  await pool.end();
  process.exit(1);
});
