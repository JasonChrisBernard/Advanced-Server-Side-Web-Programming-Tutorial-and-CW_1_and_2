
<!DOCTYPE html>
<html>
<body>
    

<form method="post" action="<?php echo $_SERVER['PHP_SELF']?>">
    <h1>Server Side CW1/CW2 marks Calculator</h1>
    Coursework 1: <input type="number" name="cw1" step="0.01" required>
    Coursework 2: <input type="number" name="cw2" step="0.01" required>
    <input type="submit">
</form>


<?php 

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $cw1 = "";
    $cw2 = "";
    $cw1Err = "";
    $cw2Err = "";

    if(empty($_POST['cw1'])){
        $cw1Err = "Coursework 1 mark is required";
    } else {
        $cw1 = $_POST['cw1'];

        if(!is_numeric($cw1)){
             $cw1Err = "Coursework 1 must be a number";
        } elseif($cw1 < 0 || $cw1 > 100) {
             $cw1Err = "Coursework 1 must be between 0 and 100";
        }
    }

    if(empty($_POST['cw2'])){
        $cw2Err = "Coursework 2 mark is required";
    } else {
        $cw2 = $_POST['cw2'];

        if(!is_numeric($cw2)){
             $cw2Err = "Coursework 2 must be a number";
        } elseif($cw2 < 0 || $cw2 > 100) {
             $cw1Err = "Coursework 2 must be between 0 and 100";
        }
    }


    if($cw1Err == "" || $cw2Err == ""){
        $cw = ($cw1 * 0.40) + ($cw2 * 0.60);
        echo "Overall Module Mark : " .$cw;
    } else {
        echo $cw1Err . "<br>";
        echo $cw2Err . "<br>";
    }



}

?>

</body>
</html>