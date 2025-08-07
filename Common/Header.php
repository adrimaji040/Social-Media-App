<?php
// Improved path detection for Windows/Unix compatibility
$currentScript = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$isInPages = strpos($currentScript, '/pages/') !== false;
require_once __DIR__ . "/../src/SecurityMode.php";
global $SECURITY_MODE;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <title>Algonquin Social Media</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="<?= $isInPages ? '../' : '' ?>Common/css/styles.css?v=<?= time() ?>">
    <script>
        // Theme management - Load saved theme before page renders
        (function () {
            const savedTheme = localStorage.getItem('bs-theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
</head>

<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg mb-2" id="main-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="http://www.algonquincollege.com" style="padding: 10px">
                <img src="<?= $isInPages ? '../' : '' ?>Common/img/AC2.png" alt="Algonquin College"
                    style="max-height: 30px; width:auto;" />
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php
                // Simple and reliable path generation
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];

                // Get the application root by removing the filename from the current script
                $currentPath = $_SERVER['SCRIPT_NAME'];
                $pathParts = explode('/', trim($currentPath, '/'));

                // Remove the filename (last part)
                array_pop($pathParts);

                // If we're in the pages directory, remove 'pages' to get the app root
                if (end($pathParts) === 'pages') {
                    array_pop($pathParts);
                }

                // Build the app root path
                $appRoot = empty($pathParts) ? '' : '/' . implode('/', $pathParts);

                // Generate clean absolute URLs
                $homeUrl = $protocol . $host . $appRoot . '/Index.php';
                $pagesUrl = $protocol . $host . $appRoot . '/pages/';
                ?>
                <ul class="navbar-nav w-100 justify-content-around">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $homeUrl ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $pagesUrl ?>MyFriends.php">My Friends</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $pagesUrl ?>MyAlbums.php">My Albums</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $pagesUrl ?>MyPictures.php">My Pictures</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $pagesUrl ?>UploadPictures.php">Upload Pictures</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $pagesUrl ?>EditProfile.php">Edit Profile</a>
                    </li>
                    <li class="nav-item me-2">
                        <button class="btn btn-outline-light btn-sm" id="theme-toggle" title="Toggle Dark/Light Theme">
                            <i class="fas fa-moon" id="theme-icon"></i>
                        </button>
                    </li>
                    <li class="nav-item me-2">
                        <?php if (isset($_SESSION['user'])): ?>
                            <a class="nav-link" href="<?= $pagesUrl ?>Logout.php">Log Out</a>
                        <?php else: ?>
                            <a class="nav-link" href="<?= $pagesUrl ?>Login.php">Log In</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Security Mode Button - Commented out
    <div class="position-fixed end-0 mt-5 me-3 px-3 py-2 rounded shadow 
    <?= $SECURITY_MODE === 'secure' ? 'bg-success' : 'bg-danger' ?> text-white fw-bold z-3"
        style="border-bottom-left-radius: .5rem; top: 60px;">
        Mode: <?= strtoupper($SECURITY_MODE ?? 'UNKNOWN') ?>
    </div>
    -->

    <div class="content px-4 flex-grow-1">