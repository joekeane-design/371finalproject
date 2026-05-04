<?php
session_start();
require_once 'connection.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['FirstName'] ?? '');
    $lastName  = trim($_POST['LastName']  ?? '');
    $email     = trim($_POST['Email']      ?? '');
    $phone     = trim($_POST['Phone']      ?? '');
    $address   = trim($_POST['Address']    ?? '');
    $password  = $_POST['password']        ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
        $error = 'First name, last name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $conn = OpenCon();

        // Check if email is already registered
        $check = $conn->prepare("SELECT CustomerID FROM customers WHERE Email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO customers (FirstName, LastName, Email, PasswordHash, Phone) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssss", $firstName, $lastName, $email, $hash, $phone);

            if ($stmt->execute()) {
                $customerID = $conn->insert_id;
                $_SESSION['CustomerID']   = $customerID;
                $_SESSION['FirstName'] = $firstName;
                header("Location: dashbaord.php");
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }

            $stmt->close();
        }

        $check->close();
        CloseCon($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f0f4f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 24px 0;
        }

        .register-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 440px;
        }

        h2 {
            text-align: center;
            color: #2e7d32;
            margin-bottom: 24px;
        }

        .form-row {
            display: flex;
            gap: 14px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #2e7d32;
        }

        .btn-register {
            width: 100%;
            padding: 11px;
            background-color: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 8px;
        }

        .btn-register:hover {
            background-color: #1b5e20;
        }

        .error-msg {
            background-color: #fdecea;
            color: #c62828;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .login-link {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
            color: #555;
        }

        .login-link a {
            color: #2e7d32;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .optional {
            font-weight: normal;
            color: #888;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Create Account</h2>

        <?php if ($error !== ''): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="FirstName">First Name</label>
                    <input type="text" id="FirstName" name="FirstName"
                           value="<?php echo htmlspecialchars($_POST['FirstName'] ?? ''); ?>"
                           placeholder="John" required>
                </div>
                <div class="form-group">
                    <label for="LastName">Last Name</label>
                    <input type="text" id="LastName" name="LastName"
                           value="<?php echo htmlspecialchars($_POST['LastName'] ?? ''); ?>"
                           placeholder="Smith" required>
                </div>
            </div>

            <div class="form-group">
                <label for="Email">Email Address</label>
                <input type="email" id="Email" name="Email"
                       value="<?php echo htmlspecialchars($_POST['Email'] ?? ''); ?>"
                       placeholder="you@example.com" required>
            </div>

            <div class="form-group">
                <label for="Phone">Phone <span class="optional">(optional)</span></label>
                <input type="tel" id="Phone" name="Phone"
                       value="<?php echo htmlspecialchars($_POST['Phone'] ?? ''); ?>"
                       placeholder="701-555-0100">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="At least 6 characters" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="Re-enter your password" required>
            </div>

            <button type="submit" class="btn-register">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Log in here</a>
        </div>
    </div>
</body>
</html>
