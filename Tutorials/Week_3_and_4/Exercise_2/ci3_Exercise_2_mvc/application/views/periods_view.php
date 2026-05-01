<!DOCTYPE html>
<html>
    <head>
        <title>Dinosaur Periods</title>
    </head>
    <body>
        <h1>Dinosaur Periods</h1>

        <ul>
            <?php foreach($periods as $period): ?>
                <li>
                    <a href="http://localhost:8080/index.php/Dinosaurs/getinfo/<?php echo $period; ?>">
                    <?php echo $period; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </body>
</html>