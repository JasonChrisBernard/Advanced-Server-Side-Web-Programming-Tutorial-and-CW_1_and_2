const crypto = require('crypto');
const ApiKey = require('../models/ApiKey');

function showApiDocs(req, res) {
  res.render('developer/api-docs', {
    title: 'Developer API Documentation'
  });
}

async function showApiKeys(req, res, next) {
  try {
    const apiKeys = await ApiKey.listAll();

    res.render('developer/api-keys', {
      title: 'API Key Management',
      apiKeys,
      newApiKey: null
    });
  } catch (error) {
    next(error);
  }
}

async function createApiKey(req, res, next) {
  try {
    const name = req.body.name || 'Coursework Client';

    let permissions = req.body.permissions || [];
    if (!Array.isArray(permissions)) {
      permissions = [permissions];
    }

    if (!permissions.length) {
      req.flash('error', 'Select at least one API permission.');
      return res.redirect('/developer/api-keys');
    }

    const rawKey = `ak_${crypto.randomBytes(24).toString('hex')}`;

    await ApiKey.create({
      name,
      rawKey,
      permissions
    });

    const apiKeys = await ApiKey.listAll();

    res.render('developer/api-keys', {
      title: 'API Key Management',
      apiKeys,
      newApiKey: rawKey
    });
  } catch (error) {
    next(error);
  }
}

async function revokeApiKey(req, res, next) {
  try {
    await ApiKey.revoke(req.params.id);
    req.flash('success', 'API key revoked successfully.');
    res.redirect('/developer/api-keys');
  } catch (error) {
    next(error);
  }
}

module.exports = {
  showApiDocs,
  showApiKeys,
  createApiKey,
  revokeApiKey
};