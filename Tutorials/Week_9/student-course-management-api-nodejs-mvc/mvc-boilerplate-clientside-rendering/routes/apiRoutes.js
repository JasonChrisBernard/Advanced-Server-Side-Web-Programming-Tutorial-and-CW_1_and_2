'use strict'

const express = require('express')
const studentRoutes = require('./studentRoutes')
const courseRoutes = require('./courseRoutes')

const router = express.Router()

router.use('/students', studentRoutes)
router.use('/courses', courseRoutes)

module.exports = router
