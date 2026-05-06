<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}

$conn = OpenCon();
$stmt = $conn->prepare("SELECT RequestID, YardSize, Notes, FinalEstimate, Status FROM service_requests WHERE CustomerID = ? ORDER BY RequestID DESC");
$stmt->bind_param("i", $_SESSION['CustomerID']);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <h1>Hello, <?php echo htmlspecialchars($_SESSION['FirstName']); ?>!</h1>
    <h3>You Upcoming service requests:</h3>
    <?php if (empty($requests)): ?>
        <p class="no-services">No service requests found.</p>
    <?php else: ?>
        <div class="services-grid">
            <?php foreach ($requests as $request): ?>
                <div class="service-card">
                    <h2>Request #<?php echo htmlspecialchars($request['RequestID']); ?></h2>
                    <p class="description"><?php echo htmlspecialchars($request['Notes']); ?></p>
                    <div class="service-meta">
                        <span>Yard Size: <?php echo htmlspecialchars($request['YardSize']); ?></span>
                        <span>Estimate: $<?php echo number_format($request['FinalEstimate'], 2); ?></span>
                        <span class="service-status">Status: <?php echo htmlspecialchars($request['Status']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>
