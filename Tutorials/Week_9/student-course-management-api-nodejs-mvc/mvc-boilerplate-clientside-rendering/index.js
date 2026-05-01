'use strict'

const express = require('express')
const logger = require('morgan')
const path = require('path')
const session = require('express-session')
const methodOverride = require('method-override')

const config = require('./config/appConfig')
const apiKeyAuth = require('./middleware/apiKeyAuth')
const notFound = require('./middleware/notFound')
const errorHandler = require('./middleware/errorHandler')
const pageRoutes = require('./routes/pageRoutes')
const apiRoutes = require('./routes/apiRoutes')

const app = express()

app.use(logger('dev'))
app.use(express.static(path.join(__dirname, 'public')))
app.use(express.urlencoded({ extended: true }))
app.use(express.json())
app.use(methodOverride('_method'))

app.use(session({
  secret: config.sessionSecret,
  resave: false,
  saveUninitialized: false
}))

// View route for client-side rendering page
app.use('/', pageRoutes)

// API routes protected by API key authentication
app.use('/api', apiKeyAuth, apiRoutes)

app.use(notFound)
app.use(errorHandler)

app.listen(config.port, function () {
  console.log('Server running on http://localhost:' + config.port)
  console.log('API key: ' + config.apiKey)
})
