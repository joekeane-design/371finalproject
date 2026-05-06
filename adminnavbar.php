<?php
// Author Joe Keane
if (!isset($_SESSION['AdminID'])) {
    header("Location: adminlogin.php");
    exit();
}
?>
<nav>
    <div class="nav-inner">
        <span class="nav-brand">Fargo Green Lawn Admin</span>
        <ul class="nav-links">
            <li><a href="admindashboard.php">Dashboard</a></li>
            <li><a href="adminservices.php">Services</a></li>
            <li><a href="adminupcomingbookings.php">Upcoming Bookings</a></li>
            <li><a href="adminbookinghistory.php">Past Bookings</a></li>
        </ul>
        <a href="logout.php" class="nav-logout">Logout</a>
    </div>
</nav>
