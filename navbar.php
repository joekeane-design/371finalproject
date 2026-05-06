<?php
if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}
?>
<nav>
    <div class="nav-inner">
        <span class="nav-brand">Fargo Green Lawn</span>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="book.php">Book</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
        <a href="logout.php" class="nav-logout">Logout</a>
    </div>
</nav>
