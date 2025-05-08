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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=arrow_drop_down,arrow_drop_up,info,key,logout" />
</head>

<body class="dashboard">
    <div class="user-actions-container">
        <div class="change-password-link">
            <a href="/?route=change-password">
                <span class="material-symbols-outlined">key</span>
                Change Password
            </a>
        </div>
        <form method="POST" class="logout-form">
            <input type="hidden" name="action" value="logout">
            <button type="submit">
                <span class="material-symbols-outlined">logout</span>
                Logout
            </button>
        </form>
    </div>
    <div class="dashboard-container">
        <h1>Hello <div class="username"><?php echo htmlspecialchars($currentUser->username); ?></div>!</h1>
        <h2>Your Passwords</h2>

        <!-- Password Creation Form -->
        <div class="password-creation-form">
            <h3 class="password-creation-form-title">Add New Password</h3>
            <form method="POST">
                <input type="hidden" name="action" value="save_password">

                <div class="form-group-container">
                    <div class="form-group">
                        <label for="name">Name*</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="website">Website <span class="optional">(optional)</span></label>
                        <input type="url" id="website" name="website" placeholder="https://example.com">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password (leave empty to generate)</label>
                    <input type="text" id="password" name="password">
                </div>

                <!-- Password Generator Options -->
                <div class="password-generator-options">
                    <div class="password-generator-options-open-close-button">
                        <h4>Password Generator Options:</h4>
                        <div class="password-generator-options-open-button">
                            Expand more
                            <span class="material-symbols-outlined">arrow_drop_down</span>
                        </div>
                        <div class="password-generator-options-close-button">
                            Expand less
                            <span class="material-symbols-outlined">arrow_drop_up</span>
                        </div>
                    </div>
                    <script>
                        document.querySelector('.password-generator-options-open-close-button').addEventListener('click', function() {
                            document.querySelector('.password-generator-options').classList.toggle('active');
                            this.classList.toggle('active');
                        });
                    </script>

                    <div class="password-generator-options-container">
                        <div class="info-text">
                            <span class="material-symbols-outlined">info</span>
                            <p>The password generator will generate a password with the specified options. <br> Enter the number of each character you want to generate.</p>
                        </div>
                        <div class="form-group">
                            <label for="uppercase">Uppercase:</label>
                            <input type="number" id="uppercase" name="uppercase" min="0" max="26" value="3">
                        </div>
                        <div class="form-group">
                            <label for="lowercase">Lowercase:</label>
                            <input type="number" id="lowercase" name="lowercase" min="0" max="26" value="2">
                        </div>
                        <div class="form-group">
                            <label for="numbers">Numbers:</label>
                            <input type="number" id="numbers" name="numbers" min="0" max="10" value="5">
                        </div>
                        <div class="form-group">
                            <label for="special">Special:</label>
                            <input type="number" id="special" name="special" min="0" max="20" value="2">
                        </div>
                    </div>
                </div>

                <div class="form-submit-button">
                    <button type="submit">Save Password</button>
                </div>
            </form>
        </div>

        <?php if (empty($passwords)): ?>
            <p>No passwords saved yet.</p>
        <?php else: ?>
            <table class="passwords-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Website</th>
                        <th>Password</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($passwords as $password): ?>
                        <tr>
                            <td class="name-column"><?php echo htmlspecialchars($password['name']); ?></td>
                            <td class="website-column"><a href="<?php echo htmlspecialchars($password['website'] ?? ''); ?>" target="_blank"><?php echo htmlspecialchars($password['website'] ?? 'No website'); ?></a></td>
                            <td class="password-column"><?php echo htmlspecialchars($password['password']); ?></td>
                            <td class="actions-container">
                                <button class="edit-password-button" onclick="editPassword(<?php echo $password['id']; ?>, '<?php echo htmlspecialchars($password['name']); ?>', '<?php echo htmlspecialchars($password['password']); ?>', '<?php echo htmlspecialchars($password['website'] ?? ''); ?>')">Edit</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_password">
                                    <input type="hidden" name="id" value="<?php echo $password['id']; ?>">
                                    <button class="delete-password-button" type="submit" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Edit Password Modal -->
        <div id="editModal" class="edit-password-modal">
            <div class="edit-password-modal-content">
                <h3>Edit Password</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_password">
                    <input type="hidden" name="id" id="editId">

                    <div class="form-group-container">
                        <div class="form-group">
                            <label for="editName">Name*</label>
                            <input type="text" id="editName" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="website">Website <span class="optional">(optional)</span></label>
                            <input type="url" id="editWebsite" name="website">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editPassword">Password (leave empty to generate):</label>
                        <input type="text" id="editPassword" name="password">
                    </div>


                    <!-- Password Generator Options -->
                    <div class="password-generator-options update">
                        <div class="password-generator-options-open-close-button update">
                            <h4>Password Generator Options:</h4>
                            <div class="password-generator-options-open-button update">
                                Expand more
                                <span class="material-symbols-outlined">arrow_drop_down</span>
                            </div>
                            <div class="password-generator-options-close-button update">
                                Expand less
                                <span class="material-symbols-outlined">arrow_drop_up</span>
                            </div>
                        </div>
                        <script>
                            document.querySelector('.password-generator-options-open-close-button.update').addEventListener('click', function() {
                                document.querySelector('.password-generator-options.update').classList.toggle('active');
                                this.classList.toggle('active');
                            });
                        </script>

                        <div class="password-generator-options-container update">
                            <div class="info-text" style="display: block;">
                                <span class="material-symbols-outlined">info</span>
                                <p>The password generator will generate a password with the specified options. <br> Enter the number of each character you want to generate.</p>
                            </div>
                            <br />
                            <div class="form-group">
                                <label for="uppercase">Uppercase:</label>
                                <input type="number" id="uppercase" name="uppercase" min="0" max="26" value="3">
                            </div>
                            <div class="form-group">
                                <label for="lowercase">Lowercase:</label>
                                <input type="number" id="lowercase" name="lowercase" min="0" max="26" value="2">
                            </div>
                            <div class="form-group">
                                <label for="numbers">Numbers:</label>
                                <input type="number" id="numbers" name="numbers" min="0" max="10" value="5">
                            </div>
                            <div class="form-group">
                                <label for="special">Special:</label>
                                <input type="number" id="special" name="special" min="0" max="20" value="2">
                            </div>
                        </div>
                    </div>

                    <div class="form-submit-button">
                        <button class="update-password-button" type="submit">Update</button>
                        <button class="cancel-password-update-button" type="button" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
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