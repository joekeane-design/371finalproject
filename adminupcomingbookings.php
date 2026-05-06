<?php
// Author Rivers Martin
session_start();
require_once 'connection.php';

if (!isset($_SESSION['AdminID'])) {
    header("Location: adminlogin.php");
    exit();
}

$conn = OpenCon();

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
    WHERE b.RequestedDate >= CURDATE()
      AND b.Status != 'Completed'
    GROUP BY b.BookingID
    ORDER BY b.RequestedDate ASC, b.BookingTime ASC
") or die("Query failed: " . $conn->error);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upcoming Bookings</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h1>Upcoming Bookings</h1>

<?php include 'adminnavbar.php'; ?>

<?php if ($result->num_rows === 0): ?>
    <p class="empty">No upcoming bookings found.</p>
<?php else: ?>

<div class="table-wrap" style="padding:0 20px;">
<table>
    <tr>
        <th>Booking ID</th>
        <th>Customer</th>
        <th>Email</th>
        <th>Services</th>
        <th>Date</th>
        <th>Time</th>
        <th>Price</th>
        <th>Status</th>
        <th>Provider</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['BookingID']; ?></td>
            <td><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></td>
            <td><?php echo htmlspecialchars($row['Email']); ?></td>
            <td><?php echo htmlspecialchars($row['Services'] ?? 'No services listed'); ?></td>
            <td><?php echo htmlspecialchars($row['RequestedDate']); ?></td>
            <td><?php echo htmlspecialchars($row['BookingTime']); ?></td>
            <td>$<?php echo number_format($row['FinalPrice'], 2); ?></td>
            <td><?php echo htmlspecialchars($row['Status']); ?></td>
            <td><?php echo htmlspecialchars($row['ServiceProvider'] ?? 'Not assigned'); ?></td>
        </tr>
    <?php endwhile; ?>
</table>
</div>

<?php endif; ?>

</body>
</html>

<?php CloseCon($conn); ?>