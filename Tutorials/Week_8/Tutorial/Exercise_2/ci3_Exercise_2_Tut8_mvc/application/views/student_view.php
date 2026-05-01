<!DOCTYPE html>
<html>
<head>
    <title>Student Modules</title>

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
    <h1>Student Module Search</h1>

    <div class="section">
        <h2>Search Modules Taken by Student</h2>
        <input type="text" id="studentNo" placeholder="Enter student no e.g. W1953572">
        <button id="searchStudent">Search Student</button>
    </div>

    <div id="result"></div>

    <a href="<?php echo site_url('university_view/modules'); ?>">Back to Module Display</a>
</div>

<script>
$(document).ready(function() {

    const API_KEY = 'UNI-API-2026-JASON-9F4A72';

    $('#searchStudent').click(function() {
        let studentNo = $('#studentNo').val().trim();

        if (studentNo === '') {
            $('#result').html('<p class="error">Please enter a student number.</p>');
            return;
        }

        $('#result').html('<p>Searching student...</p>');

        $.ajax({
            url: "<?php echo site_url('api/university/student'); ?>",
            type: "GET",
            data: {
                student_no: studentNo
            },
            dataType: "json",
            headers: {
                'X-API-KEY': API_KEY
            },
            success: function(response) {
                let student = response.data.student;
                let modules = response.data.modules;

                let html = `
                    <h2>Student Details</h2>
                    <div class="card">
                        <p><strong>Student Number:</strong> ${student.student_no}</p>
                        <p><strong>Name:</strong> ${student.full_name}</p>
                        <p><strong>Email:</strong> ${student.email}</p>
                    </div>

                    <h2>Modules Taken</h2>
                `;

                if (modules.length > 0) {
                    modules.forEach(function(module) {
                        html += `
                            <div class="card">
                                <h3>${module.module_code}</h3>
                                <p><strong>Module Name:</strong> ${module.module_name}</p>
                                <p><strong>Lecturer:</strong> ${module.lecturer}</p>
                            </div>
                        `;
                    });
                } else {
                    html += '<p>This student is not registered for any modules.</p>';
                }

                $('#result').html(html);
            },
            error: function(xhr) {
                let message = 'Student not found.';

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