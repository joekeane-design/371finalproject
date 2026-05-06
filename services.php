<?php
session_start();
require_once 'connection.php';

$conn     = OpenCon();
$result   = $conn->query("SELECT Name, Description, BasePrice, BaseDuration FROM services ORDER BY Name ASC");
$services = $result->fetch_all(MYSQLI_ASSOC);
CloseCon($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <a href="dashbaord.php" class="back-link">&larr; Back to Dashboard</a>

    <h1>Our Services</h1>

    <?php if (empty($services)): ?>
        <p class="no-services">No services available at this time.</p>
    <?php else: ?>
        <div class="services-grid">
            <?php foreach ($services as $service): ?>
                <div class="service-card">
                    <h2><?php echo htmlspecialchars($service['Name']); ?></h2>
                    <p class="description"><?php echo htmlspecialchars($service['Description']); ?></p>
                    <div class="service-meta">
                        <span class="service-price">$<?php echo number_format($service['BasePrice'], 2); ?></span>
                        <span class="service-duration"><?php echo htmlspecialchars($service['BaseDuration']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>
