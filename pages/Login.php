<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . "/../Common/Header.php");
include_once __DIR__ . '/../src/Functions.php';
include_once __DIR__ . '/../src/EntityClassLib.php';

extract($_POST);
$loginErrorMsg = '';

if (isset($btnLogin)) {
    try {
        // Fetch the user by ID and password
        $user = getUserByIdAndPassword($txtId, $txtPswd);

        if ($user) {
            // Log in the user
            $_SESSION['user'] = $user;

            // Check if there's a redirect URL stored in session
            $redirectUrl = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'index.php';

            // Redirect to the stored page
            header("Location: $redirectUrl");
            exit();
        } else {
            // If user is not found or password doesn't match, show error message
            $loginErrorMsg = 'Incorrect User ID and Password Combination!';
        }
    } catch (Exception $e) {
        die("The system is currently not available, try again later.");
    }
}
?>
<section class="container text-start mb-5 mt-3">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg">
                <div class="card-header text-center">
                    <i class="fas fa-sign-in-alt me-2"></i>Login to Your Account
                </div>
                <div class="card-body p-4">
                    <form action='Login.php' method='post'>
                        <div class="text-center mb-4">
                            <h1 class="display-6 animated-border">Welcome Back!</h1>
                            <p class="text-muted">Sign in to continue your journey</p>
                        </div>

                        <?php if (!empty($loginErrorMsg)): ?>
                            <div class="alert alert-danger border-0 shadow-sm" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($loginErrorMsg); ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="studentId" class="form-label">
                                <i class="fas fa-user me-2"></i>User ID
                            </label>
                            <input type="text" id="studentId" name="txtId" class="form-control form-control-lg"
                                placeholder="Enter your user ID"
                                value="<?php echo isset($txtId) ? htmlspecialchars($txtId) : ''; ?>" required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <div class="input-group">
                                <input type="password" id="password" name="txtPswd" class="form-control form-control-lg"
                                    placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" name="btnLogin" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Clear Form
                            </button>
                        </div>

                        <div class="text-center mt-4">
                            <p class="text-muted">
                                New to our platform?
                                <a href='NewUser.php' class="text-decoration-none">
                                    <i class="fas fa-user-plus me-1"></i>Create an account
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordField = document.getElementById('password');
        const toggleIcon = this.querySelector('i');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    });
</script>

<?php include(__DIR__ . '/../Common/Footer.php'); ?>