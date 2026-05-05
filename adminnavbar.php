<?php
// Author Joe Keane
if (!isset($_SESSION['AdminID'])) {
    header("Location: adminlogin.php");
    exit();
}
?>
<nav>
    <ul>
        <li><a href="admindashboard.php">Dashboard</a></li>
        <li><a href="adminservices.php">Services</a></li>
        <li><a href="adminbookinghistory.php">Past Bookings</a></li>
        <li><a href="adminupcomingbookings.php">Upcoming Bookings</a></li>
        <li><a href="adminlogin.php?logout=1">Logout</a></li>
    </ul>
</nav>
