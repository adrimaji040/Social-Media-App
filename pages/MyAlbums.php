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
    header("Location: Login.php");
    exit();
}

$user = $_SESSION['user'];
$options = getAccessibilityOptions(); // Fetch accessibility options

// Handle form submission to update accessibility
$successMessage = '';
if (isset($_SESSION['successMessage'])) {
    $successMessage = $_SESSION['successMessage'];
    unset($_SESSION['successMessage']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_changes'])) {
    try {
        foreach ($_POST['accessibility'] as $albumId => $newAccessibility) {
            updateAlbumAccessibility($albumId, $newAccessibility);
        }
        $successMessage = "Accessibility updated successfully!";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }

    // Refresh the albums data to reflect changes
    $albums = getUserAlbums($user->getUserId());
} else {
    // Initial fetch of albums
    $albums = getUserAlbums($user->getUserId());
}

// Handle delete request
if (isset($_GET['delete_album'])) {
    $albumId = $_GET['delete_album'];
    try {
        Album::delete($albumId);
        header("Location: MyAlbums.php");
        $_SESSION['successMessage'] = "Album deleted successfully!";
        exit();
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
    $albums = getUserAlbums($user->getUserId());
}
?>
<div class="container my-4">
    <div class="card border-0 shadow-lg">
        <div class="card-header bg-transparent text-center py-4">
            <h1 class="mb-3 animated-border display-6">
                <i class="fas fa-photo-video text-primary me-3"></i>My Albums
            </h1>
            <p class="lead mb-0">
                Welcome <strong class="text-primary"><?php echo htmlspecialchars($user->getName()); ?></strong>!
                <small class="text-muted">(Not you? <a href="Login.php" class="text-decoration-none">change user here</a>)</small>
            </p>
        </div>
        
        <div class="card-body p-4">
            <!-- Success message -->
            <?php if (!empty($successMessage)): ?>
                <div id="successMessage" class="alert alert-success alert-dismissible fade show disappearing-message">
                    <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($albums)): ?>
                <div class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-images text-muted mb-4" style="font-size: 4rem;"></i>
                        <h3 class="text-muted mb-3">No Albums Yet</h3>
                        <p class="text-muted mb-4">Start building your photo collection by creating your first album!</p>
                        <a href="AddAlbum.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Create Your First Album
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <form method="post" action="MyAlbums.php" id="albumsForm">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-muted mb-0">
                            <i class="fas fa-folder me-2"></i>
                            <?php echo count($albums); ?> Album<?php echo count($albums) !== 1 ? 's' : ''; ?>
                        </h5>
                        <a href="AddAlbum.php" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>New Album
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-semibold">
                                        <i class="fas fa-tag me-2 text-primary"></i>Album Title
                                    </th>
                                    <th class="border-0 fw-semibold text-center">
                                        <i class="fas fa-images me-2 text-success"></i>Pictures
                                    </th>
                                    <th class="border-0 fw-semibold text-center">
                                        <i class="fas fa-eye me-2 text-info"></i>Privacy
                                    </th>
                                    <th class="border-0 fw-semibold text-center">
                                        <i class="fas fa-cog me-2 text-warning"></i>Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($albums as $album): ?>
                                    <tr class="album-row">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="album-icon me-3">
                                                    <i class="fas fa-folder text-primary"></i>
                                                </div>
                                                <div>
                                                    <a href="MyPictures.php?album_id=<?php echo $album['Album_Id']; ?>" 
                                                       class="fw-semibold text-decoration-none album-link">
                                                        <?php echo htmlspecialchars($album['Title']); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border">
                                                <?php echo $album['PictureCount']; ?> 
                                                <?php echo $album['PictureCount'] == 1 ? 'photo' : 'photos'; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <select class="form-select form-select-sm privacy-select" 
                                                    name="accessibility[<?php echo $album['Album_Id']; ?>]">
                                                <?php foreach ($options as $option): ?>
                                                    <option value="<?php echo $option['Accessibility_Code']; ?>"
                                                        <?php echo ($album['Accessibility_Code'] == $option['Accessibility_Code']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($option['Description']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="MyPictures.php?album_id=<?php echo $album['Album_Id']; ?>" 
                                                   class="btn btn-outline-success btn-sm" 
                                                   title="View Pictures">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm delete-album-btn" 
                                                        data-album-id="<?php echo $album['Album_Id']; ?>"
                                                        data-album-title="<?php echo htmlspecialchars($album['Title']); ?>"
                                                        title="Delete Album">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Select privacy settings and click Save Changes to apply
                        </div>
                        <button type="submit" name="save_changes" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Album
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you sure you want to delete the album "<strong id="albumToDelete"></strong>"?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-warning me-2"></i>
                    <strong>Warning:</strong> All pictures in this album will be permanently deleted. This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>Delete Album
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete button clicks
    document.querySelectorAll('.delete-album-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const albumId = this.dataset.albumId;
            const albumTitle = this.dataset.albumTitle;
            
            document.getElementById('albumToDelete').textContent = albumTitle;
            document.getElementById('confirmDeleteBtn').href = `MyAlbums.php?delete_album=${albumId}`;
            
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

    // Handle album rows hover animation
    document.querySelectorAll('.album-row').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
});
</script>

<?php include(__DIR__ . '/../Common/Footer.php'); ?>


