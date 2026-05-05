<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['AdminID'])) {
    header("Location: adminlogin.php");
    exit();
}

$conn = OpenCon();
$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['Name'] ?? '');
        $description = trim($_POST['Description'] ?? '');
        $price = trim($_POST['BasePrice'] ?? '');
        $duration = trim($_POST['BaseDuration'] ?? '');

        if ($name === '' || $description === '' || $price === '' || $duration === '') {
            $error = 'All fields are required.';
        } elseif (!is_numeric($price) || $price < 0) {
            $error = 'Price must be a valid number.';
        } else {
            $stmt = $conn->prepare("INSERT INTO services (Name, Description, BasePrice, BaseDuration) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $name, $description, $price, $duration);

            if ($stmt->execute()) {
                $message = 'Service added successfully.';
            } else {
                $error = 'Error adding service.';
            }

            $stmt->close();
        }
    }

    if ($action === 'update') {
        $serviceID = (int)($_POST['ServiceID'] ?? 0);
        $name = trim($_POST['Name'] ?? '');
        $description = trim($_POST['Description'] ?? '');
        $price = trim($_POST['BasePrice'] ?? '');
        $duration = trim($_POST['BaseDuration'] ?? '');

        if ($serviceID <= 0 || $name === '' || $description === '' || $price === '' || $duration === '') {
            $error = 'All fields are required.';
        } elseif (!is_numeric($price) || $price < 0) {
            $error = 'Price must be a valid number.';
        } else {
            $stmt = $conn->prepare("UPDATE services SET Name = ?, Description = ?, BasePrice = ?, BaseDuration = ? WHERE ServiceID = ?");
            $stmt->bind_param("ssdsi", $name, $description, $price, $duration, $serviceID);

            if ($stmt->execute()) {
                $message = 'Service updated successfully.';
            } else {
                $error = 'Error updating service.';
            }

            $stmt->close();
        }
    }

    if ($action === 'delete') {
        $serviceID = (int)($_POST['ServiceID'] ?? 0);

        if ($serviceID > 0) {
            $stmt = $conn->prepare("DELETE FROM services WHERE ServiceID = ?");
            $stmt->bind_param("i", $serviceID);

            if ($stmt->execute()) {
                $message = 'Service deleted successfully.';
            } else {
                $error = 'Could not delete service. It may already be connected to a booking.';
            }

            $stmt->close();
        }
    }
}

$result = $conn->query("SELECT ServiceID, Name, Description, BasePrice, BaseDuration FROM services ORDER BY Name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Services</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h1>Manage Services</h1>

<nav>
    <a href="admindashboard.php">Dashboard</a>
    <a href="adminservices.php">Services</a>
    <a href="adminupcomingbookings.php">Upcoming Bookings</a>
    <a href="adminbookinghistory.php">History</a>
    <a href="adminlogin.php?logout=1">Logout</a>
</nav>

<?php if ($message !== ''): ?>
    <p class="message"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<h2>Add New Service</h2>

<form method="POST" action="adminservices.php">
    <input type="hidden" name="action" value="add">

    <label>Service Name</label><br>
    <input type="text" name="Name" required><br><br>

    <label>Description</label><br>
    <textarea name="Description" required></textarea><br><br>

    <label>Base Price</label><br>
    <input type="number" step="0.01" name="BasePrice" required><br><br>

    <label>Base Duration</label><br>
    <input type="text" name="BaseDuration" placeholder="Example: 2 hours" required><br><br>

    <button type="submit">Add Service</button>
</form>

<h2>Existing Services</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Description</th>
        <th>Price</th>
        <th>Duration</th>
        <th>Update</th>
        <th>Delete</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <?php $formID = 'serviceForm' . $row['ServiceID']; ?>

        <tr>
            <td><?php echo $row['ServiceID']; ?></td>

            <td>
                <input type="text" name="Name" form="<?php echo $formID; ?>"
                       value="<?php echo htmlspecialchars($row['Name']); ?>" required>
            </td>

            <td>
                <textarea name="Description" form="<?php echo $formID; ?>" required><?php echo htmlspecialchars($row['Description']); ?></textarea>
            </td>

            <td>
                <input type="number" step="0.01" name="BasePrice" form="<?php echo $formID; ?>"
                       value="<?php echo htmlspecialchars($row['BasePrice']); ?>" required>
            </td>

            <td>
                <input type="text" name="BaseDuration" form="<?php echo $formID; ?>"
                       value="<?php echo htmlspecialchars($row['BaseDuration']); ?>" required>
            </td>

            <td>
                <form id="<?php echo $formID; ?>" method="POST" action="adminservices.php">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="ServiceID" value="<?php echo $row['ServiceID']; ?>">
                    <button type="submit">Save</button>
                </form>
            </td>

            <td>
                <form method="POST" action="adminservices.php" onsubmit="return confirm('Delete this service?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="ServiceID" value="<?php echo $row['ServiceID']; ?>">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>

<?php CloseCon($conn); ?>
