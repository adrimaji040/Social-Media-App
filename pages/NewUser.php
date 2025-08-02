<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once(__DIR__ . "/../Common/Header.php");
include_once(__DIR__ . "/../src/Functions.php");
include_once(__DIR__ . "/../src/EntityClassLib.php");
require_once(__DIR__ . "/../src/SecurityMode.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start session only if not already started
}

extract($_POST);

$errors = [];
$txtId = isset($txtId) ? $txtId : '';
$txtName = isset($txtName) ? $txtName : '';
$txtPhoneNumber = isset($txtPhoneNumber) ? $txtPhoneNumber : '';
$txtPassword = isset($txtPassword) ? $txtPassword : '';
$txtPasswordConfirm = isset($txtPasswordConfirm) ? $txtPasswordConfirm : '';

if (isset($regSubmit)) {
    // Validate fields
    if (empty($txtId)) {
        $errors['txtId'] = "Student ID is required.";
    } else {
        try {
            $existingUser = getUserById($txtId);
            if ($existingUser) {
                $errors['txtId'] = "A user with this ID has already signed up.";
            }
        } catch (Exception $e) {
            die("The system is currently not available, try again later.");
        }
    }

    if (empty($txtName)) {
        $errors['txtName'] = "Name is required.";
    }

    if (!preg_match("/^\d{3}-\d{3}-\d{4}$/", $txtPhoneNumber)) {
        $errors['txtPhoneNumber'] = "Phone Number must be in the format nnn-nnn-nnnn.";
    }

    if (
        strlen($txtPassword) < 6 ||
        !preg_match("/[A-Z]/", $txtPassword) ||
        !preg_match("/[a-z]/", $txtPassword) ||
        !preg_match("/\d/", $txtPassword)
    ) {
        $errors['txtPassword'] = "Password must be at least 6 characters long, and contain at least one uppercase letter, one lowercase letter, and one digit.";
    }

    if ($txtPassword !== $txtPasswordConfirm) {
        $errors['txtPasswordConfirm'] = "Passwords do not match.";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            global $SECURITY_MODE;
            // Hash the password before storing
            if ($SECURITY_MODE === "vulnerable") {
                $hashedPassword = $txtPassword; // Plaintext storage (insecure)
            } else {
                $hashedPassword = password_hash($txtPassword, PASSWORD_DEFAULT); // Secure hash
            }

            addNewUser($txtId, $txtName, $txtPhoneNumber, $hashedPassword);
            $_SESSION['user'] = getUserByIdAndPassword($txtId, $txtPassword);
            header("Location: Index.php");
            exit();
        } catch (Exception $e) {
            die("The system is currently not available, try again later.");
        }
    }
}
?>

<section class="container text-start mb-5 mt-3">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-7">
            <form action="NewUser.php" method="post" class="p-4 border bg bg-light rounded shadow">
                <div class="text-center">
                    <h1 class="display-4 animated-border">Sign up</h1>
                    <p class="lead">All fields are required</p>
                </div>

                <div class="row mb-3">
                    <label for="studentId" class="col-sm-4 col-form-label">User ID:</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" id="studentId" name="txtId"
                            value="<?php echo htmlspecialchars($txtId); ?>">
                        <div class="text-danger"><?php echo isset($errors['txtId']) ? $errors['txtId'] : ''; ?></div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="studentName" class="col-sm-4 col-form-label">Name:</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" id="studentName" name="txtName"
                            value="<?php echo htmlspecialchars($txtName); ?>">
                        <div class="text-danger"><?php echo isset($errors['txtName']) ? $errors['txtName'] : ''; ?>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="phoneNumber" class="col-sm-4 col-form-label">Phone Number:</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" id="phoneNumber" name="txtPhoneNumber"
                            value="<?php echo htmlspecialchars($txtPhoneNumber); ?>">
                        <div class="text-danger">
                            <?php echo isset($errors['txtPhoneNumber']) ? $errors['txtPhoneNumber'] : ''; ?>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="createPassword" class="col-sm-4 col-form-label">Password:</label>
                    <div class="col-sm-8">
                        <input type="password" class="form-control" id="createPassword" name="txtPassword">
                        <div class="text-danger">
                            <?php echo isset($errors['txtPassword']) ? $errors['txtPassword'] : ''; ?>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="confirmPassword" class="col-sm-4 col-form-label">Password Again:</label>
                    <div class="col-sm-8">
                        <input type="password" class="form-control" id="confirmPassword" name="txtPasswordConfirm">
                        <div class="text-danger">
                            <?php echo isset($errors['txtPasswordConfirm']) ? $errors['txtPasswordConfirm'] : ''; ?>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" name="regSubmit" class="btn btn-primary me-2">Submit</button>
                    <button type="reset" class="btn btn-outline-secondary">Clear</button>
                </div>
            </form>
        </div>
    </div>
</section>


<?php include(__DIR__ . '/../Common/Footer.php'); ?>