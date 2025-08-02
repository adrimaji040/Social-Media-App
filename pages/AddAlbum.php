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
            $album = new Album($title, $description, $accessibility, $user->getUserId());
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

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-transparent text-center py-4">
                    <h1 class="mb-3 animated-border display-6">
                        <i class="fas fa-plus-circle text-primary me-3"></i>Create New Album
                    </h1>
                    <p class="lead mb-0">
                        Welcome <strong class="text-primary"><?php echo htmlspecialchars($user->getName()); ?></strong>!
                        <small class="text-muted">(Not you? <a href="Login.php" class="text-decoration-none">change user
                                here</a>)</small>
                    </p>
                </div>

                <div class="card-body p-4">
                    <form action="AddAlbum.php" method="post" id="addAlbumForm" novalidate>
                        <!-- Title Field -->
                        <div class="mb-4">
                            <label for="title" class="form-label fw-semibold">
                                <i class="fas fa-tag text-primary me-2"></i>Album Title
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control form-control-lg <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>"
                                id="title" name="title" value="<?php echo htmlspecialchars($title); ?>"
                                placeholder="Enter a memorable name for your album..." maxlength="50" required>
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Choose a descriptive title that represents your photos
                                </small>
                            </div>
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle me-1"></i><?php echo $errors['title']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Accessibility Dropdown -->
                        <div class="mb-4">
                            <label for="accessibility" class="form-label fw-semibold">
                                <i class="fas fa-eye text-info me-2"></i>Privacy Setting
                                <span class="text-danger">*</span>
                            </label>
                            <select
                                class="form-select form-select-lg <?php echo isset($errors['accessibility']) ? 'is-invalid' : ''; ?>"
                                id="accessibility" name="accessibility" required>
                                <option value="">Choose who can see this album...</option>
                                <?php foreach ($options as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option['Accessibility_Code']); ?>" <?php echo ($accessibility == $option['Accessibility_Code']) ? 'selected' : ''; ?>>
                                        <?php
                                        $icon = '';
                                        switch ($option['Accessibility_Code']) {
                                            case 'private':
                                                $icon = '🔒';
                                                break;
                                            case 'shared':
                                                $icon = '👥';
                                                break;
                                            case 'public':
                                                $icon = '🌍';
                                                break;
                                            default:
                                                $icon = '📷';
                                                break;
                                        }
                                        echo $icon . ' ' . htmlspecialchars($option['Description']);
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    You can change this setting later from your albums page
                                </small>
                            </div>
                            <?php if (isset($errors['accessibility'])): ?>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle me-1"></i><?php echo $errors['accessibility']; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Description Field -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">
                                <i class="fas fa-align-left text-success me-2"></i>Description
                                <small class="text-muted fw-normal">(Optional)</small>
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="4"
                                placeholder="Tell us about this album... What memories will it hold?"
                                maxlength="500"><?php echo htmlspecialchars($description); ?></textarea>
                            <div class="form-text">
                                <small class="text-muted">
                                    <span id="charCount">0</span>/500 characters
                                </small>
                            </div>
                        </div>

                        <!-- Submit and Clear Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-between pt-3 border-top">
                            <div>
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="clearForm()">
                                    <i class="fas fa-eraser me-2"></i>Clear Form
                                </button>
                                <a href="MyAlbums.php" class="btn btn-outline-info">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Albums
                                </a>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg px-4" id="submitBtn">
                                <i class="fas fa-plus me-2"></i>Create Album
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Tips Card -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">
                        <i class="fas fa-lightbulb text-warning me-2"></i>Quick Tips
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled small text-muted">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Use descriptive titles for easy searching
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Add descriptions to remember special moments
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled small text-muted">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Choose privacy settings carefully
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    You can edit album details anytime
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('addAlbumForm');
        const titleInput = document.getElementById('title');
        const descriptionTextarea = document.getElementById('description');
        const charCount = document.getElementById('charCount');
        const submitBtn = document.getElementById('submitBtn');

        // Character counter for description
        function updateCharCount() {
            const count = descriptionTextarea.value.length;
            charCount.textContent = count;
            charCount.className = count > 450 ? 'text-warning' : count > 480 ? 'text-danger' : 'text-muted';
        }

        descriptionTextarea.addEventListener('input', updateCharCount);
        updateCharCount(); // Initial count

        // Real-time validation
        function validateField(field, errorContainer) {
            if (field.hasAttribute('required') && !field.value.trim()) {
                field.classList.add('is-invalid');
                return false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
                return true;
            }
        }

        // Validate on blur
        titleInput.addEventListener('blur', () => validateField(titleInput));
        document.getElementById('accessibility').addEventListener('change', function () {
            validateField(this);
        });

        // Form submission with loading state
        form.addEventListener('submit', function (e) {
            let isValid = true;

            // Validate required fields
            isValid &= validateField(titleInput);
            isValid &= validateField(document.getElementById('accessibility'));

            if (!isValid) {
                e.preventDefault();
                return;
            }

            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Album...';
            submitBtn.disabled = true;
        });

        // Auto-focus title field
        titleInput.focus();
    });

    function clearForm() {
        if (confirm('Are you sure you want to clear all form data?')) {
            document.getElementById('addAlbumForm').reset();
            document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid');
            });
            document.getElementById('charCount').textContent = '0';
            document.getElementById('title').focus();
        }
    }
</script>

<?php include(__DIR__ . '/../Common/Footer.php'); ?>