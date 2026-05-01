'use strict'

const config = require('../config/appConfig')

function apiKeyAuth(req, res, next) {
  const receivedKey = req.get('x-api-key') || req.query.api_key

  if (receivedKey !== config.apiKey) {
    return res.status(401).json({
      success: false,
      error: 'Unauthorized. Valid API key is required.'
    })
  }

  next()
}

module.exports = apiKeyAuth
