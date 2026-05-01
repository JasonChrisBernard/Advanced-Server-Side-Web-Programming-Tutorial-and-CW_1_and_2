'use strict'

const db = require('../data/memoryDb')

function findIndexByIdOrCourseId(id) {
  return db.courses.findIndex(function (course) {
    return String(course.id) === String(id) ||
      String(course.courseId).toLowerCase() === String(id).toLowerCase()
  })
}

function cleanCourse(payload) {
  const course = {}

  if (payload.courseId !== undefined) course.courseId = String(payload.courseId).trim()
  if (payload.courseName !== undefined) course.courseName = String(payload.courseName).trim()
  if (payload.instructorName !== undefined) course.instructorName = String(payload.instructorName).trim()
  if (payload.creditValue !== undefined) course.creditValue = Number(payload.creditValue)
  if (payload.department !== undefined) course.department = String(payload.department).trim()

  return course
}

function validateCourse(payload, partial) {
  const errors = []

  if (!partial || payload.courseId !== undefined) {
    if (!payload.courseId || String(payload.courseId).trim() === '') {
      errors.push('Course ID is required')
    }
  }

  if (!partial || payload.courseName !== undefined) {
    if (!payload.courseName || String(payload.courseName).trim() === '') {
      errors.push('Course name is required')
    }
  }

  if (!partial || payload.instructorName !== undefined) {
    if (!payload.instructorName || String(payload.instructorName).trim() === '') {
      errors.push('Instructor name is required')
    }
  }

  if (!partial || payload.creditValue !== undefined) {
    if (payload.creditValue === undefined || payload.creditValue === '' || Number(payload.creditValue) <= 0) {
      errors.push('Credit value must be a positive number')
    }
  }

  if (!partial || payload.department !== undefined) {
    if (!payload.department || String(payload.department).trim() === '') {
      errors.push('Department is required')
    }
  }

  return errors
}

function duplicateCourseId(courseId, currentInternalId) {
  return db.courses.some(function (course) {
    return String(course.courseId).toLowerCase() === String(courseId).toLowerCase() &&
      String(course.id) !== String(currentInternalId)
  })
}

class CourseModel {
  static getAll() {
    return db.courses
  }

  static findByIdOrCourseId(id) {
    return db.courses[findIndexByIdOrCourseId(id)] || null
  }

  static create(payload) {
    const course = cleanCourse(payload)
    course.id = db.nextCourseId++
    db.courses.push(course)
    return course
  }

  static replace(id, payload) {
    const index = findIndexByIdOrCourseId(id)
    if (index === -1) return null

    const updatedCourse = cleanCourse(payload)
    updatedCourse.id = db.courses[index].id
    db.courses[index] = updatedCourse
    return updatedCourse
  }

  static update(id, payload) {
    const index = findIndexByIdOrCourseId(id)
    if (index === -1) return null

    Object.assign(db.courses[index], cleanCourse(payload))
    return db.courses[index]
  }

  static delete(id) {
    const index = findIndexByIdOrCourseId(id)
    if (index === -1) return null

    return db.courses.splice(index, 1)[0]
  }

  static isDuplicateCourseId(courseId, currentInternalId) {
    return duplicateCourseId(courseId, currentInternalId)
  }

  static validate(payload, options) {
    return validateCourse(payload, options && options.partial)
  }
}

module.exports = CourseModel
