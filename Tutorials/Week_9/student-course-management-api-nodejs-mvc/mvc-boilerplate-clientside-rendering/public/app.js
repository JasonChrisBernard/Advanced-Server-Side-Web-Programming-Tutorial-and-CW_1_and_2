const API_KEY = 'student-course-api-key'
const app = document.getElementById('app')

function escapeHTML(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

function api(url, options = {}) {
  options.headers = options.headers || {}
  options.headers['x-api-key'] = API_KEY

  if (options.body) {
    options.headers['Content-Type'] = 'application/json'
    options.body = JSON.stringify(options.body)
  }

  return fetch(url, options).then(async function (res) {
    const data = await res.json()

    if (!res.ok) {
      const message = data.error || (Array.isArray(data.errors) ? data.errors.join(', ') : 'Request failed')
      throw new Error(message)
    }

    return data
  })
}

function showMessage(type, message) {
  return `<div class="message ${type}">${escapeHTML(message)}</div>`
}

function showStudents() {
  api('/api/students')
    .then(function (data) {
      const rows = data.students.map(function (student) {
        return `
          <tr>
            <td>${escapeHTML(student.id)}</td>
            <td>${escapeHTML(student.name)}</td>
            <td>${escapeHTML(student.email)}</td>
            <td>${escapeHTML(student.age)}</td>
            <td>${escapeHTML(student.course)}</td>
            <td class="actions">
              <button onclick="viewStudent(${student.id})">View</button>
              <button onclick="editStudent(${student.id})">Edit</button>
              <button class="danger" onclick="deleteStudent(${student.id})">Delete</button>
            </td>
          </tr>
        `
      }).join('')

      app.innerHTML = `
        <section class="card">
          <div class="section-title">
            <div>
              <p class="eyebrow">Scenario 1</p>
              <h2>Student Management</h2>
            </div>
            <span class="pill">${data.count} students</span>
          </div>

          <form onsubmit="addStudent(event)">
            <input name="name" placeholder="Student name" required>
            <input name="email" type="email" placeholder="Email" required>
            <input name="age" type="number" placeholder="Age" min="1" required>
            <input name="course" placeholder="Course" required>
            <button>Add Student</button>
          </form>

          <h3>All Students</h3>
          <div class="table-wrapper">
            <table>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Age</th>
                <th>Course</th>
                <th>Actions</th>
              </tr>
              ${rows}
            </table>
          </div>
        </section>
      `
    })
    .catch(function (err) {
      app.innerHTML = showMessage('error', err.message)
    })
}

function addStudent(event) {
  event.preventDefault()
  const form = event.target

  api('/api/students', {
    method: 'POST',
    body: {
      name: form.name.value,
      email: form.email.value,
      age: form.age.value,
      course: form.course.value
    }
  })
    .then(showStudents)
    .catch(function (err) { alert(err.message) })
}

function viewStudent(id) {
  api('/api/students/' + id).then(function (data) {
    alert(
      'Student ID: ' + data.student.id +
      '\nName: ' + data.student.name +
      '\nEmail: ' + data.student.email +
      '\nAge: ' + data.student.age +
      '\nCourse: ' + data.student.course
    )
  })
}

function editStudent(id) {
  api('/api/students/' + id).then(function (data) {
    const student = data.student

    app.innerHTML = `
      <section class="card narrow">
        <p class="eyebrow">Scenario 1</p>
        <h2>Edit Student</h2>

        <form onsubmit="updateStudent(event, ${student.id})">
          <label>Student name</label>
          <input name="name" value="${escapeHTML(student.name)}" required>
          <label>Email</label>
          <input name="email" type="email" value="${escapeHTML(student.email)}" required>
          <label>Age</label>
          <input name="age" type="number" min="1" value="${escapeHTML(student.age)}" required>
          <label>Course</label>
          <input name="course" value="${escapeHTML(student.course)}" required>

          <button type="submit">PUT Update Full Record</button>
          <button type="button" onclick="patchStudent(${student.id})">PATCH Name Only</button>
          <button type="button" class="secondary" onclick="showStudents()">Cancel</button>
        </form>
      </section>
    `
  })
}

function updateStudent(event, id) {
  event.preventDefault()
  const form = event.target

  api('/api/students/' + id, {
    method: 'PUT',
    body: {
      name: form.name.value,
      email: form.email.value,
      age: form.age.value,
      course: form.course.value
    }
  })
    .then(showStudents)
    .catch(function (err) { alert(err.message) })
}

function patchStudent(id) {
  const newName = prompt('Enter new student name:')
  if (!newName) return

  api('/api/students/' + id, {
    method: 'PATCH',
    body: { name: newName }
  })
    .then(showStudents)
    .catch(function (err) { alert(err.message) })
}

function deleteStudent(id) {
  if (!confirm('Delete this student?')) return

  api('/api/students/' + id, { method: 'DELETE' })
    .then(showStudents)
    .catch(function (err) { alert(err.message) })
}

function showCourses() {
  api('/api/courses')
    .then(function (data) {
      const rows = data.courses.map(function (course) {
        return `
          <tr>
            <td>${escapeHTML(course.courseId)}</td>
            <td>${escapeHTML(course.courseName)}</td>
            <td>${escapeHTML(course.instructorName)}</td>
            <td>${escapeHTML(course.creditValue)}</td>
            <td>${escapeHTML(course.department)}</td>
            <td class="actions">
              <button onclick="viewCourse('${course.id}')">View</button>
              <button onclick="editCourse('${course.id}')">Edit</button>
              <button class="danger" onclick="deleteCourse('${course.id}')">Delete</button>
            </td>
          </tr>
        `
      }).join('')

      app.innerHTML = `
        <section class="card">
          <div class="section-title">
            <div>
              <p class="eyebrow">Scenario 2</p>
              <h2>Course Management</h2>
            </div>
            <span class="pill">${data.count} courses</span>
          </div>

          <form onsubmit="addCourse(event)">
            <input name="courseId" placeholder="Course ID" required>
            <input name="courseName" placeholder="Course name" required>
            <input name="instructorName" placeholder="Instructor name" required>
            <input name="creditValue" type="number" min="1" placeholder="Credit value" required>
            <input name="department" placeholder="Department" required>
            <button>Add Course</button>
          </form>

          <h3>All Courses</h3>
          <div class="table-wrapper">
            <table>
              <tr>
                <th>Course ID</th>
                <th>Course Name</th>
                <th>Instructor</th>
                <th>Credits</th>
                <th>Department</th>
                <th>Actions</th>
              </tr>
              ${rows}
            </table>
          </div>
        </section>
      `
    })
    .catch(function (err) {
      app.innerHTML = showMessage('error', err.message)
    })
}

function addCourse(event) {
  event.preventDefault()
  const form = event.target

  api('/api/courses', {
    method: 'POST',
    body: {
      courseId: form.courseId.value,
      courseName: form.courseName.value,
      instructorName: form.instructorName.value,
      creditValue: form.creditValue.value,
      department: form.department.value
    }
  })
    .then(showCourses)
    .catch(function (err) { alert(err.message) })
}

function viewCourse(id) {
  api('/api/courses/' + id).then(function (data) {
    alert(
      'Course ID: ' + data.course.courseId +
      '\nCourse Name: ' + data.course.courseName +
      '\nInstructor: ' + data.course.instructorName +
      '\nCredit Value: ' + data.course.creditValue +
      '\nDepartment: ' + data.course.department
    )
  })
}

function editCourse(id) {
  api('/api/courses/' + id).then(function (data) {
    const course = data.course

    app.innerHTML = `
      <section class="card narrow">
        <p class="eyebrow">Scenario 2</p>
        <h2>Edit Course</h2>

        <form onsubmit="updateCourse(event, ${course.id})">
          <label>Course ID</label>
          <input name="courseId" value="${escapeHTML(course.courseId)}" required>
          <label>Course name</label>
          <input name="courseName" value="${escapeHTML(course.courseName)}" required>
          <label>Instructor name</label>
          <input name="instructorName" value="${escapeHTML(course.instructorName)}" required>
          <label>Credit value</label>
          <input name="creditValue" type="number" min="1" value="${escapeHTML(course.creditValue)}" required>
          <label>Department</label>
          <input name="department" value="${escapeHTML(course.department)}" required>

          <button type="submit">PUT Update Full Record</button>
          <button type="button" onclick="patchCourse(${course.id})">PATCH Course Name Only</button>
          <button type="button" class="secondary" onclick="showCourses()">Cancel</button>
        </form>
      </section>
    `
  })
}

function updateCourse(event, id) {
  event.preventDefault()
  const form = event.target

  api('/api/courses/' + id, {
    method: 'PUT',
    body: {
      courseId: form.courseId.value,
      courseName: form.courseName.value,
      instructorName: form.instructorName.value,
      creditValue: form.creditValue.value,
      department: form.department.value
    }
  })
    .then(showCourses)
    .catch(function (err) { alert(err.message) })
}

function patchCourse(id) {
  const newName = prompt('Enter new course name:')
  if (!newName) return

  api('/api/courses/' + id, {
    method: 'PATCH',
    body: { courseName: newName }
  })
    .then(showCourses)
    .catch(function (err) { alert(err.message) })
}

function deleteCourse(id) {
  if (!confirm('Delete this course?')) return

  api('/api/courses/' + id, { method: 'DELETE' })
    .then(showCourses)
    .catch(function (err) { alert(err.message) })
}

function showApiHelp() {
  app.innerHTML = `
    <section class="card">
      <p class="eyebrow">Testing</p>
      <h2>API Help</h2>

      <p>Use this API key in every protected request:</p>
      <pre>x-api-key: ${API_KEY}</pre>

      <div class="grid-two">
        <div>
          <h3>Student API</h3>
          <pre>GET    /api/students
GET    /api/students/1
POST   /api/students
PUT    /api/students/1
PATCH  /api/students/1
DELETE /api/students/1</pre>
        </div>

        <div>
          <h3>Course API</h3>
          <pre>GET    /api/courses
GET    /api/courses/1
GET    /api/courses/COSC001
POST   /api/courses
PUT    /api/courses/1
PATCH  /api/courses/1
DELETE /api/courses/1</pre>
        </div>
      </div>
    </section>
  `
}

function showMvcHelp() {
  app.innerHTML = `
    <section class="card">
      <p class="eyebrow">Project Structure</p>
      <h2>MVC Architecture Used</h2>

      <div class="mvc-list">
        <p><strong>Model:</strong> <code>models/StudentModel.js</code> and <code>models/CourseModel.js</code> handle data logic, validation, searching, creating, updating, and deleting records.</p>
        <p><strong>View:</strong> <code>views/index.html</code>, <code>public/app.js</code>, and <code>public/style.css</code> display the client-side rendered interface.</p>
        <p><strong>Controller:</strong> <code>controllers/studentController.js</code> and <code>controllers/courseController.js</code> receive requests, call models, and return JSON responses.</p>
        <p><strong>Routes:</strong> <code>routes/studentRoutes.js</code>, <code>routes/courseRoutes.js</code>, and <code>routes/apiRoutes.js</code> map URLs to controller functions.</p>
        <p><strong>Middleware:</strong> <code>middleware/apiKeyAuth.js</code> protects the REST API using the API key.</p>
      </div>
    </section>
  `
}

showStudents()
