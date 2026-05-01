# Student and Course Management API - Node.js MVC

This project completes both coursework scenarios using a proper Node.js MVC structure.

## What is included

Scenario 1: Student Management API
- Add new students
- View all students
- View one specific student
- Update full student information using PUT
- Update part of student information using PATCH
- Delete a student

Scenario 2: Course Management API
- Add new courses
- View all courses
- View one specific course
- Update full course information using PUT
- Update part of course information using PATCH
- Delete a course

Course fields:
- Course ID
- Course name
- Instructor name
- Credit value
- Department

## API key

All API routes require this header:

```text
x-api-key: student-course-api-key
```

The API key is configured in:

```text
config/appConfig.js
```

## MVC architecture

```text
mvc-boilerplate-clientside-rendering/
│
├── config/
│   └── appConfig.js
│
├── controllers/
│   ├── pageController.js
│   ├── studentController.js
│   └── courseController.js
│
├── data/
│   └── memoryDb.js
│
├── middleware/
│   ├── apiKeyAuth.js
│   ├── errorHandler.js
│   └── notFound.js
│
├── models/
│   ├── StudentModel.js
│   └── CourseModel.js
│
├── routes/
│   ├── apiRoutes.js
│   ├── pageRoutes.js
│   ├── studentRoutes.js
│   └── courseRoutes.js
│
├── views/
│   └── index.html
│
├── public/
│   ├── app.js
│   └── style.css
│
├── index.js
└── package.json
```

### Model

The model files contain the business/data logic. They use in-memory arrays from `data/memoryDb.js`.

```text
models/StudentModel.js
models/CourseModel.js
```

### View

The view is the client-side rendered user interface.

```text
views/index.html
public/app.js
public/style.css
```

### Controller

The controllers receive HTTP requests, call the model, and return JSON responses.

```text
controllers/studentController.js
controllers/courseController.js
controllers/pageController.js
```

### Routes

Routes connect URLs and HTTP methods to controller functions.

```text
routes/studentRoutes.js
routes/courseRoutes.js
routes/apiRoutes.js
routes/pageRoutes.js
```

### Middleware

Middleware handles cross-cutting logic such as API key authentication and error handling.

```text
middleware/apiKeyAuth.js
middleware/notFound.js
middleware/errorHandler.js
```

## How to run

Open the project folder in terminal and run:

```bash
npm install
npm start
```

Then open:

```text
http://localhost:3000
```

## Student API endpoints

```text
GET    /api/students
GET    /api/students/1
POST   /api/students
PUT    /api/students/1
PATCH  /api/students/1
DELETE /api/students/1
```

Example create student body:

```json
{
  "name": "John Silva",
  "email": "john@example.com",
  "age": 22,
  "course": "Computer Science"
}
```

## Course API endpoints

```text
GET    /api/courses
GET    /api/courses/1
GET    /api/courses/COSC001
POST   /api/courses
PUT    /api/courses/1
PATCH  /api/courses/1
DELETE /api/courses/1
```

Example create course body:

```json
{
  "courseId": "COSC003",
  "courseName": "Web Application Development",
  "instructorName": "Ms Uthpala",
  "creditValue": 20,
  "department": "Software Engineering"
}
```

## Example curl commands

View all students:

```bash
curl -H "x-api-key: student-course-api-key" http://localhost:3000/api/students
```

Create a course:

```bash
curl -X POST http://localhost:3000/api/courses \
  -H "Content-Type: application/json" \
  -H "x-api-key: student-course-api-key" \
  -d '{"courseId":"COSC003","courseName":"Web Application Development","instructorName":"Ms Uthpala","creditValue":20,"department":"Software Engineering"}'
```
