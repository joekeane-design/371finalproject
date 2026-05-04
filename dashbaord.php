<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    </head>
<body>
    <navbar.php></navbar.php>
    <h1>Hello, <?php echo htmlspecialchars($_SESSION['FirstName']); ?>!</h1>
   # cards that will show upcoming bookings, types of services.
</body>
</html>