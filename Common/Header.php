<?php
require_once "SecurityMode.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en" style="position: relative; min-height: 100%;">

<head>
    <title>Algonquin Social Media</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="Common/css/styles.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-2">
        <div class="container-fluid">
            <a class="navbar-brand" href="http://www.algonquincollege.com" style="padding: 10px">
                <img src="Common/img/AC2.png" alt="Algonquin College" style="max-height: 30px; width:auto;" />
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav w-100 justify-content-around">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="Index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="MyFriends.php">My Friends</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="MyAlbums.php">My Albums</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="MyPictures.php">My Pictures</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="UploadPictures.php">Upload Pictures</a>
                    </li>
                    <li class="nav-item">
                            <a class="nav-link" href="EditProfile.php">Edit Profile</a>
                        </li>
                    <li class="nav-item ms-auto me-2">
                        <?php if (isset($_SESSION['user'])): ?>
                            <a class="nav-link" href="Logout.php">Log Out</a>
                        <?php else: ?>
                            <a class="nav-link" href="Login.php">Log In</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="position-fixed end-0 mt-5 me-3 px-3 py-2 rounded shadow 
    <?= $SECURITY_MODE === 'secure' ? 'bg-success' : 'bg-danger' ?> text-white fw-bold z-3"
        style="border-bottom-left-radius: .5rem; top: 60px;">
        Mode: <?= strtoupper($SECURITY_MODE) ?>
    </div>

    <div class="content px-4">
