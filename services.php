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
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f0f4f0;
            min-height: 100vh;
            padding: 40px 20px;
        }

        h1 {
            text-align: center;
            color: #2e7d32;
            margin-bottom: 32px;
            font-size: 28px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .service-card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .service-card h2 {
            color: #2e7d32;
            font-size: 18px;
        }

        .service-card .description {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
            flex-grow: 1;
        }

        .service-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            border-top: 1px solid #e8f5e9;
            padding-top: 12px;
        }

        .service-price {
            font-size: 20px;
            font-weight: bold;
            color: #2e7d32;
        }

        .service-duration {
            font-size: 13px;
            color: #777;
            background-color: #f1f8e9;
            padding: 4px 10px;
            border-radius: 12px;
        }

        .no-services {
            text-align: center;
            color: #777;
            font-size: 16px;
            margin-top: 60px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-bottom: 24px;
            color: #2e7d32;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
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
