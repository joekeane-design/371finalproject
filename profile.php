<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['CustomerID'];
$error      = '';
$success    = '';

$conn = OpenCon();

// Load current profile
$fetch = $conn->prepare("SELECT FirstName, LastName, Email, Phone, Address FROM customers WHERE CustomerID = ?");
$fetch->bind_param("i", $customerID);
$fetch->execute();
$fetch->bind_result($dbFirst, $dbLast, $dbEmail, $dbPhone, $dbAddress);
$fetch->fetch();
$fetch->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['FirstName'] ?? '');
    $lastName  = trim($_POST['LastName']  ?? '');
    $email     = trim($_POST['Email']     ?? '');
    $phone     = trim($_POST['Phone']     ?? '');
    $address   = trim($_POST['Address']   ?? '');
    $newPass   = $_POST['new_password']    ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($firstName === '' || $lastName === '' || $email === '') {
        $error = 'First name, last name, and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($newPass !== '' && strlen($newPass) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($newPass !== '' && $newPass !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        // Check email uniqueness if changed
        if ($email !== $dbEmail) {
            $chk = $conn->prepare("SELECT CustomerID FROM customers WHERE Email = ? AND CustomerID != ?");
            $chk->bind_param("si", $email, $customerID);
            $chk->execute();
            $chk->store_result();
            $taken = $chk->num_rows > 0;
            $chk->close();

            if ($taken) {
                $error = 'That email address is already in use by another account.';
            }
        }

        if ($error === '') {
            if ($newPass !== '') {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "UPDATE customers SET FirstName=?, LastName=?, Email=?, Phone=?, Address=?, PasswordHash=? WHERE CustomerID=?"
                );
                $stmt->bind_param("ssssssi", $firstName, $lastName, $email, $phone, $address, $hash, $customerID);
            } else {
                $stmt = $conn->prepare(
                    "UPDATE customers SET FirstName=?, LastName=?, Email=?, Phone=?, Address=? WHERE CustomerID=?"
                );
                $stmt->bind_param("sssssi", $firstName, $lastName, $email, $phone, $address, $customerID);
            }

            if ($stmt->execute()) {
                $_SESSION['FirstName'] = $firstName;
                $dbFirst   = $firstName;
                $dbLast    = $lastName;
                $dbEmail   = $email;
                $dbPhone   = $phone;
                $dbAddress = $address;
                $success   = 'Your profile has been updated successfully.';
            } else {
                $error = 'Update failed. Please try again.';
            }
            $stmt->close();
        }
    }
}

CloseCon($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <a href="dashbaord.php" class="back-link">&larr; Back to Dashboard</a>

    <div class="profile-container">
        <h2>My Profile</h2>

        <?php if ($error !== ''): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="profile.php">

            <div class="section-title">Personal Information</div>

            <div class="form-row">
                <div class="form-group">
                    <label for="FirstName">First Name</label>
                    <input type="text" id="FirstName" name="FirstName"
                           value="<?php echo htmlspecialchars($dbFirst); ?>" required>
                </div>
                <div class="form-group">
                    <label for="LastName">Last Name</label>
                    <input type="text" id="LastName" name="LastName"
                           value="<?php echo htmlspecialchars($dbLast); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="Email">Email Address</label>
                <input type="email" id="Email" name="Email"
                       value="<?php echo htmlspecialchars($dbEmail); ?>" required>
            </div>

            <div class="form-group">
                <label for="Phone">Phone <span class="optional">(optional)</span></label>
                <input type="tel" id="Phone" name="Phone"
                       value="<?php echo htmlspecialchars($dbPhone ?? ''); ?>"
                       placeholder="701-555-0100">
            </div>

            <div class="form-group">
                <label for="Address">Address <span class="optional">(optional)</span></label>
                <input type="text" id="Address" name="Address"
                       value="<?php echo htmlspecialchars($dbAddress ?? ''); ?>"
                       placeholder="123 Main St">
            </div>

            <div class="section-title">Change Password</div>

            <div class="form-group">
                <label for="new_password">New Password <span class="optional">(optional)</span></label>
                <input type="password" id="new_password" name="new_password"
                       placeholder="Leave blank to keep current password">
                <p class="pass-hint">At least 6 characters if changing.</p>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="Re-enter new password">
            </div>

            <button type="submit" class="btn-save">Save Changes</button>
        </form>
    </div>

</body>
</html>
