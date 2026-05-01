<!DOCTYPE html>
<html>
<head>
    <title>Age Calculator</title>
</head>
<body>
    <h1> Age Calculator </h1>

    <form method="post" action="http://localhost:8080/index.php/Age/calculate">
        <label>Enter your date of birth: </label>
        <input type="date" name="date_of_birth" required>

        <br><br>
        <button type="submit" >Calculate Age</button>

    </form>
</body>
</html>
