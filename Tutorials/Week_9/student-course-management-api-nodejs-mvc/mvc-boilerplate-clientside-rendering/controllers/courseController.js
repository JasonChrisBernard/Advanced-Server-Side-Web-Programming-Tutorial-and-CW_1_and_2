'use strict'

const CourseModel = require('../models/CourseModel')

exports.getAllCourses = function (req, res) {
  const courses = CourseModel.getAll()

  res.json({
    success: true,
    count: courses.length,
    courses: courses
  })
}

exports.getCourseById = function (req, res) {
  const course = CourseModel.findByIdOrCourseId(req.params.id)

  if (!course) {
    return res.status(404).json({
      success: false,
      error: 'Course not found'
    })
  }

  res.json({
    success: true,
    course: course
  })
}

exports.createCourse = function (req, res) {
  const errors = CourseModel.validate(req.body, { partial: false })

  if (errors.length > 0) {
    return res.status(400).json({
      success: false,
      errors: errors
    })
  }

  if (CourseModel.isDuplicateCourseId(req.body.courseId)) {
    return res.status(409).json({
      success: false,
      error: 'Course ID already exists'
    })
  }

  const course = CourseModel.create(req.body)

  res.status(201).json({
    success: true,
    message: 'Course added successfully',
    course: course
  })
}

exports.replaceCourse = function (req, res) {
  const errors = CourseModel.validate(req.body, { partial: false })

  if (errors.length > 0) {
    return res.status(400).json({
      success: false,
      errors: errors
    })
  }

  const existingCourse = CourseModel.findByIdOrCourseId(req.params.id)

  if (!existingCourse) {
    return res.status(404).json({
      success: false,
      error: 'Course not found'
    })
  }

  if (CourseModel.isDuplicateCourseId(req.body.courseId, existingCourse.id)) {
    return res.status(409).json({
      success: false,
      error: 'Course ID already exists'
    })
  }

  const course = CourseModel.replace(req.params.id, req.body)

  res.json({
    success: true,
    message: 'Course information updated successfully',
    course: course
  })
}

exports.updateCoursePartially = function (req, res) {
  const errors = CourseModel.validate(req.body, { partial: true })

  if (errors.length > 0) {
    return res.status(400).json({
      success: false,
      errors: errors
    })
  }

  const existingCourse = CourseModel.findByIdOrCourseId(req.params.id)

  if (!existingCourse) {
    return res.status(404).json({
      success: false,
      error: 'Course not found'
    })
  }

  if (req.body.courseId !== undefined && CourseModel.isDuplicateCourseId(req.body.courseId, existingCourse.id)) {
    return res.status(409).json({
      success: false,
      error: 'Course ID already exists'
    })
  }

  const course = CourseModel.update(req.params.id, req.body)

  res.json({
    success: true,
    message: 'Course information partially updated successfully',
    course: course
  })
}

exports.deleteCourse = function (req, res) {
  const course = CourseModel.delete(req.params.id)

  if (!course) {
    return res.status(404).json({
      success: false,
      error: 'Course not found'
    })
  }

  res.json({
    success: true,
    message: 'Course deleted successfully',
    course: course
  })
}
