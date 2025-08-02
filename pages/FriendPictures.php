<?php

include_once(__DIR__ . "/../src/EntityClassLib.php");
include_once(__DIR__ . "/../src/Functions.php");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure the user is authenticated
if (!isset($_SESSION['user']) || !($_SESSION['user'] instanceof User)) {
    $_SESSION['redirect_url'] = basename($_SERVER['PHP_SELF']);
    header("Location: Login.php");
    exit();
}

$user = $_SESSION['user'];

// Get friend's user ID from query parameters
$friendId = $_GET['friendId'] ?? null;
if (!$friendId) {
    die("Friend ID not specified.");
}

// Fetch the friend's data using a direct query
$pdo = getPDO();
$stmt = $pdo->prepare("SELECT * FROM User WHERE UserId = ?");
$stmt->execute([$friendId]);
$friendData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$friendData) {
    die("Friend not found.");
}

// Create a friend object
$friend = new User(
    $friendData['UserId'],
    $friendData['Name'],
    $friendData['Phone'] ?? null, 
    $friendData['Password'] ?? null 
);


// Fetch the friend's shared albums
$friendAlbums = $friend->fetchAllAlbums($accessibilityCode = 'shared');
$friendAlbumIds = array_map(function ($album) {
    return $album->getAlbumId();
}, $friendAlbums);

$selectedAlbumId = isset($_GET['album_id']) ? intval($_GET['album_id']) : null;
$selectedAlbum = null;
$errorMessage = '';

if ($selectedAlbumId && in_array($selectedAlbumId, $friendAlbumIds)) {
    try {
        $selectedAlbum = Album::read($selectedAlbumId);
        if ($selectedAlbum->getOwnerId() !== $friend->getUserId()) {
            $errorMessage = "Access denied.";
            $selectedAlbum = null;
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}


if ($selectedAlbum) {
    $pictures = $selectedAlbum->fetchAllPictures();
    $selectedPictureId = isset($_GET['picture_id']) ? intval($_GET['picture_id']) : null;

    if ($selectedPictureId) {
        try {
            $selectedPicture = Picture::read($selectedPictureId);
            if ($selectedPicture->getAlbumId() !== $selectedAlbum->getAlbumId()) {
                $errorMessage = "Picture not found in this album.";
                $selectedPicture = null;
            }
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    } else {
        // Default to the first picture if none is selected
        $selectedPicture = !empty($pictures) ? $pictures[0] : null;
        $selectedPictureId = $selectedPicture ? $selectedPicture->getPictureId() : null;
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $commentText = trim($_POST['comment_text']);
    $pictureId = intval($_POST['picture_id']);

    if (!empty($commentText)) {
        try {
            $selectedPicture = Picture::read($pictureId);
            $selectedPicture->addComment($user->getUserId(), $commentText);
            header("Location: FriendPictures.php?friendId=$friendId&album_id=$selectedAlbumId&picture_id=$pictureId");
            exit();
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    } else {
        $errorMessage = "Comment cannot be empty.";
    }
}

require_once(__DIR__ . "/../Common/Header.php");
?>
<h1 class="card-title text-center text-dark my-3 display-6 animated-border">
    Your friend <?= htmlspecialchars($friend->getName()) ?>'s Shared Pictures
</h1>
<div class="container mt-5">
    <div class="row mx-3 <?= count($friendAlbums) < 1 ? "d-none" : '' ?>">
        <div class="col-md-8">
            <form method="GET" action="FriendPictures.php">
                <input type="hidden" name="friendId" value="<?= htmlspecialchars($friendId); ?>">
                <div class="mb-3">
                    <select class="form-select" id="albumSelect" name="album_id" onchange="this.form.submit()">
                        <option value="">-- Select Album --</option>
                        <?php foreach ($friendAlbums as $album): ?>
                            <option value="<?= $album->getAlbumId(); ?>" <?= ($selectedAlbumId == $album->getAlbumId()) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($album->getTitle()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger disappearing-message ms-4">
            <?= htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>
    <?php if ($selectedAlbum): ?>
        <?php if (!empty($pictures)): ?>
            <?php if ($selectedPicture): ?>
                <div class="row mx-3">
                    <div class="col-md-8">
                        <div class="image-container">
                            <div class="main-image mb-3">
                                <img src="<?= htmlspecialchars($selectedPicture->getFilePath()); ?>" class="img-fluid">
                            </div>
                        </div>
                        <div class="thumbnail-bar d-flex overflow-auto mb-3">
                            <?php foreach ($pictures as $picture): ?>
                                <a href="FriendPictures.php?friendId=<?= $friendId; ?>&album_id=<?= $selectedAlbumId; ?>&picture_id=<?= $picture->getPictureId(); ?>" class="me-1">
                                    <img src="<?= htmlspecialchars($picture->getThumbnailPath()); ?>" alt="Thumbnail" class="thumbnail-img img-fluid <?= ($picture->getPictureId() == $selectedPictureId) ? 'selected-thumbnail' : ''; ?>">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-4 mb-5">
                        <div class="text-section">
                            <h5 class="mt-0 display-6" style="color:#007BFF;"><?= htmlspecialchars($selectedPicture->getTitle()); ?></h5>
                            <p><strong>Description: </strong><?= !empty(htmlspecialchars($selectedPicture->getDescription())) ? htmlspecialchars($selectedPicture->getDescription()) : "No description found."; ?></p>
                            <?php
                            $comments = $selectedPicture->fetchComments();
                            if (!empty($comments)): ?>
                                <h6 class="mt-4">Comments:</h6>
                                <div class="comments-section">
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="comment mb-2">
                                            <strong style="color: #007BFF;"><?= htmlspecialchars($comment['Name']); ?></strong>
                                            <p><?= htmlspecialchars($comment['Comment_Text']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>No comments yet.</p>
                            <?php endif; ?>
                            <form method="POST" action="FriendPictures.php?friendId=<?= $friendId; ?>&album_id=<?= $selectedAlbumId; ?>&picture_id=<?= $selectedPictureId; ?>">
                                <input type="hidden" name="picture_id" value="<?= $selectedPictureId; ?>">
                                <div class="mb-3">
                                    <textarea class="form-control" id="comment_text" name="comment_text" rows="3" placeholder="Leave a comment ..."></textarea>
                                </div>
                                <button type="submit" name="add_comment" class="btn btn-primary btn-sm">Add Comment</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger disappearing-message">Picture not found.</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="row mx-3">
            <div class="lead col-8 mt-4">No pictures in this album.</div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="row mx-3">
            <p class="lead fs-5 text-start mt-4"><?= count($friendAlbums) < 1 ? '<div class="text-center lead">This friend has no shared albums.' : "Please select an album to view pictures." ?></p>
        </div>
    <?php endif; ?>
</div>
<?php require_once(__DIR__ . "/../Common/Footer.php"); ?>


