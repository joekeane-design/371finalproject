<?php
// Author Rivers Martin
session_start();
require_once 'connection.php';

if (!isset($_SESSION['AdminID'])) {
    header("Location: adminlogin.php");
    exit();
}

$conn = OpenCon();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingID = (int)($_POST['BookingID'] ?? 0);
    $status = trim($_POST['Status'] ?? '');
    $provider = trim($_POST['ServiceProvider'] ?? '');

    $allowedStatuses = ['Pending', 'Confirmed', 'Completed'];

    if ($bookingID > 0 && in_array($status, $allowedStatuses)) {
        $stmt = $conn->prepare("UPDATE bookings SET Status = ?, ServiceProvider = ? WHERE BookingID = ?");
        $stmt->bind_param("ssi", $status, $provider, $bookingID);

        if ($stmt->execute()) {
            $message = 'Booking updated successfully.';
        } else {
            $message = 'Error updating booking.';
        }

        $stmt->close();
    }
}

$totalCustomers   = $conn->query("SELECT COUNT(*) AS total FROM customers") or die($conn->error);
$totalCustomers   = $totalCustomers->fetch_assoc()['total'];
$totalBookings    = $conn->query("SELECT COUNT(*) AS total FROM bookings") or die($conn->error);
$totalBookings    = $totalBookings->fetch_assoc()['total'];
$pendingBookings  = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE Status = 'Pending'") or die($conn->error);
$pendingBookings  = $pendingBookings->fetch_assoc()['total'];
$confirmedBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE Status = 'Confirmed'") or die($conn->error);
$confirmedBookings = $confirmedBookings->fetch_assoc()['total'];
$completedBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE Status = 'Completed'") or die($conn->error);
$completedBookings = $completedBookings->fetch_assoc()['total'];

$result = $conn->query("
    SELECT
        b.BookingID,
        b.FinalPrice,
        b.RequestedDate,
        b.BookingTime,
        b.Status,
        b.ServiceProvider,
        c.FirstName,
        c.LastName,
        c.Email,
        GROUP_CONCAT(s.Name SEPARATOR ', ') AS Services
    FROM bookings b
    JOIN customers c ON b.CustomerID = c.CustomerID
    LEFT JOIN booking_services bs ON b.BookingID = bs.BookingID
    LEFT JOIN services s ON bs.ServiceID = s.ServiceID
    GROUP BY b.BookingID
    ORDER BY b.RequestedDate ASC, b.BookingTime ASC
") or die($conn->error);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include 'adminnavbar.php'; ?>

<h1>Admin Dashboard</h1>

<p style="text-align:center;color:#666;margin-bottom:24px;">Welcome, <?php echo htmlspecialchars($_SESSION['Username']); ?>!</p>

<?php if ($message !== ''): ?>
    <p class="success-msg" style="margin:0 20px 16px;"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>


<div class="summary">
    <div class="card">
        <strong><?php echo $totalCustomers; ?></strong>
        Customers
    </div>

    <div class="card">
        <strong><?php echo $totalBookings; ?></strong>
        Total Bookings
    </div>

    <div class="card">
        <strong><?php echo $pendingBookings; ?></strong>
        Pending
    </div>

    <div class="card">
        <strong><?php echo $confirmedBookings; ?></strong>
        Confirmed
    </div>

    <div class="card">
        <strong><?php echo $completedBookings; ?></strong>
        Completed
    </div>
</div>

<h2 style="padding:0 20px 8px;border-bottom:2px solid #c8e6c9;margin:0 20px 16px;">Manage Bookings</h2>

<div class="table-wrap" style="padding:0 20px;">
<table>
    <tr>
        <th>ID</th>
        <th>Customer</th>
        <th>Email</th>
        <th>Services</th>
        <th>Date</th>
        <th>Time</th>
        <th>Price</th>
        <th>Status</th>
        <th>Provider</th>
        <th>Update</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <?php $formID = 'bookingForm' . $row['BookingID']; ?>

        <tr>
            <td><?php echo $row['BookingID']; ?></td>

            <td><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></td>

            <td><?php echo htmlspecialchars($row['Email']); ?></td>

            <td><?php echo htmlspecialchars($row['Services'] ?? 'No services listed'); ?></td>

            <td><?php echo htmlspecialchars($row['RequestedDate']); ?></td>

            <td><?php echo htmlspecialchars($row['BookingTime']); ?></td>

            <td>$<?php echo number_format($row['FinalPrice'], 2); ?></td>

            <td>
                <select name="Status" form="<?php echo $formID; ?>">
                    <option value="Pending" <?php if ($row['Status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Confirmed" <?php if ($row['Status'] === 'Confirmed') echo 'selected'; ?>>Confirmed</option>
                    <option value="Completed" <?php if ($row['Status'] === 'Completed') echo 'selected'; ?>>Completed</option>
                </select>
            </td>

            <td>
                <input type="text" name="ServiceProvider" form="<?php echo $formID; ?>"
                       value="<?php echo htmlspecialchars($row['ServiceProvider'] ?? ''); ?>">
            </td>

            <td>
                <form id="<?php echo $formID; ?>" method="POST" action="admindashboard.php">
                    <input type="hidden" name="BookingID" value="<?php echo $row['BookingID']; ?>">
                    <button type="submit">Save</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
</div>

</body>
</html>

<?php CloseCon($conn); ?>
