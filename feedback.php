<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['AdminID'])) {
    header("Location: adminlogin.php");
    exit();
}

$conn = OpenCon();

$result = $conn->query("
    SELECT
        f.FeedbackID,
        f.Name,
        f.Email,
        f.Rating,
        f.Comment,
        f.CustomerID
    FROM feedback f
    ORDER BY f.FeedbackID DESC
") or die($conn->error);

CloseCon($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Feedback</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include 'adminnavbar.php'; ?>

<h1>Customer Feedback</h1>

<?php if ($result->num_rows === 0): ?>
    <p class="empty">No feedback submissions yet.</p>
<?php else: ?>

<div class="feedback-grid">
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="feedback-card">
            <div class="feedback-card-header">
                <div class="feedback-meta">
                    <span class="feedback-name"><?php echo htmlspecialchars($row['Name']); ?></span>
                    <?php if (!empty($row['Email'])): ?>
                        <span class="feedback-email"><?php echo htmlspecialchars($row['Email']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="feedback-stars">
                    <?php
                    $rating = (int)$row['Rating'];
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $rating
                            ? '<span class="star filled">&#9733;</span>'
                            : '<span class="star">&#9733;</span>';
                    }
                    ?>
                </div>
            </div>
            <p class="feedback-comment"><?php echo htmlspecialchars($row['Comment']); ?></p>
            <div class="feedback-card-footer">
                <span class="feedback-id">Feedback #<?php echo $row['FeedbackID']; ?></span>
                <?php if ($row['CustomerID']): ?>
                    <span class="feedback-badge registered">Registered Customer</span>
                <?php else: ?>
                    <span class="feedback-badge guest">Guest</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php endif; ?>

</body>
</html>
