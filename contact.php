<?php
session_start();
require_once 'connection.php';

$loggedIn   = isset($_SESSION['CustomerID']);
$customerID = $loggedIn ? $_SESSION['CustomerID'] : null;
$prefillName  = '';
$prefillEmail = '';

if ($loggedIn) {
    $conn  = OpenCon();
    $fetch = $conn->prepare("SELECT FirstName, LastName, Email FROM customers WHERE CustomerID = ?");
    $fetch->bind_param("i", $customerID);
    $fetch->execute();
    $fetch->bind_result($fn, $ln, $em);
    $fetch->fetch();
    $fetch->close();
    CloseCon($conn);
    $prefillName  = trim($fn . ' ' . $ln);
    $prefillEmail = $em;
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($name === '') {
        $error = 'Please enter your name.';
    } elseif ($comment === '') {
        $error = 'Please enter a comment or message.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating.';
    } else {
        $conn = OpenCon();
        $stmt = $conn->prepare(
            "INSERT INTO feedback (CustomerID, Name, Email, Rating, Comment)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issis", $customerID, $name, $email, $rating, $comment);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = 'Submission failed. Please try again.';
        }

        $stmt->close();
        CloseCon($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php if ($loggedIn): ?>
        <?php include 'navbar.php'; ?>
    <?php endif; ?>

    <div class="contact-wrap">

        <?php if ($success): ?>
            <div class="contact-success">
                <div class="contact-success-icon">&#10003;</div>
                <h2>Thank You!</h2>
                <p>Your feedback has been submitted. We appreciate you taking the time to reach out.</p>
                <?php if ($loggedIn): ?>
                    <a href="dashbaord.php" class="btn-back-home">Back to Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn-back-home">Log In</a>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <div class="contact-header">
                <h1>Contact Us</h1>
                <p>Have feedback or a question? We'd love to hear from you.</p>
            </div>

            <div class="contact-body">

                <?php if ($error !== ''): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="contact.php">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name"
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? $prefillName); ?>"
                                   placeholder="Your name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="optional">(optional)</span></label>
                            <input type="email" id="email" name="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? $prefillEmail); ?>"
                                   placeholder="you@example.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Rating</label>
                        <div class="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--):
                                $checked = (isset($_POST['rating']) && (int)$_POST['rating'] === $i) ? 'checked' : '';
                            ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="rating"
                                       value="<?php echo $i; ?>" <?php echo $checked; ?>>
                                <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?>">&#9733;</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comment">Message</label>
                        <textarea id="comment" name="comment" rows="5"
                                  placeholder="Tell us about your experience or ask us anything..."><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Send Feedback</button>

                </form>
            </div>

        <?php endif; ?>
    </div>

</body>
</html>
