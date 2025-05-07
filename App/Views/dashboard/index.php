<?php
$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    header('Location: /?route=login');
    exit;
}

$passwordManager = new \App\Classes\Password\PasswordManager(new \App\Classes\Database());
$passwords = $passwordManager->getUserPasswords($currentUser->getId());

// Handle password actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'logout':
                $auth->logout();
                header('Location: /?route=login');
                exit;

            case 'save_password':
                if (isset($_POST['name'])) {
                    $password = $_POST['password'] ?? '';
                    if (empty($password)) {
                        // Generate password if field is empty
                        $generator = new \App\Classes\Security\PasswordGenerator();
                        $password = $generator->generate(
                            (int)$_POST['length'],
                            (int)$_POST['uppercase'],
                            (int)$_POST['lowercase'],
                            (int)$_POST['numbers'],
                            (int)$_POST['special']
                        );
                    }
                    $result = $passwordManager->createPassword(
                        $currentUser->getId(),
                        $_POST['name'],
                        $password,
                        $_POST['website'] ?? null
                    );
                    if ($result) {
                        header('Location: /?route=dashboard');
                        exit;
                    }
                }
                break;

            case 'update_password':
                if (isset($_POST['id']) && isset($_POST['name'])) {
                    $password = $_POST['password'] ?? '';
                    if (empty($password)) {
                        // Generate password if field is empty
                        $generator = new \App\Classes\Security\PasswordGenerator();
                        $password = $generator->generate(
                            (int)$_POST['length'],
                            (int)$_POST['uppercase'],
                            (int)$_POST['lowercase'],
                            (int)$_POST['numbers'],
                            (int)$_POST['special']
                        );
                    }
                    $result = $passwordManager->updatePassword(
                        (int)$_POST['id'],
                        $currentUser->getId(),
                        $_POST['name'],
                        $password,
                        $_POST['website'] ?? null
                    );
                    if ($result) {
                        header('Location: /?route=dashboard');
                        exit;
                    }
                }
                break;

            case 'delete_password':
                if (isset($_POST['id'])) {
                    $result = $passwordManager->deletePassword(
                        (int)$_POST['id'],
                        $currentUser->getId()
                    );
                    if ($result) {
                        header('Location: /?route=dashboard');
                        exit;
                    }
                }
                break;

            case 'edit_password':
                if (isset($_POST['id']) && isset($_POST['name']) && isset($_POST['password']) && isset($_POST['website'])) {
                    $result = $passwordManager->updatePassword(
                        (int)$_POST['id'],
                        $currentUser->getId(),
                        $_POST['name'],
                        $_POST['password'],
                        $_POST['website']
                    );
                    if ($result) {
                        header('Location: /?route=dashboard');
                        exit;
                    }
                }
                break;
        }
    }
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

    <h2>Your Passwords</h2>

    <!-- Password Creation Form -->
    <div>
        <h3>Add New Password</h3>
        <form method="POST">
            <input type="hidden" name="action" value="save_password">
            
            <div>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="password">Password (leave empty to generate):</label>
                <input type="text" id="password" name="password">
            </div>
            <div>
                <label for="website">Website:</label>
                <input type="url" id="website" name="website">
            </div>

            <div>
                <h4>Password Generator Options</h4>
                <div>
                    <label for="length">Length:</label>
                    <input type="number" id="length" name="length" min="8" max="64" value="12">
                </div>
                <div>
                    <label for="uppercase">Uppercase:</label>
                    <input type="number" id="uppercase" name="uppercase" min="0" max="26" value="3">
                </div>
                <div>
                    <label for="lowercase">Lowercase:</label>
                    <input type="number" id="lowercase" name="lowercase" min="0" max="26" value="2">
                </div>
                <div>
                    <label for="numbers">Numbers:</label>
                    <input type="number" id="numbers" name="numbers" min="0" max="10" value="5">
                </div>
                <div>
                    <label for="special">Special:</label>
                    <input type="number" id="special" name="special" min="0" max="20" value="2">
                </div>
            </div>

            <div>
                <button type="submit">Save Password</button>
            </div>
        </form>
    </div>

    <?php if (empty($passwords)): ?>
        <p>No passwords saved yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Password</th>
                    <th>Website</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($passwords as $password): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($password['name']); ?></td>
                        <td><?php echo htmlspecialchars($password['password']); ?></td>
                        <td><?php echo htmlspecialchars($password['website'] ?? ''); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_password">
                                <input type="hidden" name="id" value="<?php echo $password['id']; ?>">
                                <button type="submit" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                            <button onclick="editPassword(<?php echo $password['id']; ?>, '<?php echo htmlspecialchars($password['name']); ?>', '<?php echo htmlspecialchars($password['password']); ?>', '<?php echo htmlspecialchars($password['website'] ?? ''); ?>')">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Edit Password Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; margin: 10% auto; padding: 20px; width: 50%;">
            <h3>Edit Password</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="id" id="editId">
                <div>
                    <label for="editName">Name:</label>
                    <input type="text" id="editName" name="name" required>
                </div>
                <div>
                    <label for="editPassword">Password (leave empty to generate):</label>
                    <input type="text" id="editPassword" name="password">
                </div>
                <div>
                    <label for="editWebsite">Website:</label>
                    <input type="url" id="editWebsite" name="website">
                </div>

                <div>
                    <h4>Password Generator Options</h4>
                    <div>
                        <label for="editLength">Length:</label>
                        <input type="number" id="editLength" name="length" min="8" max="64" value="12">
                    </div>
                    <div>
                        <label for="editUppercase">Uppercase:</label>
                        <input type="number" id="editUppercase" name="uppercase" min="0" max="26" value="3">
                    </div>
                    <div>
                        <label for="editLowercase">Lowercase:</label>
                        <input type="number" id="editLowercase" name="lowercase" min="0" max="26" value="2">
                    </div>
                    <div>
                        <label for="editNumbers">Numbers:</label>
                        <input type="number" id="editNumbers" name="numbers" min="0" max="10" value="5">
                    </div>
                    <div>
                        <label for="editSpecial">Special:</label>
                        <input type="number" id="editSpecial" name="special" min="0" max="20" value="2">
                    </div>
                </div>

                <div>
                    <button type="submit">Update</button>
                    <button type="button" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editPassword(id, name, password, website) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editPassword').value = password;
            document.getElementById('editWebsite').value = website || '';
            document.getElementById('editModal').style.display = 'block';
        }
    </script>
</body>

</html>