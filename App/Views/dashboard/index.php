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

    <div class="password-generator-section">
        <h2>Password Generator</h2>
        <form method="POST" action="/?route=dashboard" class="password-generator-form">
            <input type="hidden" name="action" value="generate_password">
            
            <div class="form-group">
                <label for="length">Password Length:</label>
                <input type="number" id="length" name="length" min="8" max="64" value="12" required>
            </div>

            <div class="form-group">
                <label for="uppercase">Uppercase Letters:</label>
                <input type="number" id="uppercase" name="uppercase" min="0" max="26" value="3">
            </div>

            <div class="form-group">
                <label for="lowercase">Lowercase Letters:</label>
                <input type="number" id="lowercase" name="lowercase" min="0" max="26" value="2">
            </div>

            <div class="form-group">
                <label for="numbers">Numbers:</label>
                <input type="number" id="numbers" name="numbers" min="0" max="10" value="5">
            </div>

            <div class="form-group">
                <label for="special">Special Characters:</label>
                <input type="number" id="special" name="special" min="0" max="20" value="2">
            </div>

            <button type="submit">Generate Password</button>
        </form>

        <?php if (isset($_POST['action']) && $_POST['action'] === 'generate_password'): ?>
            <?php
            $generator = new \App\Classes\Security\PasswordGenerator();
            $password = $generator->generate(
                (int)$_POST['length'],
                (int)$_POST['uppercase'],
                (int)$_POST['lowercase'],
                (int)$_POST['numbers'],
                (int)$_POST['special']
            );
            ?>
            <div class="generated-password">
                <h3>Generated Password:</h3>
                <p class="password"><?php echo htmlspecialchars($password); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>