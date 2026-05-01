<!DOCTYPE html>
<html>
<head>
    <title>Period Information</title>
</head>
<body>

    <?php if ($info): ?>

        <h1><?php echo $info['period']; ?> Period</h1>

        <p><strong>Time:</strong> <?php echo $info['time']; ?></p>

        <p><strong>Land Animals:</strong> <?php echo $info['land_animals']; ?></p>

        <p><strong>Marine Animals:</strong> <?php echo $info['marine_animals']; ?></p>

        <p><strong>Avian Animals:</strong> <?php echo $info['avian_animals']; ?></p>

        <p><strong>Plant Life:</strong> <?php echo $info['plant_life']; ?></p>

    <?php else: ?>

        <h1>Period Not Found</h1>
        <p>Sorry, no information was found for this geological period.</p>

    <?php endif; ?>

    <p>
        <a href="http://localhost:8080/index.php/Dinosaurs/periods">
            Back to Periods
        </a>
    </p>

</body>
</html>