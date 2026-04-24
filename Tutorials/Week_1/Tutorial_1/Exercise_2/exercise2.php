
<!DOCTYPE html>
<html>
<body>
    

<form method="post" action="<?php echo $_SERVER['PHP_SELF']?>">
    <h1>Student's 6COSC022W Marks</h1>
    Enter Student Mark <input type="number" step="0.01" name="mark"><br>
    <input type="submit">
</form>


<?php 
echo "<br>";
$students = [
    "Samwise Gamgee" => 88, 
    "Frodo Baggins" => 56,
    "Elrond Half-Elven" => 92,
    "Gandalf Mithrandir" => 35,
    "Merry Brandybuck" => 41,
    "Pippin Took" => 25,
    "Legolas Greenleaf" => 67
    ];


echo "<table border = '1' cellpadding='8'>";
echo "<tr><th>Name</th><th>Mark</th></tr>";


foreach($students as $names => $marks){
    echo "<tr>";
    echo "<td>$names</td>"; 
    echo "<td>$marks</td>";
    echo "</tr>";


}

echo "</table>";
echo "<br>";

if($_SERVER['REQUEST_METHOD'] == "POST"){
   $mark = "";
   $markErr = "";

   if(empty($_POST['mark'])){
     $markErr = "Mark is Required";
   } else {
        $mark = $_POST['mark'];

        if(!is_numeric($mark)){
            $markErr = "Mark must be a number";
        } elseif($mark < 0 || $mark > 100) {
            $markErr = "Mark must be between 0 and 100";
        }
   }

   

    if($markErr == ""){
        echo "<table border = '1' cellpadding='8'>";
        echo "<tr><th>Name</th><th>Mark</th></tr>";

        $found = false;

        foreach($students as $names => $marks){
        if($marks >= $mark) {
            echo "<tr>";
            echo "<td>$names</td>"; 
            echo "<td>$marks</td>";
            echo "</tr>";
            $found = true;

        } 
        
    }

    echo "</table>";

    if($found == false){
        echo "No students Found";
    }
    }

    else {
        echo $markErr;
    }
    


}

?>

</body>
</html>