//Author - Joe Keane
<?php
if (!isset($_SESSION['AdminID'])) {
    header("Location: adminlogin.php");
    exit();
}
?>
<nav>
    <ul>
        <li><a href="admindashboard.php">Dashboard</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>
