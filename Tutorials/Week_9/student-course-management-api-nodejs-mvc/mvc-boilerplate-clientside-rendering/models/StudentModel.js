'use strict'

const db = require('../data/memoryDb')

function findIndexById(id) {
  return db.students.findIndex(function (student) {
    return String(student.id) === String(id)
  })
}

function cleanStudent(payload) {
  const student = {}

  if (payload.name !== undefined) student.name = String(payload.name).trim()
  if (payload.email !== undefined) student.email = String(payload.email).trim()
  if (payload.age !== undefined) student.age = Number(payload.age)
  if (payload.course !== undefined) student.course = String(payload.course).trim()

  return student
}

function validateStudent(payload, partial) {
  const errors = []

  if (!partial || payload.name !== undefined) {
    if (!payload.name || String(payload.name).trim() === '') {
      errors.push('Student name is required')
    }
  }

  if (!partial || payload.email !== undefined) {
    if (!payload.email || String(payload.email).trim() === '') {
      errors.push('Student email is required')
    }
  }

  if (!partial || payload.age !== undefined) {
    if (payload.age === undefined || payload.age === '' || Number(payload.age) <= 0) {
      errors.push('Student age must be a positive number')
    }
  }

  if (!partial || payload.course !== undefined) {
    if (!payload.course || String(payload.course).trim() === '') {
      errors.push('Student course is required')
    }
  }

  return errors
}

class StudentModel {
  static getAll() {
    return db.students
  }

  static findById(id) {
    return db.students[findIndexById(id)] || null
  }

  static create(payload) {
    const student = cleanStudent(payload)
    student.id = db.nextStudentId++
    db.students.push(student)
    return student
  }

  static replace(id, payload) {
    const index = findIndexById(id)
    if (index === -1) return null

    const updatedStudent = cleanStudent(payload)
    updatedStudent.id = db.students[index].id
    db.students[index] = updatedStudent
    return updatedStudent
  }

  static update(id, payload) {
    const index = findIndexById(id)
    if (index === -1) return null

    Object.assign(db.students[index], cleanStudent(payload))
    return db.students[index]
  }

  static delete(id) {
    const index = findIndexById(id)
    if (index === -1) return null

    return db.students.splice(index, 1)[0]
  }

  static validate(payload, options) {
    return validateStudent(payload, options && options.partial)
  }
}

module.exports = StudentModel
