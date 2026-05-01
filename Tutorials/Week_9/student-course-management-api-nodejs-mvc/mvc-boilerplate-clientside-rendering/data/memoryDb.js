'use strict'

// In-memory database.
// Important: data resets whenever the Node server restarts.

const db = {
  students: [
    {
      id: 1,
      name: 'Jason Bernard',
      email: 'jason@example.com',
      age: 22,
      course: 'Software Engineering'
    },
    {
      id: 2,
      name: 'Amelia Perera',
      email: 'amelia@example.com',
      age: 21,
      course: 'Computer Science'
    }
  ],

  courses: [
    {
      id: 1,
      courseId: 'COSC001',
      courseName: 'Advanced Server-Side Development',
      instructorName: 'Ms Uthpala',
      creditValue: 20,
      department: 'School of Computer Science'
    },
    {
      id: 2,
      courseId: 'COSC002',
      courseName: 'Database Systems',
      instructorName: 'Dr Fernando',
      creditValue: 20,
      department: 'Software Engineering'
    }
  ],

  nextStudentId: 3,
  nextCourseId: 3
}

module.exports = db
