<?php
require_once 'EntityClassLib.php';
require_once 'Functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !($_SESSION['user'] instanceof User)) {
    $_SESSION['redirect_url'] = basename($_SERVER['PHP_SELF']);
    header("Location: Login.php"); // Redirect to login page
    exit();
}

// Get the logged-in user
$user = $_SESSION['user'];
$albumId = $_POST['albumId'] ?? null;
$album = $albumId ? Album::read($albumId) : null;

$txtTitle = $_POST['txtTitle'] ?? '';
$txtDescription = $_POST['txtDescription'] ?? '';

$successMessage = $_SESSION['successMessage']?? '';
unset($_SESSION['successMessage']);
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnUpload'])) {
    if (isset($_FILES['txtUpload']) && $albumId) {
        $supportedImageTypes = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);
        $uploadedFiles = [];
        $fileNames = is_array($_FILES['txtUpload']['name']) ? $_FILES['txtUpload']['name'] : [$_FILES['txtUpload']['name']];
        $tmpPaths = is_array($_FILES['txtUpload']['tmp_name']) ? $_FILES['txtUpload']['tmp_name'] : [$_FILES['txtUpload']['tmp_name']];
        $errorCodes = is_array($_FILES['txtUpload']['error']) ? $_FILES['txtUpload']['error'] : [$_FILES['txtUpload']['error']];

        for ($i = 0; $i < count($fileNames); $i++) {
            $originalName = $fileNames[$i];
            $tmpFilePath = $tmpPaths[$i];
            $errorCode = $errorCodes[$i];

            if ($errorCode == UPLOAD_ERR_OK) {
                $fileType = exif_imagetype($tmpFilePath);
                if (!in_array($fileType, $supportedImageTypes)) {
                    $errorMessage = "The file type of '{$originalName}' is not allowed. Please upload JPG, JPEG, GIF, or PNG files.<br>";
                    continue;
                }
                try {
                    $picture = new Picture($originalName, $albumId, $txtTitle, $txtDescription);
                    $filePath = $picture->saveToUploadFolder($tmpFilePath, $albumId);
                    $picture->create();
                    $uploadedFiles[] = $originalName;
                } catch (Exception $e) {
                    $errorMessage = "Error uploading file '{$originalName}': " . $e->getMessage() . "<br>";
                }
            } elseif ($errorCode == 1) {
                $errorMessage = "Error uploading file '{$originalName}': File is too large.<br>";
            } elseif ($errorCode == 4) {
                $errorMessage = "No files selected for upload.";
            } else {
                $errorMessage = "Error occurred while uploading the file(s). Please try again later.<br/>";
            }
        }
        if (!empty($uploadedFiles)) {
            $_SESSION['successMessage'] = "Successfully uploaded " . count($uploadedFiles) . " image(s) to the album '" . $album->getTitle() . "'.";
            header("Location: UploadPictures.php");
            exit();
        }
    } elseif (!$albumId) {
        $errorMessage = "Please select an album to upload the pictures.";
    }
}
$albums = $user->fetchAllAlbums();

include(__DIR__ . "/../Common/Header.php");
?>
<div class="container mb-5 mt-3">
    <div class="shadow py-2 px-3 mb-5 bg-body-tertiary rounded" style="max-width: 60vw; min-height:380px; margin: auto;">
        <div class="card-body">
            <h1 class="card-title text-center text-dark mb-3 display-6 animated-border">Upload Pictures</h1>
            <div class="container">
                <?php if (count($albums) > 0): ?>
                    <div class="text-start">
                        <small>
                            <ul>
                                <li>Accepted image types: JPG, JPEG, GIF and PNG. </li>
                                <li>You can upload multiple pictures at a time by holding the shift key while selecting images.</li>
                                <li>When uploading multiple pictures, the title and description will apply to all pictures.</li>
                            </ul>
                        </small>
                    </div>
                    
                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success text-center mt-2 disappearing-message" role="alert">
                            <?php echo $successMessage; ?>
                        </div>
                    <?php elseif (!empty($errorMessage)): ?>
                        <div class="alert alert-danger text-center mt-2" role="alert">
                            <?php echo $errorMessage; ?>
                        </div>
                    <?php endif; ?>
                    <form class="my-3" action="UploadPictures.php" method="post" enctype="multipart/form-data">
                        <div class="form-group mb-3">
                            <select class="form-control" name="albumId" id="albumId">
                                <option value="" disabled selected>... Select an Album ...</option>
                                <?php foreach ($albums as $album): ?>
                                    <option value="<?= $album->getAlbumId(); ?>"><?= $album->getTitle(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mb-3 ps-1">
                            <label for="txtUpload">Image(s) to Upload:</label>
                            <input type="file" class="form-control-file" name="txtUpload[]" id="txtUpload" multiple accept=".jpg,.jpeg,.gif,.png" />
                        </div>
                        <div class="form-group mb-3">
                            <input type="text" class="form-control" name="txtTitle" id="txtTitle" placeholder="Add a Title ..." />
                        </div>
                        <div class="form-group mb-3">
                            <textarea class="form-control" name="txtDescription" id="txtDescription" placeholder="Add a Description ..." rows="4"></textarea>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary" name="btnUpload">Submit</button>
                            <button type="reset" class="btn btn-secondary">Clear</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="lead fs-5 text-center my-5 pt-4" role="alert">
                        You do not have any albums. Please <a href="AddAlbum.php">create an album</a> first.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../Common/Footer.php'); ?>
