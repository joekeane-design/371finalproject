<?php
if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}
?>
<nav>
    <ul>
        <li><a href="dashbaord.php">Dashboard</a></li>
        <li><a href="services.php">Book</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>
