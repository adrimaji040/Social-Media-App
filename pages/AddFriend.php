<?php

include_once(__DIR__ . "/../src/EntityClassLib.php");
include_once(__DIR__ . "/../src/Functions.php");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !($_SESSION['user'] instanceof User)) {
    $_SESSION['redirect_url'] = basename($_SERVER['PHP_SELF']);
    header("Location: Login.php");
    exit();
}

// Function to ensure the FriendshipStatus table is initialized
function initializeFriendshipStatus($pdo)
{
    $statuses = [
        ['pending', 'Friend request pending'],
        ['accepted', 'Friend request accepted']
    ];

    foreach ($statuses as $status) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM FriendshipStatus WHERE Status_Code = ?');
        $stmt->execute([$status[0]]);
        if ($stmt->fetchColumn() == 0) {
            $insertStmt = $pdo->prepare('INSERT INTO FriendshipStatus (Status_Code, Description) VALUES (?, ?)');
            $insertStmt->execute($status);
        }
    }
}

// Get the logged-in user
$user = $_SESSION['user'];

$errors = [];
$successes = [];

try {
    $pdo = getPDO();

    // Ensure the FriendshipStatus table is initialized
    initializeFriendshipStatus($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $friendId = trim($_POST['friendId']);

        // Validate input
        if (empty($friendId)) {
            $errors[] = 'Please enter a User ID.';
        } elseif ($friendId === $user->getUserId()) {
            $errors[] = 'You cannot send a friend request to yourself.';
        } else {
            // Fetch the friend's name
            $stmt = $pdo->prepare('SELECT Name FROM User WHERE UserId = ?');
            $stmt->execute([$friendId]);
            $friendName = $stmt->fetchColumn();

            if ($friendName) {
                // Check for a pending request from B to A
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM Friendship WHERE Friend_RequesterId = ? AND Friend_RequesteeId = ? AND Status = ?');
                $stmt->execute([$friendId, $user->getUserId(), 'pending']);
                if ($stmt->fetchColumn() > 0) {
                    // Accept the pending friend request from B to A
                    $stmt = $pdo->prepare('UPDATE Friendship SET Status = ? WHERE Friend_RequesterId = ? AND Friend_RequesteeId = ?');
                    $stmt->execute(['accepted', $friendId, $user->getUserId()]);

                    // Also insert the reverse friendship to maintain bidirectional relationship
                    $stmt = $pdo->prepare('INSERT INTO Friendship (Friend_RequesterId, Friend_RequesteeId, Status) VALUES (?, ?, ?)');
                    $stmt->execute([$user->getUserId(), $friendId, 'accepted']);

                    $successes[] = "You and $friendName (ID: $friendId) are now friends.";
                } else {
                    // Check for existing relationships
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM Friendship WHERE 
                        (Friend_RequesterId = ? AND Friend_RequesteeId = ?) OR 
                        (Friend_RequesterId = ? AND Friend_RequesteeId = ?)');
                    $stmt->execute([$user->getUserId(), $friendId, $friendId, $user->getUserId()]);
                    if ($stmt->fetchColumn() > 0) {
                        $errors[] = "You and $friendName (ID: $friendId) are already friends.";
                    } else {
                        // Send a friend request
                        $stmt = $pdo->prepare('INSERT INTO Friendship (Friend_RequesterId, Friend_RequesteeId, Status) VALUES (?, ?, ?)');
                        $stmt->execute([$user->getUserId(), $friendId, 'pending']);
                        $successes[] = "Your request has been sent to $friendName (ID: $friendId). Once $friendName accepts your friend request, you will be able to view each other's shared albums.";
                    }
                }
            } else {
                $errors[] = 'The specified user does not exist.';
            }
        }
    }
} catch (Exception $e) {
    $errors[] = 'An error occurred: ' . htmlspecialchars($e->getMessage());
}

include(__DIR__ . "/../Common/Header.php");
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-transparent text-center py-4">
                    <h1 class="mb-3 animated-border display-6">
                        <i class="fas fa-user-plus text-primary me-3"></i>Add Friends
                    </h1>
                    <p class="lead mb-0">
                        Welcome <strong class="text-primary"><?= htmlspecialchars($user->getName()); ?></strong>!
                        <small class="text-muted">(Not you? <a href="Login.php" class="text-decoration-none">change user
                                here</a>)</small>
                    </p>
                </div>

                <div class="card-body p-4">
                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Oops!</strong> There were some issues:
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Success Messages -->
                    <?php if (!empty($successes)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Success!</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($successes as $success): ?>
                                    <li><?= htmlspecialchars($success) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Add Friend Form -->
                    <form method="post" id="addFriendForm" novalidate>
                        <div class="mb-4">
                            <label for="friendId" class="form-label fw-semibold">
                                <i class="fas fa-search text-info me-2"></i>Find Friend by User ID
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">
                                    <i class="fas fa-user text-muted"></i>
                                </span>
                                <input type="text" name="friendId" id="friendId" class="form-control"
                                    placeholder="Enter User ID (e.g., john_doe, user123...)" required>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Send Request
                                </button>
                            </div>
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Ask your friend for their User ID to send them a friend request
                                </small>
                            </div>
                        </div>
                    </form>

                    <!-- Quick Actions -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="MyFriends.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to My Friends
                        </a>
                        <small class="text-muted">
                            <i class="fas fa-question-circle me-1"></i>
                            Need help finding friends? Contact support
                        </small>
                    </div>
                </div>
            </div>

            <!-- How to Find Friends Card -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-3">
                        <i class="fas fa-lightbulb text-warning me-2"></i>How to Find Friends
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled small text-muted">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Ask friends for their exact User ID
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    User IDs are case-sensitive
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled small text-muted">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Check for typos in the User ID
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Friends will receive your request
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
        const form = document.getElementById('addFriendForm');
        const friendIdInput = document.getElementById('friendId');
        const submitBtn = document.getElementById('submitBtn');

        // Real-time validation
        friendIdInput.addEventListener('input', function () {
            const value = this.value.trim();
            if (value.length > 0) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });

        // Form submission with loading state
        form.addEventListener('submit', function (e) {
            const friendId = friendIdInput.value.trim();

            if (!friendId) {
                e.preventDefault();
                friendIdInput.classList.add('is-invalid');
                friendIdInput.focus();
                return;
            }

            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;

            // Re-enable after 10 seconds as fallback
            setTimeout(() => {
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Request';
                submitBtn.disabled = false;
            }, 10000);
        });

        // Auto-focus the input field
        friendIdInput.focus();

        // Handle input validation
        friendIdInput.addEventListener('blur', function () {
            const value = this.value.trim();
            if (value.length === 0) {
                this.classList.add('is-invalid');
            }
        });

        friendIdInput.addEventListener('focus', function () {
            this.classList.remove('is-invalid');
        });
    });
</script>

<?php include(__DIR__ . '/../Common/Footer.php'); ?>