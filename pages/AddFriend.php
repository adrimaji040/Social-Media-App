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
    //initializeFriendshipStatus($pdo);

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

<div class="container mb-5 mt-3">
    <div class="shadow py-2 px-3 mb-5 bg-body-tertiary rounded" style="max-width: 60vw; margin: auto;">
        <h1 class="mb-4 text-center display-6 text-primary animated-border">Add Friends</h1>
        <p class="text-center lead">
            Welcome <b><?= htmlspecialchars($user->getName()); ?></b>!
            (Not you? <a href="Login.php">Change user here</a>)
        </p>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger text-center mt-2" role="alert">
                <?php foreach ($errors as $error): ?>
                    <?= htmlspecialchars($error) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Success Messages -->
        <?php if (!empty($successes)): ?>
            <div class="alert alert-success">
                <?php foreach ($successes as $success): ?>
                    <?= htmlspecialchars($success) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="post" class="mt-4">
            <div class="mb-3">
                <label for="friendId" class="form-label lead">Enter the User ID of the friend you want to add:</label>
                <input type="text" name="friendId" id="friendId" class="form-control form-control-md" placeholder="User ID ..." required>
            </div>
            <div class="text-center mb-3">
                <button type="submit" class="btn btn-primary btn-md">
                Send Friend Request
                </button>
            </div>
        </form>
    </div>
</div>

<?php include(__DIR__ . '/../Common/Footer.php'); ?>
