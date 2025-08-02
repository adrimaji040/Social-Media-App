<?php

include_once(__DIR__ . "/../src/EntityClassLib.php");
include_once(__DIR__ . "/../src/Functions.php");
require_once(__DIR__ . "/../src/SecurityMode.php");

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
$userAlbums = $user->fetchAllAlbums();


$selectedAlbumId = isset($_GET['album_id']) ? intval($_GET['album_id']) : null;
$selectedAlbum = null;
$errorMessage = '';

if ($selectedAlbumId) {
    try {
        $selectedAlbum = Album::read($selectedAlbumId);
        if ($selectedAlbum->getOwnerId() !== $user->getUserId()) {
            $errorMessage = "Access denied.";
            $selectedAlbum = null;
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Handle picture selection
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
            header("Location: MyPictures.php?album_id=$selectedAlbumId&picture_id=$pictureId");
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

<div class="container my-4">
    <div class="card border-0 shadow-lg">
        <div class="card-header bg-transparent text-center py-4">
            <h1 class="mb-3 animated-border display-6">
                <i class="fas fa-images text-primary me-3"></i>My Pictures
            </h1>
            <p class="lead mb-0">
                Welcome <strong class="text-primary"><?php echo htmlspecialchars($user->getName()); ?></strong>!
                <small class="text-muted">(Not you? <a href="Login.php" class="text-decoration-none">change user
                        here</a>)</small>
            </p>
        </div>

        <div class="card-body p-4">
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show disappearing-message">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (count($userAlbums) > 0): ?>
                <!-- Album Selector -->
                <div class="mb-4">
                    <form method="GET" action="MyPictures.php">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <label for="albumSelect" class="form-label fw-semibold">
                                    <i class="fas fa-folder-open text-warning me-2"></i>Select Album
                                </label>
                                <select class="form-select form-select-lg" id="albumSelect" name="album_id"
                                    onchange="this.form.submit()">
                                    <option value="">Choose an album to view pictures...</option>
                                    <?php foreach ($userAlbums as $album): ?>
                                        <option value="<?= $album->getAlbumId(); ?>" <?= ($selectedAlbumId == $album->getAlbumId()) ? 'selected' : ''; ?>>
                                            📁 <?= htmlspecialchars($album->getTitle()); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mt-3 mt-md-0">
                                <div class="d-grid gap-2">
                                    <a href="AddAlbum.php" class="btn btn-outline-primary">
                                        <i class="fas fa-plus me-2"></i>New Album
                                    </a>
                                    <?php if ($selectedAlbum): ?>
                                        <a href="UploadPictures.php?album_id=<?= $selectedAlbumId; ?>"
                                            class="btn btn-outline-success">
                                            <i class="fas fa-upload me-2"></i>Upload Photos
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if ($selectedAlbum): ?>
                    <div class="album-header mb-4 p-3 bg-light rounded-3">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-1 text-primary">
                                    <i class="fas fa-folder-open me-2"></i><?= htmlspecialchars($selectedAlbum->getTitle()); ?>
                                </h4>
                                <p class="text-muted mb-0">
                                    <?php
                                    $description = $selectedAlbum->getDescription();
                                    echo !empty($description) ? htmlspecialchars($description) : 'No description available';
                                    ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-primary fs-6">
                                    <?= count($pictures); ?>         <?= count($pictures) == 1 ? 'Photo' : 'Photos'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($pictures)): ?>
                        <?php if ($selectedPicture): ?>
                            <div class="row">
                                <!-- Main Image Section -->
                                <div class="col-lg-8 mb-4">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body p-0">
                                            <div class="main-image-container position-relative">
                                                <img src="<?= htmlspecialchars($selectedPicture->getFilePath()); ?>"
                                                    class="img-fluid rounded-top w-100"
                                                    style="max-height: 500px; object-fit: contain; background: #f8f9fa;"
                                                    alt="<?= htmlspecialchars($selectedPicture->getTitle()); ?>">
                                                <div
                                                    class="image-overlay position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-white p-3">
                                                    <h5 class="mb-1"><?= htmlspecialchars($selectedPicture->getTitle()); ?></h5>
                                                    <small>
                                                        <i class="fas fa-image me-2"></i>
                                                        Photo in <?= htmlspecialchars($selectedAlbum->getTitle()); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Thumbnail Navigation -->
                                    <?php if (count($pictures) > 1): ?>
                                        <div class="card border-0 shadow-sm mt-3">
                                            <div class="card-body">
                                                <h6 class="card-title mb-3">
                                                    <i class="fas fa-th me-2 text-primary"></i>Other Photos in Album
                                                </h6>
                                                <div class="thumbnail-bar d-flex flex-wrap gap-2">
                                                    <?php foreach ($pictures as $picture): ?>
                                                        <a href="MyPictures.php?album_id=<?= $selectedAlbumId; ?>&picture_id=<?= $picture->getPictureId(); ?>"
                                                            class="thumbnail-link">
                                                            <img src="<?= htmlspecialchars($picture->getThumbnailPath()); ?>"
                                                                alt="<?= htmlspecialchars($picture->getTitle()); ?>"
                                                                class="thumbnail-img rounded <?= ($picture->getPictureId() == $selectedPictureId) ? 'selected-thumbnail' : ''; ?>"
                                                                style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; transition: all 0.3s ease;">
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Details and Comments Section -->
                                <div class="col-lg-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-transparent">
                                            <h5 class="mb-0">
                                                <i class="fas fa-info-circle text-info me-2"></i>Photo Details
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-4">
                                                <h6 class="text-primary mb-2">Description</h6>
                                                <p class="text-muted">
                                                    <?php
                                                    $description = $selectedPicture->getDescription();
                                                    echo !empty($description) ? htmlspecialchars($description) : '<em>No description available</em>';
                                                    ?>
                                                </p>
                                            </div>

                                            <!-- Comments Section -->
                                            <div class="comments-section">
                                                <h6 class="text-primary mb-3">
                                                    <i class="fas fa-comments me-2"></i>Comments
                                                    <?php
                                                    $comments = $selectedPicture->fetchComments();
                                                    if (!empty($comments)): ?>
                                                        <span class="badge bg-primary ms-2"><?= count($comments); ?></span>
                                                    <?php endif; ?>
                                                </h6>

                                                <?php if (!empty($comments)): ?>
                                                    <div class="comments-list mb-4" style="max-height: 300px; overflow-y: auto;">
                                                        <?php foreach ($comments as $comment): ?>
                                                            <div class="comment-item bg-light rounded p-3 mb-3">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="comment-avatar me-3">
                                                                        <i class="fas fa-user-circle text-primary fs-4"></i>
                                                                    </div>
                                                                    <div class="comment-content flex-grow-1">
                                                                        <div class="comment-header mb-2">
                                                                            <strong
                                                                                class="text-primary"><?= htmlspecialchars($comment['Name']); ?></strong>
                                                                            <small class="text-muted ms-2">
                                                                                <i class="fas fa-clock me-1"></i>
                                                                                <?= date('M j, Y g:i A', strtotime($comment['Date_Created'])); ?>
                                                                            </small>
                                                                        </div>
                                                                        <div class="comment-text">
                                                                            <?php if ($SECURITY_MODE === "vulnerable"): ?>
                                                                                <p class="mb-0"><?= $comment['Comment_Text']; ?></p>
                                                                            <?php else: ?>
                                                                                <p class="mb-0"><?= htmlspecialchars($comment['Comment_Text']); ?>
                                                                                </p>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-center py-4 text-muted">
                                                        <i class="fas fa-comment-slash fs-3 mb-2"></i>
                                                        <p class="mb-0">No comments yet. Be the first to comment!</p>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Add Comment Form -->
                                                <form method="POST"
                                                    action="MyPictures.php?album_id=<?= $selectedAlbumId; ?>&picture_id=<?= $selectedPictureId; ?>"
                                                    class="add-comment-form">
                                                    <input type="hidden" name="picture_id" value="<?= $selectedPictureId; ?>">
                                                    <div class="mb-3">
                                                        <label for="comment_text" class="form-label">Add a comment</label>
                                                        <textarea class="form-control" id="comment_text" name="comment_text" rows="3"
                                                            placeholder="Share your thoughts about this photo..." required></textarea>
                                                    </div>
                                                    <div class="d-grid">
                                                        <button type="submit" name="add_comment" class="btn btn-primary">
                                                            <i class="fas fa-paper-plane me-2"></i>Post Comment
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>Picture not found.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-images text-muted mb-4" style="font-size: 4rem;"></i>
                                <h3 class="text-muted mb-3">No Pictures in This Album</h3>
                                <p class="text-muted mb-4">Start adding memories to this album!</p>
                                <a href="UploadPictures.php?album_id=<?= $selectedAlbumId; ?>" class="btn btn-primary btn-lg">
                                    <i class="fas fa-upload me-2"></i>Upload Pictures
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-folder-open text-muted mb-4" style="font-size: 4rem;"></i>
                            <h3 class="text-muted mb-3">Select an Album</h3>
                            <p class="text-muted mb-0">Choose an album from the dropdown above to view your pictures.</p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-folder-plus text-muted mb-4" style="font-size: 4rem;"></i>
                        <h3 class="text-muted mb-3">No Albums Yet</h3>
                        <p class="text-muted mb-4">Create your first album to start organizing your photos!</p>
                        <a href="AddAlbum.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Create Your First Album
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .thumbnail-img {
        border: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .thumbnail-img:hover {
        border-color: var(--bs-primary);
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .thumbnail-img.selected-thumbnail {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), 0.25);
    }

    .image-overlay {
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent) !important;
    }

    .comment-item {
        transition: transform 0.2s ease;
    }

    .comment-item:hover {
        transform: translateX(5px);
    }

    .main-image-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .empty-state i {
        opacity: 0.3;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Smooth scrolling for thumbnail clicks
        document.querySelectorAll('.thumbnail-link').forEach(link => {
            link.addEventListener('click', function (e) {
                // Add loading state
                const img = this.querySelector('img');
                img.style.opacity = '0.7';

                // Show loading cursor
                document.body.style.cursor = 'wait';

                setTimeout(() => {
                    document.body.style.cursor = 'default';
                }, 1000);
            });
        });

        // Auto-expand comment textarea
        const commentTextarea = document.getElementById('comment_text');
        if (commentTextarea) {
            commentTextarea.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }

        // Form submission loading state
        document.querySelector('.add-comment-form')?.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Posting...';
            submitBtn.disabled = true;

            // Re-enable after 5 seconds as fallback
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
    });
</script>
<?php require_once(__DIR__ . "/../Common/Footer.php"); ?>