'use strict'

function notFound(req, res) {
  res.status(404).json({
    success: false,
    error: 'Route not found',
    url: req.originalUrl
  })
}

module.exports = notFound
