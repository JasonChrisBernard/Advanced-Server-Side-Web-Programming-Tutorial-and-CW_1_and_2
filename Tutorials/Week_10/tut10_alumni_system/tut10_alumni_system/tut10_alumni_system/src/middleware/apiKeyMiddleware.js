const ApiKey = require('../models/ApiKey');

function hasPermission(storedPermissions, requiredPermissions) {
  const permissions = Array.isArray(storedPermissions) ? storedPermissions : JSON.parse(storedPermissions || '[]');
  return requiredPermissions.every((permission) => permissions.includes(permission));
}

function requireApiKey(requiredPermissions = []) {
  return async (req, res, next) => {
    try {
      const rawKey = req.header('x-api-key') || req.query.api_key;
      if (!rawKey) {
        return res.status(401).json({ success: false, message: 'Missing API key.' });
      }

      const apiKey = await ApiKey.findActiveByRawKey(rawKey);
      if (!apiKey) {
        return res.status(401).json({ success: false, message: 'Invalid or expired API key.' });
      }

      if (!hasPermission(apiKey.permissions, requiredPermissions)) {
        return res.status(403).json({ success: false, message: 'API key does not have the required permission.' });
      }

      req.apiKey = apiKey;
      await ApiKey.touch(apiKey.id);
      next();
    } catch (error) {
      next(error);
    }
  };
}

module.exports = { requireApiKey };
