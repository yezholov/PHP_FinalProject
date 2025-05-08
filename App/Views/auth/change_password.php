<?php
// Ensure user is logged in, otherwise redirect to login
if (!isset($auth) || !$auth->isLoggedIn()) {
    header('Location: /?route=login');
    exit;
}
$currentUser = $auth->getCurrentUser();

// Variable to hold error messages
$error = $_SESSION['change_password_error'] ?? null;
$success = $_SESSION['change_password_success'] ?? null;
unset($_SESSION['change_password_error']); // Clear after displaying
unset($_SESSION['change_password_success']); // Clear after displaying

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_back" />
</head>
<body class="change-password auth">
    <div class="auth-container">
        <div class="back-to-dashboard-link-container"><a href="/?route=dashboard" class="back-to-dashboard-link"><span class="material-symbols-outlined">
arrow_back
</span> Back to Dashboard</a></div>
        <h2 class="change-password-title">Change Your Password</h2>

        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form method="POST" action="/?route=change-password">
            <input type="hidden" name="action" value="change-password">
            
            <div class="form-group">
                <label for="old_password">Current Password:</label>
                <input type="password" id="old_password" name="old_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            
            <button class="change-password-button" type="submit">Change Password</button>
        </form>
       
    </div>
</body>
</html> 