'use strict'

const StudentModel = require('../models/StudentModel')

exports.getAllStudents = function (req, res) {
  const students = StudentModel.getAll()

  res.json({
    success: true,
    count: students.length,
    students: students
  })
}

exports.getStudentById = function (req, res) {
  const student = StudentModel.findById(req.params.id)

  if (!student) {
    return res.status(404).json({
      success: false,
      error: 'Student not found'
    })
  }

  res.json({
    success: true,
    student: student
  })
}

exports.createStudent = function (req, res) {
  const errors = StudentModel.validate(req.body, { partial: false })

  if (errors.length > 0) {
    return res.status(400).json({
      success: false,
      errors: errors
    })
  }

  const student = StudentModel.create(req.body)

  res.status(201).json({
    success: true,
    message: 'Student added successfully',
    student: student
  })
}

exports.replaceStudent = function (req, res) {
  const errors = StudentModel.validate(req.body, { partial: false })

  if (errors.length > 0) {
    return res.status(400).json({
      success: false,
      errors: errors
    })
  }

  const student = StudentModel.replace(req.params.id, req.body)

  if (!student) {
    return res.status(404).json({
      success: false,
      error: 'Student not found'
    })
  }

  res.json({
    success: true,
    message: 'Student information updated successfully',
    student: student
  })
}

exports.updateStudentPartially = function (req, res) {
  const errors = StudentModel.validate(req.body, { partial: true })

  if (errors.length > 0) {
    return res.status(400).json({
      success: false,
      errors: errors
    })
  }

  const student = StudentModel.update(req.params.id, req.body)

  if (!student) {
    return res.status(404).json({
      success: false,
      error: 'Student not found'
    })
  }

  res.json({
    success: true,
    message: 'Student information partially updated successfully',
    student: student
  })
}

exports.deleteStudent = function (req, res) {
  const student = StudentModel.delete(req.params.id)

  if (!student) {
    return res.status(404).json({
      success: false,
      error: 'Student not found'
    })
  }

  res.json({
    success: true,
    message: 'Student deleted successfully',
    student: student
  })
}
