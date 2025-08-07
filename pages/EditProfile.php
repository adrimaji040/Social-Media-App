<?php

include_once(__DIR__ . "/../src/EntityClassLib.php");
include_once(__DIR__ . "/../src/Functions.php");
require_once(__DIR__ . "/../src/SecurityMode.php");
include(__DIR__ . "/../Common/Header.php");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !($_SESSION['user'] instanceof User)) {
    $_SESSION['redirect_url'] = basename($_SERVER['PHP_SELF']);
    header("Location: Login.php");
    exit();
}

$user = $_SESSION['user'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validar nombre
    if (empty($newName)) {
        $errors['name'] = "Name is required.";
    }

    // Validar cambio de contraseña solo si se ingresó algo
    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
        global $SECURITY_MODE;

        if (empty($currentPassword)) {
            $errors['current_password'] = "Current password is required.";
        } else {
            // Verify current password based on security mode
            $isCurrentPasswordValid = false;
            if ($SECURITY_MODE === "vulnerable") {
                // In vulnerable mode, passwords are stored as plain text
                $isCurrentPasswordValid = ($currentPassword === $user->getPassword());
            } else {
                // In secure mode, passwords are hashed
                $isCurrentPasswordValid = password_verify($currentPassword, $user->getPassword());
            }

            if (!$isCurrentPasswordValid) {
                $errors['current_password'] = "Current password is incorrect.";
            }
        }

        if (empty($newPassword)) {
            $errors['new_password'] = "New password is required.";
        } elseif (
            strlen($newPassword) < 6 ||
            !preg_match("/[A-Z]/", $newPassword) ||
            !preg_match("/[a-z]/", $newPassword) ||
            !preg_match("/\d/", $newPassword)
        ) {
            $errors['new_password'] = "Password must be at least 6 characters, with uppercase, lowercase, and a digit.";
        }

        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = "Passwords do not match.";
        }
    }

    // Si no hay errores, actualizar en la base de datos
    if (empty($errors)) {
        try {
            $pdo = getPDO();
            // Actualizar nombre
            $stmt = $pdo->prepare("UPDATE User SET Name = :name WHERE UserId = :userId");
            $stmt->execute(['name' => $newName, 'userId' => $user->getUserId()]);
            $user->setName($newName);

            // Actualizar contraseña si corresponde
            if (!empty($newPassword)) {
                global $SECURITY_MODE;
                if ($SECURITY_MODE === "vulnerable") {
                    // In vulnerable mode, store password as plain text
                    $hashedPassword = $newPassword;
                } else {
                    // In secure mode, hash the password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                }
                $stmt = $pdo->prepare("UPDATE User SET Password = :password WHERE UserId = :userId");
                $stmt->execute(['password' => $hashedPassword, 'userId' => $user->getUserId()]);
                $user->setPassword($hashedPassword);
            }

            $_SESSION['user'] = $user;
            $success = "Profile updated successfully!";
        } catch (Exception $e) {
            $errors['general'] = "Error updating profile. Please try again.";
        }
    }
}
?>

<div class="container mb-5 mt-3">
    <div class="shadow py-2 px-3 mb-5 bg-body-tertiary rounded" style="max-width: 60vw; margin: auto;">
        <h1 class="mb-4 text-center display-6 text-primary animated-border">Edit Profile</h1>
        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger text-center">
                <?php foreach ($errors as $error)
                    echo htmlspecialchars($error) . "<br>"; ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="name" class="form-label lead">Name:</label>
                <input type="text" name="name" id="name" class="form-control"
                    value="<?= htmlspecialchars($user->getName()) ?>">
            </div>
            <hr>
            <h5>Change Password</h5>
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password:</label>
                <input type="password" name="current_password" id="current_password" class="form-control">
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password:</label>
                <input type="password" name="new_password" id="new_password" class="form-control">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control">
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary me-2">Save Changes</button>
                <a href="../Index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include(__DIR__ . '/../Common/Footer.php'); ?>