<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include("./common/header.php");
include_once 'Functions.php';
include_once 'EntityClassLib.php';


// testDBConnection();

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
         die("Error: " . $e->getMessage());
    }
}
?>
<section class="container text-start mb-5 mt-3">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <form action='Login.php' method='post' class="p-4 border bg bg-light rounded shadow" style="max-width: 600px;">
                <h1 class="text-center display-6 animated-border">Login</h1>
                <p class="lead text-center">You need to <a href='NewUser.php'>sign up</a> if you are a new user.</p>

                <div class="mb-3">
                    <div class="text-danger">
                        <?php echo $loginErrorMsg; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="studentId" class="col-sm-4 col-form-label text-end">User ID:</label>
                    <div class="col-sm-6">
                        <input type='text' class="form-control" id="studentId" name='txtId' value="<?php echo isset($txtId) ? $txtId : ''; ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="password" class="col-sm-4 col-form-label text-end">Password:</label>
                    <div class="col-sm-6">
                        <input type='password' class="form-control" id="password" name='txtPswd'>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-6 offset-sm-4">
                        <button type="submit" name='btnLogin' class="btn btn-primary me-2">Login</button>
                        <button type="reset" class="btn btn-outline-secondary">Clear</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include('./common/footer.php'); ?>