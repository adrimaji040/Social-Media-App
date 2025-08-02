<?php
include_once(__DIR__ . "/../src/EntityClassLib.php");
include_once(__DIR__ . "/../src/Functions.php");
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

<div class="container my-4">
    <div class="card border-0 shadow-lg">
        <div class="card-header bg-transparent text-center py-4">
            <h1 class="mb-3 animated-border display-6">
                <i class="fas fa-users text-primary me-3"></i>My Friends
            </h1>
            <p class="lead mb-0">
                Welcome <strong class="text-primary"><?= htmlspecialchars($user->getName()); ?></strong>!
                <small class="text-muted">(Not you? <a href="Login.php" class="text-decoration-none">change user
                        here</a>)</small>
            </p>
        </div>

        <div class="card-body p-4">
            <!-- Friends List -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user-friends me-2"></i>Friends List
                        </h5>
                        <span class="badge bg-light text-primary">
                            <?= count($friends); ?> Friend<?= count($friends) !== 1 ? 's' : ''; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($friends)): ?>
                        <form method="post" id="friendsForm">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAllFriends">
                                                    <label class="form-check-label fw-semibold" for="selectAllFriends">
                                                        Friend's Name
                                                    </label>
                                                </div>
                                            </th>
                                            <th class="border-0 text-center fw-semibold">
                                                <i class="fas fa-share-alt text-success me-2"></i>Shared Albums
                                            </th>
                                            <th class="border-0 text-center fw-semibold">
                                                <i class="fas fa-eye text-info me-2"></i>View Photos
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($friends as $friend): ?>
                                            <tr class="friend-row">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="form-check me-3">
                                                            <input type="checkbox" name="friends[]"
                                                                value="<?= $friend['UserId'] ?>"
                                                                class="form-check-input friend-checkbox">
                                                        </div>
                                                        <div class="friend-info">
                                                            <div class="friend-avatar me-3">
                                                                <i class="fas fa-user-circle text-primary fs-3"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-semibold text-dark">
                                                                    <?= htmlspecialchars($friend['FullName']) ?>
                                                                </div>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-handshake me-1"></i>Friend
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge <?= $friend['SharedAlbums'] > 0 ? 'bg-success' : 'bg-secondary'; ?> fs-6">
                                                        <?= $friend['SharedAlbums'] ?>
                                                        <?= $friend['SharedAlbums'] == 1 ? 'album' : 'albums'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="FriendPictures.php?friendId=<?= $friend['UserId'] ?>"
                                                        class="btn btn-outline-info btn-sm">
                                                        <i class="fas fa-images me-1"></i>View Photos
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                <div>
                                    <span class="text-muted small" id="selectedCount">0 friends selected</span>
                                </div>
                                <div class="btn-group">
                                    <button type="submit" name="defriend" class="btn btn-outline-danger"
                                        onclick="return confirmDefriend();" id="defriendBtn" disabled>
                                        <i class="fas fa-user-minus me-2"></i>Remove Selected
                                    </button>
                                    <a href="AddFriend.php" class="btn btn-primary">
                                        <i class="fas fa-user-plus me-2"></i>Add Friends
                                    </a>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-user-friends text-muted mb-4" style="font-size: 4rem;"></i>
                                <h3 class="text-muted mb-3">No Friends Yet</h3>
                                <p class="text-muted mb-4">Start building your social network by adding friends!</p>
                                <a href="AddFriend.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Add Your First Friend
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Friend Requests -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user-clock me-2"></i>Pending Friend Requests
                        </h5>
                        <span class="badge bg-light text-dark">
                            <?= count($friendRequests); ?> Request<?= count($friendRequests) !== 1 ? 's' : ''; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($friendRequests)): ?>
                        <form method="post" id="requestsForm">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAllRequests">
                                                    <label class="form-check-label fw-semibold" for="selectAllRequests">
                                                        Name
                                                    </label>
                                                </div>
                                            </th>
                                            <th class="border-0 text-center fw-semibold">
                                                <i class="fas fa-clock text-warning me-2"></i>Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($friendRequests as $request): ?>
                                            <tr class="request-row">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="form-check me-3">
                                                            <input type="checkbox" name="friend_requests[]"
                                                                value="<?= $request['UserId'] ?>"
                                                                class="form-check-input request-checkbox">
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <div class="request-avatar me-3">
                                                                <i class="fas fa-user-circle text-warning fs-3"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-semibold text-dark">
                                                                    <?= htmlspecialchars($request['FullName']) ?>
                                                                </div>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-paper-plane me-1"></i>Sent friend request
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-hourglass-half me-1"></i>Pending
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                <div>
                                    <span class="text-muted small" id="selectedRequestsCount">0 requests selected</span>
                                </div>
                                <div class="btn-group">
                                    <button type="submit" name="accept" class="btn btn-outline-success" id="acceptBtn"
                                        disabled>
                                        <i class="fas fa-check me-2"></i>Accept Selected
                                    </button>
                                    <button type="submit" name="decline" class="btn btn-outline-danger"
                                        onclick="return confirmDecline();" id="declineBtn" disabled>
                                        <i class="fas fa-times me-2"></i>Decline Selected
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mb-2">No Pending Requests</h5>
                            <p class="text-muted mb-0">You have no pending friend requests at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .friend-row,
    .request-row {
        transition: all 0.2s ease;
    }

    .friend-row:hover,
    .request-row:hover {
        transform: translateX(5px);
        background-color: rgba(var(--bs-primary-rgb), 0.05);
    }

    .friend-info,
    .request-info {
        display: flex;
        align-items: center;
    }

    .empty-state i {
        opacity: 0.3;
    }

    .table th {
        border-bottom: 2px solid #dee2e6;
    }

    .card-header.bg-gradient {
        background: linear-gradient(135deg, var(--bs-primary), #4a90e2) !important;
    }

    .card-header.bg-warning.bg-gradient {
        background: linear-gradient(135deg, var(--bs-warning), #f39c12) !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Handle select all for friends
        const selectAllFriends = document.getElementById('selectAllFriends');
        const friendCheckboxes = document.querySelectorAll('.friend-checkbox');
        const defriendBtn = document.getElementById('defriendBtn');
        const selectedCount = document.getElementById('selectedCount');

        // Handle select all for requests
        const selectAllRequests = document.getElementById('selectAllRequests');
        const requestCheckboxes = document.querySelectorAll('.request-checkbox');
        const acceptBtn = document.getElementById('acceptBtn');
        const declineBtn = document.getElementById('declineBtn');
        const selectedRequestsCount = document.getElementById('selectedRequestsCount');

        if (selectAllFriends) {
            selectAllFriends.addEventListener('change', function () {
                friendCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateFriendsButtons();
            });
        }

        if (selectAllRequests) {
            selectAllRequests.addEventListener('change', function () {
                requestCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateRequestsButtons();
            });
        }

        friendCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateFriendsButtons);
        });

        requestCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateRequestsButtons);
        });

        function updateFriendsButtons() {
            const checkedFriends = document.querySelectorAll('.friend-checkbox:checked');
            const count = checkedFriends.length;

            if (selectedCount) {
                selectedCount.textContent = `${count} friend${count !== 1 ? 's' : ''} selected`;
            }

            if (defriendBtn) {
                defriendBtn.disabled = count === 0;
            }

            if (selectAllFriends) {
                selectAllFriends.indeterminate = count > 0 && count < friendCheckboxes.length;
                selectAllFriends.checked = count === friendCheckboxes.length && count > 0;
            }
        }

        function updateRequestsButtons() {
            const checkedRequests = document.querySelectorAll('.request-checkbox:checked');
            const count = checkedRequests.length;

            if (selectedRequestsCount) {
                selectedRequestsCount.textContent = `${count} request${count !== 1 ? 's' : ''} selected`;
            }

            if (acceptBtn) acceptBtn.disabled = count === 0;
            if (declineBtn) declineBtn.disabled = count === 0;

            if (selectAllRequests) {
                selectAllRequests.indeterminate = count > 0 && count < requestCheckboxes.length;
                selectAllRequests.checked = count === requestCheckboxes.length && count > 0;
            }
        }

        // Initial update
        updateFriendsButtons();
        updateRequestsButtons();
    });

    function confirmDefriend() {
        const checkboxes = document.querySelectorAll('input[name="friends[]"]:checked');
        if (checkboxes.length === 0) {
            alert("Please select at least one friend to remove.");
            return false;
        }
        return confirm(`Are you sure you want to remove ${checkboxes.length} friend${checkboxes.length !== 1 ? 's' : ''} from your friends list?`);
    }

    function confirmDecline() {
        const checkboxes = document.querySelectorAll('input[name="friend_requests[]"]:checked');
        if (checkboxes.length === 0) {
            alert("Please select at least one friend request to decline.");
            return false;
        }
        return confirm(`Are you sure you want to decline ${checkboxes.length} friend request${checkboxes.length !== 1 ? 's' : ''}?`);
    }
</script>
<?php include(__DIR__ . '/../Common/Footer.php'); ?>