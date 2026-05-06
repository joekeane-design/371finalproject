<?php
session_start();

if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['last_booking'])) {
    header("Location: dashbaord.php");
    exit();
}

$b = $_SESSION['last_booking'];
unset($_SESSION['last_booking']);

$formattedDate = date("F j, Y", strtotime($b['requestedDate']));
$formattedTime = date("g:i A", strtotime($b['bookingTime']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="confirmation-wrap">

        <div class="confirm-header">
            <div class="checkmark">&#10003;</div>
            <h1>Booking Submitted!</h1>
            <p>Your request has been received and is pending confirmation.</p>
        </div>

        <div class="confirm-body">

            <div style="text-align:center;">
                <span class="booking-id-badge">Booking #<?php echo $b['bookingID']; ?></span>
            </div>

            <div class="notice-box">
                <strong>What happens next?</strong> Our team will review your request and reach out shortly to confirm your appointment. You can track the status of your booking on your dashboard.
            </div>

            <!-- Services -->
            <div class="confirm-section">
                <div class="confirm-section-title">Services Requested</div>
                <ul class="services-list">
                    <?php foreach ($b['services'] as $svc): ?>
                        <li><?php echo htmlspecialchars($svc); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Appointment details -->
            <div class="confirm-section">
                <div class="confirm-section-title">Appointment Details</div>
                <div class="detail-row">
                    <span class="detail-label">Preferred Date</span>
                    <span class="detail-value"><?php echo htmlspecialchars($formattedDate); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Preferred Time</span>
                    <span class="detail-value"><?php echo htmlspecialchars($formattedTime); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Yard Size</span>
                    <span class="detail-value"><?php echo htmlspecialchars($b['yardSize']); ?></span>
                </div>
                <?php if (!empty($b['notes'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Notes</span>
                    <span class="detail-value"><?php echo htmlspecialchars($b['notes']); ?></span>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color:var(--orange);font-weight:600;">Pending Confirmation</span>
                </div>
            </div>

            <!-- Pricing -->
            <div class="confirm-section">
                <div class="confirm-section-title">Pricing Estimate</div>
                <div class="pricing-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($b['baseTotal'], 2); ?></span>
                </div>
                <?php if ($b['discountPct'] > 0): ?>
                <div class="pricing-row discount">
                    <span>Discount (<?php echo $b['discountPct']; ?>% — 3+ services)</span>
                    <span>&minus;$<?php echo number_format($b['baseTotal'] * $b['discountPct'] / 100, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="pricing-row total">
                    <span>Estimated Total</span>
                    <span>$<?php echo number_format($b['finalEstimate'], 2); ?></span>
                </div>
            </div>

            <div class="confirm-actions">
                <a href="dashbaord.php" class="btn-dashboard">Go to Dashboard</a>
                <a href="book.php" class="btn-book-another">Book Another Service</a>
            </div>

        </div>
    </div>

</body>
</html>
