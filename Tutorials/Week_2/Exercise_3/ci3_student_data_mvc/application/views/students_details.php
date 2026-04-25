<!DOCTYPE html>
<html>
    <head><title>Student Details</title></head>
    <body>
        <h1>Student Details</h1>

        <p><strong>Name:</strong> <?php echo $s_name ?></p>
        <p><strong>Age:</strong> <?php echo $s_age ?></p>
        <p><strong>Course:</strong> <?php echo $s_course ?></p>
        <p><strong>Student ID:</strong> <?php echo $s_id ?> </p>

        <p><strong>Picture:</strong></p>
        <img src="<?php echo $picture; ?>" alt="Student Picture" width="200">
    </body>
</html>