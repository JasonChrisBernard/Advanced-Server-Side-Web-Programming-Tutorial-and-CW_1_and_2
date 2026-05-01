'use strict'

module.exports = {
  port: process.env.PORT || 3000,
  apiKey: process.env.API_KEY || 'student-course-api-key',
  sessionSecret: process.env.SESSION_SECRET || 'student-course-management-secret'
}
