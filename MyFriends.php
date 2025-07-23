<?php
include_once 'EntityClassLib.php';
include_once 'Functions.php';
include("./common/header.php");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !($_SESSION['user'] instanceof User)) {
    $_SESSION['redirect_url'] = basename($_SERVER['PHP_SELF']);
    header("Location: Login.php");
    exit();
}

$user = $_SESSION['user'];

// Handle friend requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getPDO();

        if (isset($_POST['accept'])) {
            if (!empty($_POST['friend_requests'])) {
                foreach ($_POST['friend_requests'] as $friendId) {
                    // Accept the friend request
                    $stmt = $pdo->prepare('UPDATE Friendship SET Status = ? WHERE Friend_RequesterId = ? AND Friend_RequesteeId = ?');
                    $stmt->execute(['accepted', $friendId, $user->getUserId()]);

                    // Add reverse friendship if not already present
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM Friendship WHERE Friend_RequesterId = ? AND Friend_RequesteeId = ?');
                    $stmt->execute([$user->getUserId(), $friendId]);
                    if ($stmt->fetchColumn() == 0) {
                        $stmt = $pdo->prepare('INSERT INTO Friendship (Friend_RequesterId, Friend_RequesteeId, Status) VALUES (?, ?, ?)');
                        $stmt->execute([$user->getUserId(), $friendId, 'accepted']);
                    }
                }
            } else {
                echo "<div class='alert alert-warning'>Please select at least one friend request to accept.</div>";
            }
        } elseif (isset($_POST['decline'])) {
            if (!empty($_POST['friend_requests'])) {
                foreach ($_POST['friend_requests'] as $friendId) {
                    // Delete the friend request
                    $stmt = $pdo->prepare('DELETE FROM Friendship WHERE Friend_RequesterId = ? AND Friend_RequesteeId = ?');
                    $stmt->execute([$friendId, $user->getUserId()]);
                }
            } else {
                echo "<div class='alert alert-warning'>Please select at least one friend request to decline.</div>";
            }
        } elseif (isset($_POST['defriend'])) {
            if (!empty($_POST['friends'])) {
                foreach ($_POST['friends'] as $friendId) {
                    // Delete friendship from both directions
                    $stmt = $pdo->prepare('DELETE FROM Friendship WHERE (Friend_RequesterId = ? AND Friend_RequesteeId = ?) OR (Friend_RequesterId = ? AND Friend_RequesteeId = ?)');
                    $stmt->execute([$user->getUserId(), $friendId, $friendId, $user->getUserId()]);
                }
                header("Location: MyFriends.php");
                exit();
            } else {
                echo "<div class='alert alert-warning'>Please select at least one friend to defriend.</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Fetch the user's friends
$pdo = getPDO();
$stmt = $pdo->prepare('
SELECT DISTINCT
    u.UserId, 
    u.Name AS FullName, 
    (
        SELECT COUNT(*) 
        FROM Album a
        WHERE 
            a.Owner_Id = u.UserId AND 
            a.Accessibility_Code = "shared"
    ) AS SharedAlbums
FROM 
    Friendship f
JOIN 
    User u 
ON 
    (u.UserId = f.Friend_RequesterId AND f.Friend_RequesteeId = ?) 
    OR 
    (u.UserId = f.Friend_RequesteeId AND f.Friend_RequesterId = ?)
WHERE 
    f.Status = "accepted"
');
$stmt->execute([$user->getUserId(), $user->getUserId()]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending friend requests
$stmt = $pdo->prepare('
SELECT 
    u.UserId, u.Name AS FullName
FROM 
    Friendship f
JOIN 
    User u 
ON 
    u.UserId = f.Friend_RequesterId
WHERE 
    f.Friend_RequesteeId = ? AND f.Status = "pending"
');
$stmt->execute([$user->getUserId()]);
$friendRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mb-5 mt-3">
    <div class="shadow py-2 px-3 mb-5 bg-body-tertiary rounded">
        <div class="card-body">
            <h1 class="mb-2 animated-border display-6 text-center">My Friends</h1>
            <p class="text-center lead">
                Welcome <b><?= htmlspecialchars($user->getName()); ?></b>!
                (Not you? <a href="Login.php">change user here</a>)
            </p>

            <!-- Friends List -->
            <div class="card mb-4" style="min-height: 300px;">
                <div class="card-header bg-primary bg-gradient text-white">
                    <h4>Friends List</h4>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?php if (!empty($friends)): ?>
                            <table class="table table-bordered table-striped table-hover mt-3 mb-4">
                                <thead class="bg-gradient text-center">
                                    <tr>
                                        <th>Friend's Name</th>
                                        <th>Shared Albums <i class="bi bi-images"></i></th>
                                        <th>Select</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-gradient text-center">
                                    <?php foreach ($friends as $friend): ?>
                                        <tr>
                                            <td>
                                                <a href="FriendPictures.php?friendId=<?= $friend['UserId'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($friend['FullName']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?= $friend['SharedAlbums'] ?></span>
                                            </td>
                                            <td>
                                                <input type="checkbox" name="friends[]" value="<?= $friend['UserId'] ?>" class="form-check-input">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="text-start mt-3">
                                <button type="submit" name="defriend" class="btn btn-outline-danger me-2" onclick="return confirmDefriend();" style="width:190px;">
                                    Defriend Selected
                                </button>
                                <a href="AddFriend.php" class="btn btn-outline-primary" style="width:190px;">
                                    <i class="bi bi-person-plus-fill"></i> Add Friends
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted lead mb-5">You have no friends yet.
                                <a href="AddFriend.php"><i class="bi bi-person-plus-fill"></i> Click here to add friends.</a>
                            </p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Friend Requests -->
            <div class="card mb-4"style="min-height: 300px;">
                <div class="card-header bg-primary bg-gradient text-white">
                    <h4>Friend Requests</h4>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?php if (!empty($friendRequests)): ?>
                            <table class="table table-bordered table-striped table-hover mt-3 mb-4" style="max-width: 80vw;">
                                <thead class="bg-gradient text-center">
                                    <tr>
                                        <th>Name</th>
                                        <th>Select</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <?php foreach ($friendRequests as $request): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($request['FullName']) ?></td>
                                            <td>
                                                <input type="checkbox" name="friend_requests[]" value="<?= $request['UserId'] ?>" class="form-check-input">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="submit" name="accept" class="btn btn-outline-success">
                                Accept Selected
                            </button>
                            <button type="submit" name="decline" class="btn btn-outline-danger" onclick="return confirmDecline();">
                                Decline Selected
                            </button>
                        <?php else: ?>
                            <p class="text-muted lead">No pending friend requests.</p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDefriend() {
        const checkboxes = document.querySelectorAll('input[name="friends[]"]:checked');
        if (checkboxes.length === 0) {
            alert("Please select at least one friend to defriend.");
            return false;
        }
        return confirm("Are you sure you want to defriend the selected friends?");
    }

    function confirmDecline() {
        const checkboxes = document.querySelectorAll('input[name="friend_requests[]"]:checked');
        if (checkboxes.length === 0) {
            alert("Please select at least one friend request to decline.");
            return false;
        }
        return confirm("Are you sure you want to decline the selected friend requests?");
    }
</script>
<?php include('./common/footer.php'); ?>