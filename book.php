<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['CustomerID'];
$error      = '';
$success    = '';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$conn      = OpenCon();
$svcResult = $conn->query("SELECT ServiceID, Name, BasePrice, BaseDuration FROM services ORDER BY Name ASC");
$services  = $svcResult->fetch_all(MYSQLI_ASSOC);

// Index services by ID for quick lookups
$serviceMap = [];
foreach ($services as $svc) {
    $serviceMap[$svc['ServiceID']] = $svc;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $id = (int)$_POST['ServiceID'];
        if (isset($serviceMap[$id]) && !in_array($id, $_SESSION['cart'])) {
            $_SESSION['cart'][] = $id;
        }

    } elseif ($action === 'remove') {
        $id = (int)$_POST['ServiceID'];
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($v) => $v !== $id));

    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];

    } elseif ($action === 'submit') {
        $yardSize      = trim($_POST['YardSize']       ?? '');
        $notes         = trim($_POST['Notes']          ?? '');
        $requestedDate = trim($_POST['RequestedDate']  ?? '');
        $bookingTime   = trim($_POST['BookingTime']    ?? '');

        if (empty($_SESSION['cart'])) {
            $error = 'Your cart is empty. Please add at least one service.';
        } elseif ($yardSize === '') {
            $error = 'Please select a yard size.';
        } elseif ($requestedDate === '') {
            $error = 'Please select a preferred date.';
        } elseif ($bookingTime === '') {
            $error = 'Please select a preferred time.';
        } else {
            $cartIDs   = $_SESSION['cart'];
            $baseTotal = 0.0;
            foreach ($cartIDs as $id) {
                $baseTotal += (float)$serviceMap[$id]['BasePrice'];
            }

            $discountPct   = count($cartIDs) >= 3 ? 10.0 : 0.0;
            $finalEstimate = $baseTotal * (1 - $discountPct / 100);

            $reqStmt = $conn->prepare(
                "INSERT INTO service_requests (CustomerID, YardSize, Notes, BaseTotal, DiscountPercent, FinalEstimate, Status)
                 VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
            );
            $reqStmt->bind_param("issddd", $customerID, $yardSize, $notes, $baseTotal, $discountPct, $finalEstimate);

            if ($reqStmt->execute()) {
                $requestID = $conn->insert_id;

                // Link services to the request
                $itemStmt = $conn->prepare(
                    "INSERT INTO service_request_items (RequestID, ServiceID) VALUES (?, ?)"
                );
                foreach ($cartIDs as $svcID) {
                    $itemStmt->bind_param("ii", $requestID, $svcID);
                    $itemStmt->execute();
                }
                $itemStmt->close();

                // Create the booking record
                $bookStmt = $conn->prepare(
                    "INSERT INTO bookings (CustomerID, RequestID, FinalPrice, RequestedDate, BookingTime, Status)
                     VALUES (?, ?, ?, ?, ?, 'Pending')"
                );
                $bookStmt->bind_param("iidss", $customerID, $requestID, $finalEstimate, $requestedDate, $bookingTime);
                $bookStmt->execute();
                $bookingID = $conn->insert_id;
                $bookStmt->close();

                // Link services to the booking
                $bkSvcStmt = $conn->prepare(
                    "INSERT INTO booking_services (BookingID, ServiceID) VALUES (?, ?)"
                );
                foreach ($cartIDs as $svcID) {
                    $bkSvcStmt->bind_param("ii", $bookingID, $svcID);
                    $bkSvcStmt->execute();
                }
                $bkSvcStmt->close();

                $_SESSION['cart'] = [];
                $_SESSION['last_booking'] = [
                    'bookingID'      => $bookingID,
                    'requestID'      => $requestID,
                    'services'       => array_map(fn($id) => $serviceMap[$id]['Name'], $cartIDs),
                    'yardSize'       => $yardSize,
                    'notes'          => $notes,
                    'requestedDate'  => $requestedDate,
                    'bookingTime'    => $bookingTime,
                    'baseTotal'      => $baseTotal,
                    'discountPct'    => $discountPct,
                    'finalEstimate'  => $finalEstimate,
                ];
                header("Location: confirmation.php");
                exit();
            } else {
                $error = 'Booking failed. Please try again.';
            }

            $reqStmt->close();
        }
    }
}

CloseCon($conn);

// Compute cart totals for display
$cartTotal    = 0.0;
$cartCount    = count($_SESSION['cart']);
$discountPct  = $cartCount >= 3 ? 10.0 : 0.0;
foreach ($_SESSION['cart'] as $id) {
    if (isset($serviceMap[$id])) {
        $cartTotal += (float)$serviceMap[$id]['BasePrice'];
    }
}
$cartFinal = $cartTotal * (1 - $discountPct / 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Service</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <a href="dashbaord.php" class="back-link">&larr; Back to Dashboard</a>
    <h1>Book a Service</h1>

    <div class="layout">

        <!-- ── Available Services ── -->
        <div class="services-panel">
            <h2>Available Services</h2>

            <?php if ($error !== ''): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php foreach ($services as $svc): ?>
                <?php $inCart = in_array($svc['ServiceID'], $_SESSION['cart']); ?>
                <div class="service-card">
                    <div class="svc-info">
                        <h3><?php echo htmlspecialchars($svc['Name']); ?></h3>
                        <span class="svc-meta"><?php echo htmlspecialchars($svc['BaseDuration']); ?></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="svc-price">$<?php echo number_format($svc['BasePrice'], 2); ?></span>
                        <form method="POST" action="book.php">
                            <input type="hidden" name="action"    value="add">
                            <input type="hidden" name="ServiceID" value="<?php echo $svc['ServiceID']; ?>">
                            <button type="submit" class="btn-add" <?php echo $inCart ? 'disabled' : ''; ?>>
                                <?php echo $inCart ? 'Added' : '+ Add'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Cart ── -->
        <div class="cart-panel">
            <h2>Your Cart</h2>

            <?php if ($cartCount === 0): ?>
                <p class="cart-empty">No services added yet.</p>
            <?php else: ?>

                <?php if ($discountPct > 0): ?>
                    <div class="discount-badge">10% discount applied (3+ services)</div>
                <?php else: ?>
                    <div style="font-size:12px;color:#aaa;margin-bottom:10px;">
                        Add <?php echo 3 - $cartCount; ?> more service<?php echo (3 - $cartCount) !== 1 ? 's' : ''; ?> for a 10% discount
                    </div>
                <?php endif; ?>

                <?php foreach ($_SESSION['cart'] as $id): ?>
                    <?php $s = $serviceMap[$id]; ?>
                    <div class="cart-item">
                        <span class="cart-item-name"><?php echo htmlspecialchars($s['Name']); ?></span>
                        <span class="cart-item-price">$<?php echo number_format($s['BasePrice'], 2); ?></span>
                        <form method="POST" action="book.php" style="display:inline;">
                            <input type="hidden" name="action"    value="remove">
                            <input type="hidden" name="ServiceID" value="<?php echo $id; ?>">
                            <button type="submit" class="btn-remove" title="Remove">&times;</button>
                        </form>
                    </div>
                <?php endforeach; ?>

                <div class="cart-totals">
                    <div class="row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($cartTotal, 2); ?></span>
                    </div>
                    <?php if ($discountPct > 0): ?>
                    <div class="row discount">
                        <span>Discount (10%)</span>
                        <span>&minus;$<?php echo number_format($cartTotal * 0.1, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="row final">
                        <span>Total</span>
                        <span>$<?php echo number_format($cartFinal, 2); ?></span>
                    </div>
                </div>

            <?php endif; ?>

            <!-- Submit form -->
            <form method="POST" action="book.php">
                <input type="hidden" name="action" value="submit">

                <div class="form-group">
                    <label for="YardSize">Yard Size</label>
                    <select id="YardSize" name="YardSize" required>
                        <option value="">-- Select yard size --</option>
                        <option value="Small">Small</option>
                        <option value="Medium">Medium</option>
                        <option value="Large">Large</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="RequestedDate">Preferred Date</label>
                    <input type="date" id="RequestedDate" name="RequestedDate"
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>

                <div class="form-group">
                    <label for="BookingTime">Preferred Time</label>
                    <input type="time" id="BookingTime" name="BookingTime" required>
                </div>

                <div class="form-group">
                    <label for="Notes">Notes <span class="optional">(optional)</span></label>
                    <textarea id="Notes" name="Notes"
                        placeholder="Any special instructions..."></textarea>
                </div>

                <button type="submit" class="btn-submit" <?php echo $cartCount === 0 ? 'disabled' : ''; ?>>
                    Submit Booking Request
                </button>
            </form>

            <?php if ($cartCount > 0): ?>
            <form method="POST" action="book.php">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn-clear">Clear Cart</button>
            </form>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
