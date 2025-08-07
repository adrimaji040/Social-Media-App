<?php

include_once(__DIR__ . "/../src/EntityClassLib.php");
include_once(__DIR__ . "/../src/Functions.php");
include(__DIR__ . "/../Common/Header.php"); 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure the user is authenticated
if (!isset($_SESSION['user']) || !($_SESSION['user'] instanceof User)) {
    $_SESSION['redirect_url'] = basename($_SERVER['PHP_SELF']);
    header("Location: Login.php"); // Redirect to login page
    exit();
}

// Get the logged-in user
$user = $_SESSION['user'];

// Initialize variables
$errors = [];
$title = '';
$accessibility = '';
$description = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $accessibility = $_POST['accessibility'] ?? '';
    $description = $_POST['description'] ?? '';

    // Validate input fields
    if (empty($title)) {
        $errors['title'] = "Title is required.";
    }
    if (empty($accessibility)) {
        $errors['accessibility'] = "Accessibility is required.";
    }

    // If no errors, insert the album into the database
    if (empty($errors)) {
        try {
            $album = new Album($title, $description,$accessibility, $user->getUserId());
            $album->create();
            $_SESSION['successMessage'] = "Album added successfully!";
            header("Location: MyAlbums.php");
            exit();
        } catch (Exception $e) {
            echo "<div class='alert alert-danger disappearing-message'>Error: " . $e->getMessage() . " Please try again." . "</div>";
        }
    }
}

// Fetch accessibility options
$options = getAccessibilityOptions();
?>

<div class="container mb-5 mt-3">
    <div class="shadow py-2 px-3 mb-5 bg-body-tertiary rounded" style="max-width: 60vw; margin: auto;">
        <h1 class="text-center mb-4 animated-border display-6">Create New Album</h1>
        <p class="text-center lead">Welcome <b><?php echo htmlspecialchars($user->getName()); ?></b>! (Not you? <a href="Login.php">change user here</a>)</p>
        <form action="AddAlbum.php" method="post">
            <!-- Title Field -->
            <div class="mb-3">
                <label for="title" class="form-label">Title:</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>">
                <div class="text-danger"><?php echo $errors['title'] ?? ''; ?></div>
            </div>

            <!-- Accessibility Dropdown -->
            <div class="mb-3">
                <label for="accessibility" class="form-label">Accessibility:</label>
                <select class="form-select" id="accessibility" name="accessibility">
                    <option value="">-- Select Accessibility --</option>
                    <?php foreach ($options as $option): ?>
                        <option value="<?php echo htmlspecialchars($option['Accessibility_Code']); ?>" 
                            <?php echo ($accessibility == $option['Accessibility_Code']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($option['Description']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="text-danger"><?php echo $errors['accessibility'] ?? ''; ?></div>
            </div>

            <!-- Description Field -->
            <div class="mb-3">
                <label for="description" class="form-label">Description:</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <!-- Submit and Clear Buttons -->
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="reset" class="btn btn-secondary">Clear</button>
            </div>
        </form>
    </div>
</div>

<?php include(__DIR__ . '/../Common/Footer.php'); ?>

