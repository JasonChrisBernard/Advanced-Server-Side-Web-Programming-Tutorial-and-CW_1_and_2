<!DOCTYPE html>
<html>
<head>
    <title>University Modules</title>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }

        h1, h2 {
            color: #222;
        }

        input {
            padding: 10px;
            width: 300px;
            font-size: 15px;
        }

        button {
            padding: 11px 18px;
            background: #1f2937;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            background: #374151;
        }

        .card {
            background: #f9fafb;
            padding: 15px;
            margin-top: 15px;
            border-left: 5px solid #1f2937;
            border-radius: 5px;
        }

        .error {
            color: red;
            font-weight: bold;
            margin-top: 15px;
        }

        .success {
            color: green;
            font-weight: bold;
            margin-top: 15px;
        }

        a {
            display: inline-block;
            margin-top: 25px;
            color: #2563eb;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .section {
            margin-top: 25px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>University Module Display</h1>

    <div class="section">
        <h2>View All Modules</h2>
        <button id="loadModules">Load All Modules</button>
    </div>

    <div class="section">
        <h2>Search Module Details</h2>
        <input type="text" id="moduleCode" placeholder="Enter module code e.g. 6COSC022W">
        <button id="searchModule">Search Module</button>
    </div>

    <div id="result"></div>

    <a href="<?php echo site_url('university_view/student'); ?>">Go to Student Module Search</a>
</div>

<script>
$(document).ready(function() {

    const API_KEY = 'UNI-API-2026-JASON-9F4A72';

    $('#loadModules').click(function() {
        $('#result').html('<p>Loading modules...</p>');

        $.ajax({
            url: "<?php echo site_url('api/university/modules'); ?>",
            type: "GET",
            dataType: "json",
            headers: {
                'X-API-KEY': API_KEY
            },
            success: function(response) {
                let html = '<h2>All Modules</h2>';

                if (response.status === true && response.data.length > 0) {
                    response.data.forEach(function(module) {
                        html += `
                            <div class="card">
                                <h3>${module.module_code}</h3>
                                <p><strong>Module Name:</strong> ${module.module_name}</p>
                                <p><strong>Lecturer:</strong> ${module.lecturer}</p>
                            </div>
                        `;
                    });
                } else {
                    html += '<p>No modules found.</p>';
                }

                $('#result').html(html);
            },
            error: function(xhr) {
                let message = 'Unable to load modules.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                $('#result').html('<p class="error">' + message + '</p>');
            }
        });
    });

    $('#searchModule').click(function() {
        let moduleCode = $('#moduleCode').val().trim();

        if (moduleCode === '') {
            $('#result').html('<p class="error">Please enter a module code.</p>');
            return;
        }

        $('#result').html('<p>Searching module...</p>');

        $.ajax({
            url: "<?php echo site_url('api/university/module'); ?>",
            type: "GET",
            data: {
                code: moduleCode
            },
            dataType: "json",
            headers: {
                'X-API-KEY': API_KEY
            },
            success: function(response) {
                let module = response.data.module;
                let students = response.data.students;

                let html = `
                    <h2>Module Details</h2>
                    <div class="card">
                        <h3>${module.module_code}</h3>
                        <p><strong>Module Name:</strong> ${module.module_name}</p>
                        <p><strong>Lecturer:</strong> ${module.lecturer}</p>
                    </div>

                    <h2>Registered Students</h2>
                `;

                if (students.length > 0) {
                    students.forEach(function(student) {
                        html += `
                            <div class="card">
                                <p><strong>Student Number:</strong> ${student.student_no}</p>
                                <p><strong>Name:</strong> ${student.full_name}</p>
                                <p><strong>Email:</strong> ${student.email}</p>
                            </div>
                        `;
                    });
                } else {
                    html += '<p>No students are registered for this module.</p>';
                }

                $('#result').html(html);
            },
            error: function(xhr) {
                let message = 'Module not found.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                $('#result').html('<p class="error">' + message + '</p>');
            }
        });
    });

});
</script>

</body>
</html>