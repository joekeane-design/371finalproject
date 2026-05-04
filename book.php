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
                $success = 'Your booking has been submitted! We will be in touch to confirm.';
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
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background-color: #f0f4f0;
            min-height: 100vh;
            padding: 32px 20px;
        }

        h1 {
            text-align: center;
            color: #2e7d32;
            margin-bottom: 8px;
            font-size: 26px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-bottom: 20px;
            color: #2e7d32;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover { text-decoration: underline; }

        .layout {
            display: flex;
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
            align-items: flex-start;
        }

        /* ── Services panel ── */
        .services-panel {
            flex: 1 1 0;
            min-width: 0;
        }

        .services-panel h2,
        .cart-panel h2 {
            color: #2e7d32;
            font-size: 18px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #c8e6c9;
        }

        .service-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 6px rgba(0,0,0,.08);
            padding: 16px 18px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .svc-info h3 { font-size: 15px; color: #222; margin-bottom: 4px; }
        .svc-meta    { font-size: 13px; color: #777; }
        .svc-price   { font-weight: bold; color: #2e7d32; font-size: 15px; margin-right: 6px; }

        .btn-add {
            white-space: nowrap;
            padding: 7px 16px;
            background-color: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-add:hover { background-color: #1b5e20; }
        .btn-add:disabled {
            background-color: #a5d6a7;
            cursor: default;
        }

        /* ── Cart panel ── */
        .cart-panel {
            width: 340px;
            flex-shrink: 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,.1);
            padding: 24px;
        }

        .cart-empty {
            text-align: center;
            color: #aaa;
            font-size: 14px;
            padding: 20px 0;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .cart-item-name { color: #333; flex: 1; }
        .cart-item-price { color: #2e7d32; font-weight: bold; margin: 0 12px; }

        .btn-remove {
            background: none;
            border: none;
            color: #c62828;
            font-size: 18px;
            cursor: pointer;
            line-height: 1;
            padding: 0 2px;
        }
        .btn-remove:hover { color: #b71c1c; }

        .cart-totals {
            margin-top: 14px;
            font-size: 14px;
        }
        .cart-totals .row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            color: #555;
        }
        .cart-totals .row.discount { color: #e65100; }
        .cart-totals .row.final {
            font-weight: bold;
            font-size: 16px;
            color: #2e7d32;
            border-top: 2px solid #c8e6c9;
            margin-top: 6px;
            padding-top: 8px;
        }

        .discount-badge {
            display: inline-block;
            background-color: #fff3e0;
            color: #e65100;
            border: 1px solid #ffcc80;
            border-radius: 12px;
            padding: 3px 10px;
            font-size: 12px;
            margin-bottom: 12px;
        }

        .form-group { margin-top: 16px; }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 13px;
            color: #333;
        }

        select, textarea, input[type="date"], input[type="time"] {
            width: 100%;
            padding: 9px 11px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        select:focus, textarea:focus, input[type="date"]:focus, input[type="time"]:focus {
            outline: none;
            border-color: #2e7d32;
        }
        textarea { resize: vertical; min-height: 80px; }

        .optional { font-weight: normal; color: #999; font-size: 11px; }

        .btn-submit {
            width: 100%;
            margin-top: 16px;
            padding: 11px;
            background-color: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
        }
        .btn-submit:hover { background-color: #1b5e20; }
        .btn-submit:disabled { background-color: #a5d6a7; cursor: default; }

        .btn-clear {
            width: 100%;
            margin-top: 8px;
            padding: 7px;
            background: none;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 13px;
            color: #777;
            cursor: pointer;
        }
        .btn-clear:hover { background-color: #fafafa; }

        .error-msg {
            background-color: #fdecea;
            color: #c62828;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .success-msg {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
            border-radius: 4px;
            padding: 14px;
            font-size: 14px;
            text-align: center;
        }

        @media (max-width: 700px) {
            .layout { flex-direction: column; }
            .cart-panel { width: 100%; }
        }
    </style>
</head>
<body>

    <a href="dashbaord.php" class="back-link">&larr; Back to Dashboard</a>
    <h1>Book a Service</h1>

    <?php if ($success !== ''): ?>
        <div style="max-width:500px;margin:24px auto;">
            <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
        </div>
    <?php else: ?>

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

    <?php endif; ?>

</body>
</html>
