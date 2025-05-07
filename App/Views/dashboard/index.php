<?php
$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    header('Location: /?route=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>

    <h1>User Information</h1>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($currentUser->username); ?></p>
    <p><strong>User ID:</strong> <?php echo htmlspecialchars($currentUser->id); ?></p>
    <p><strong>AES Key:</strong> <?php echo base64_encode($_SESSION['aes_key']); ?></p>
    <form method="POST" class="logout-form">
        <input type="hidden" name="action" value="logout">
        <button type="submit">Logout</button>
    </form>
    <p><a href="/?route=change-password">Change Password</a></p>
</body>

</html>