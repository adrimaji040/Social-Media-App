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
<div class="container mb-5 mt-3">
    <div class="shadow py-2 px-3 mb-5 bg-body-tertiary rounded" style="min-height: 380px; margin: auto;">
        <h1 class="mb-2 animated-border display-6">My Albums</h1>
        <p class="text-center lead">Welcome <b><?php echo htmlspecialchars($user->getName()); ?></b>! (Not you? <a href="Login.php">change user here</a>)</p>
        <!-- Success message -->
        <?php if (!empty($successMessage)): ?>
            <div id="successMessage" class="alert alert-success disappearing-message"><?php echo $successMessage; ?></div>
        <?php endif;
        if (empty($albums)) { ?>
            <p class="fs-5 my-5 text-center lead">You do not have any albums. <a href="AddAlbum.php">Create a New Album.</a></p>
        <?php } else { ?>
            <form method="post" action="MyAlbums.php" style="max-width: 80vw;" class="ms-3">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-5 text-center">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Title</th>
                                <th style="width: 20%;">Number of Pictures</th>
                                <th style="width: 40%;">Accessibility</th>
                                <th style="width: 20%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($albums as $album): ?>
                                <tr>
                                    <td><a href="MyPictures.php?album_id=<?php echo $album['Album_Id']; ?>"><?php echo htmlspecialchars($album['Title']); ?></a></td>
                                    <td><?php echo $album['PictureCount']; ?></td>
                                    <td>
                                        <select class="form-select" name="accessibility[<?php echo $album['Album_Id']; ?>]">
                                            <?php foreach ($options as $option): ?>
                                                <option value="<?php echo $option['Accessibility_Code']; ?>"
                                                    <?php echo ($album['Accessibility_Code'] == $option['Accessibility_Code']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($option['Description']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <a href="MyAlbums.php?delete_album=<?php echo $album['Album_Id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this album? All pictures in the album will be deleted.');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mb-2">
                    <button type="submit" name="save_changes" class="btn btn-success btn-sm me-2">Save Changes</button>
                    <a href="AddAlbum.php" class="btn btn-primary btn-sm" style="text-decoration: none;">New Album</a>
                </div>

            </form>
        <?php }
        ?>
    </div>
</div>

<?php include(__DIR__ . '/../Common/Footer.php'); ?>
