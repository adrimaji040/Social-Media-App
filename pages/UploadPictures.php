<?php
require_once(__DIR__ . "/../src/EntityClassLib.php");
require_once(__DIR__ . "/../src/Functions.php");

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
$albumId = $_POST['albumId'] ?? $_GET['album_id'] ?? null;
$album = null;

if ($albumId) {
    try {
        $album = Album::read($albumId);
        // Verify the user owns this album
        if ($album->getOwnerId() !== $user->getUserId()) {
            $albumId = null;
            $album = null;
        }
    } catch (Exception $e) {
        $albumId = null;
        $album = null;
    }
}

$txtTitle = $_POST['txtTitle'] ?? '';
$txtDescription = $_POST['txtDescription'] ?? '';

$successMessage = $_SESSION['successMessage'] ?? '';
unset($_SESSION['successMessage']);
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnUpload'])) {
    $albumId = $_POST['albumId'] ?? null;
    
    if (!$albumId) {
        $errorMessage = "Please select an album to upload the pictures.";
    } elseif (!isset($_FILES['txtUpload']) || empty($_FILES['txtUpload']['name'][0])) {
        $errorMessage = "No files selected for upload.";
    } else {
        try {
            $album = Album::read($albumId);
            // Verify the user owns this album
            if ($album->getOwnerId() !== $user->getUserId()) {
                $errorMessage = "You don't have permission to upload to this album.";
            } else {
                $supportedImageTypes = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);
                $uploadedFiles = [];
                $fileNames = is_array($_FILES['txtUpload']['name']) ? $_FILES['txtUpload']['name'] : [$_FILES['txtUpload']['name']];
                $tmpPaths = is_array($_FILES['txtUpload']['tmp_name']) ? $_FILES['txtUpload']['tmp_name'] : [$_FILES['txtUpload']['tmp_name']];
                $errorCodes = is_array($_FILES['txtUpload']['error']) ? $_FILES['txtUpload']['error'] : [$_FILES['txtUpload']['error']];

                for ($i = 0; $i < count($fileNames); $i++) {
                    $originalName = $fileNames[$i];
                    $tmpFilePath = $tmpPaths[$i];
                    $errorCode = $errorCodes[$i];

                    // Skip empty files
                    if (empty($originalName) || $errorCode == UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    if ($errorCode == UPLOAD_ERR_OK) {
                        $fileType = exif_imagetype($tmpFilePath);
                        if (!in_array($fileType, $supportedImageTypes)) {
                            $errorMessage .= "The file type of '{$originalName}' is not allowed. Please upload JPG, JPEG, GIF, or PNG files.<br>";
                            continue;
                        }
                        try {
                            $picture = new Picture($originalName, $albumId, $txtTitle, $txtDescription);
                            $filePath = $picture->saveToUploadFolder($tmpFilePath, $albumId);
                            $picture->create();
                            $uploadedFiles[] = $originalName;
                        } catch (Exception $e) {
                            $errorMessage .= "Error uploading file '{$originalName}': " . $e->getMessage() . "<br>";
                        }
                    } elseif ($errorCode == UPLOAD_ERR_INI_SIZE || $errorCode == UPLOAD_ERR_FORM_SIZE) {
                        $errorMessage .= "Error uploading file '{$originalName}': File is too large.<br>";
                    } else {
                        $errorMessage .= "Error uploading file '{$originalName}': Upload error (code: {$errorCode}).<br>";
                    }
                }
                if (!empty($uploadedFiles)) {
                    $_SESSION['successMessage'] = "Successfully uploaded " . count($uploadedFiles) . " image(s) to the album '" . $album->getTitle() . "'.";
                    header("Location: UploadPictures.php");
                    exit();
                }
            }
        } catch (Exception $e) {
            $errorMessage = "Error accessing album: " . $e->getMessage();
        }
    }
}
$albums = $user->fetchAllAlbums();

include(__DIR__ . "/../Common/Header.php");
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-transparent text-center py-4">
                    <h1 class="mb-3 animated-border display-6">
                        <i class="fas fa-cloud-upload-alt text-primary me-3"></i>Upload Pictures
                    </h1>
                    <p class="lead mb-0">
                        Welcome <strong class="text-primary"><?php echo htmlspecialchars($user->getName()); ?></strong>!
                        <small class="text-muted">(Not you? <a href="Login.php" class="text-decoration-none">change user
                                here</a>)</small>
                    </p>
                </div>

                <div class="card-body p-4">
                    <?php if (count($albums) > 0): ?>
                        <!-- Upload Guidelines -->
                        <div class="alert alert-info border-0 mb-4">
                            <h6 class="alert-heading mb-3">
                                <i class="fas fa-info-circle me-2"></i>Upload Guidelines
                            </h6>
                            <ul class="mb-0 small">
                                <li class="mb-1">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Supported formats:</strong> JPG, JPEG, GIF, PNG
                                </li>
                                <li class="mb-1">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Multiple uploads:</strong> Hold Shift or Ctrl while selecting images
                                </li>
                                <li class="mb-1">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Batch info:</strong> Title and description apply to all selected images
                                </li>
                            </ul>
                        </div>

                        <!-- Success/Error Messages -->
                        <?php if (!empty($successMessage)): ?>
                            <div class="alert alert-success alert-dismissible fade show disappearing-message">
                                <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php elseif (!empty($errorMessage)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="UploadPictures.php" method="post" enctype="multipart/form-data" id="uploadForm"
                            novalidate>
                            <!-- Album Selection -->
                            <div class="mb-4">
                                <label for="albumId" class="form-label fw-semibold">
                                    <i class="fas fa-folder text-warning me-2"></i>Select Album
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-lg" name="albumId" id="albumId" required>
                                    <option value="">Choose destination album...</option>
                                    <?php foreach ($albums as $album): ?>
                                        <option value="<?= $album->getAlbumId(); ?>" <?= (isset($_GET['album_id']) && $_GET['album_id'] == $album->getAlbumId()) ? 'selected' : ''; ?>>
                                            📁 <?= htmlspecialchars($album->getTitle()); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <small class="text-muted">
                                        <i class="fas fa-question-circle me-1"></i>
                                        Don't see your album? <a href="AddAlbum.php" class="text-decoration-none">Create a
                                            new one</a>
                                    </small>
                                </div>
                            </div>

                            <!-- File Upload -->
                            <div class="mb-4">
                                <label for="txtUpload" class="form-label fw-semibold">
                                    <i class="fas fa-images text-success me-2"></i>Select Images
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="file"
                                    class="form-control form-control-lg"
                                    name="txtUpload[]" 
                                    id="txtUpload" 
                                    multiple 
                                    accept=".jpg,.jpeg,.gif,.png" 
                                    required />
                                <div class="form-text">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Select multiple images by holding Ctrl (Windows) or Cmd (Mac) while clicking files
                                    </small>
                                </div>
                                <div id="filePreview" class="mt-3"></div>
                            </div>

                            <!-- Title Field -->
                            <div class="mb-4">
                                <label for="txtTitle" class="form-label fw-semibold">
                                    <i class="fas fa-tag text-info me-2"></i>Title
                                    <small class="text-muted fw-normal">(Applied to all images)</small>
                                </label>
                                <input type="text" class="form-control form-control-lg" name="txtTitle" id="txtTitle"
                                    placeholder="Enter a descriptive title for your photos..." maxlength="100" />
                                <div class="form-text">
                                    <small class="text-muted">
                                        <span id="titleCount">0</span>/100 characters
                                    </small>
                                </div>
                            </div>

                            <!-- Description Field -->
                            <div class="mb-4">
                                <label for="txtDescription" class="form-label fw-semibold">
                                    <i class="fas fa-align-left text-secondary me-2"></i>Description
                                    <small class="text-muted fw-normal">(Applied to all images)</small>
                                </label>
                                <textarea class="form-control" name="txtDescription" id="txtDescription" rows="4"
                                    placeholder="Describe these photos... What's the story behind them?"
                                    maxlength="500"></textarea>
                                <div class="form-text">
                                    <small class="text-muted">
                                        <span id="descCount">0</span>/500 characters
                                    </small>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-between pt-3 border-top">
                                <div>
                                    <button type="button" class="btn btn-outline-secondary me-2" onclick="clearForm()">
                                        <i class="fas fa-eraser me-2"></i>Clear Form
                                    </button>
                                    <a href="MyAlbums.php" class="btn btn-outline-info">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Albums
                                    </a>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg px-4" name="btnUpload" id="submitBtn">
                                    <i class="fas fa-upload me-2"></i>Upload Images
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-folder-plus text-muted mb-4" style="font-size: 4rem;"></i>
                                <h3 class="text-muted mb-3">No Albums Available</h3>
                                <p class="text-muted mb-4">You need to create an album before uploading pictures.</p>
                                <a href="AddAlbum.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus me-2"></i>Create Your First Album
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Tips Card -->
            <?php if (count($albums) > 0): ?>
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-3">
                            <i class="fas fa-lightbulb text-warning me-2"></i>Pro Tips
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled small text-muted">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Compress large images before uploading
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Use descriptive titles for better organization
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled small text-muted">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Upload related photos together
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Add meaningful descriptions
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .upload-area {
        transition: all 0.3s ease;
        cursor: pointer;
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .upload-area:hover {
        border-color: var(--bs-success) !important;
        background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
    }

    .upload-area.dragover {
        border-color: var(--bs-success) !important;
        background: linear-gradient(135deg, #d1ecf1 0%, #b8daff 100%);
        transform: scale(1.02);
    }

    .file-preview-item {
        display: inline-block;
        margin: 5px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #f8f9fa;
        position: relative;
    }

    .file-preview-item img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
    }

    .file-preview-item .remove-file {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        cursor: pointer;
    }

    .progress-container {
        margin-top: 20px;
        display: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('txtUpload');
    const filePreview = document.getElementById('filePreview');
    const titleInput = document.getElementById('txtTitle');
    const descInput = document.getElementById('txtDescription');
    const titleCount = document.getElementById('titleCount');
    const descCount = document.getElementById('descCount');
    const form = document.getElementById('uploadForm');
    const submitBtn = document.getElementById('submitBtn');

    // Character counters
    function updateCharCount(input, counter) {
        const count = input.value.length;
        counter.textContent = count;
        const maxLength = input.getAttribute('maxlength');
        if (maxLength) {
            const percentage = (count / maxLength) * 100;
            counter.className = percentage > 80 ? 'text-warning' : percentage > 90 ? 'text-danger' : 'text-muted';
        }
    }

    if (titleInput && titleCount) {
        titleInput.addEventListener('input', () => updateCharCount(titleInput, titleCount));
    }

    if (descInput && descCount) {
        descInput.addEventListener('input', () => updateCharCount(descInput, descCount));
    }

    // File preview
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            updateFilePreview();
        });
    }

    function updateFilePreview() {
        if (!filePreview) return;
        
        filePreview.innerHTML = '';
        
        if (fileInput.files.length === 0) {
            return;
        }

        const previewContainer = document.createElement('div');
        previewContainer.className = 'file-preview-container';
        
        const previewTitle = document.createElement('h6');
        previewTitle.innerHTML = '<i class="fas fa-eye me-2"></i>Selected Files (' + fileInput.files.length + ')';
        previewTitle.className = 'text-muted mb-3';
        previewContainer.appendChild(previewTitle);

        for (let i = 0; i < Math.min(fileInput.files.length, 10); i++) {
            const file = fileInput.files[i];
            if (file.type.startsWith('image/')) {
                const previewItem = document.createElement('div');
                previewItem.className = 'file-preview-item d-inline-block m-2 p-2 border rounded';
                
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);
                img.style.width = '80px';
                img.style.height = '80px';
                img.style.objectFit = 'cover';
                img.className = 'rounded';
                
                const fileName = document.createElement('div');
                fileName.textContent = file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name;
                fileName.className = 'small text-muted mt-1 text-center';
                
                previewItem.appendChild(img);
                previewItem.appendChild(fileName);
                previewContainer.appendChild(previewItem);
            }
        }

        if (fileInput.files.length > 10) {
            const moreText = document.createElement('div');
            moreText.textContent = '... and ' + (fileInput.files.length - 10) + ' more files';
            moreText.className = 'text-muted small mt-2';
            previewContainer.appendChild(moreText);
        }

        filePreview.appendChild(previewContainer);
    }

    // Form validation and submission
    if (form) {
        form.addEventListener('submit', function (e) {
            let isValid = true;

            // Validate album selection
            const albumSelect = document.getElementById('albumId');
            if (!albumSelect.value) {
                albumSelect.classList.add('is-invalid');
                isValid = false;
            } else {
                albumSelect.classList.remove('is-invalid');
            }

            // Validate file selection
            if (fileInput.files.length === 0) {
                fileInput.classList.add('is-invalid');
                isValid = false;
            } else {
                fileInput.classList.remove('is-invalid');
            }

            if (!isValid) {
                e.preventDefault();
                return;
            }

            // Show loading state
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
                submitBtn.disabled = true;
            }
        });
    }

    // Real-time validation
    document.getElementById('albumId')?.addEventListener('change', function () {
        this.classList.remove('is-invalid');
    });

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            this.classList.remove('is-invalid');
        });
    }
});

function clearForm() {
    if (confirm('Are you sure you want to clear all form data and selected files?')) {
        document.getElementById('uploadForm').reset();
        document.getElementById('filePreview').innerHTML = '';
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        // Reset character counters
        const titleCount = document.getElementById('titleCount');
        const descCount = document.getElementById('descCount');
        if (titleCount) titleCount.textContent = '0';
        if (descCount) descCount.textContent = '0';
    }
}
</script>

<?php include(__DIR__ . '/../Common/Footer.php'); ?>