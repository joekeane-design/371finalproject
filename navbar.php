<?php
if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}
?>
<nav>
    <div class="nav-inner">
        <span class="nav-brand">GreenScape</span>
        <ul class="nav-links">
            <li><a href="dashbaord.php">Dashboard</a></li>
            <li><a href="services.php">Book</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
        <a href="logout.php" class="nav-logout">Logout</a>
    </div>
</nav>
