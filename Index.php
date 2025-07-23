<?php
include_once 'Functions.php';
include("./common/header.php");

$user = $_SESSION['user'] ?? null;

?>
<div class="container mb-2">
    <div class="card border-light">
        <div class="card-body">
            <?php if (isset($user)) { ?>
                <p class="card-text text-start display-6 animated-border">
                    Hello, <?= $user->getName() ?>! <br><br>
                </p>
            <?php } ?>
            <h1 class="text-start mb-3 display-6 animated-border">
                Welcome to Algonquin Social Media Website<span class="text-primary"></span>!
            </h1>
            <?php if (!isset($user)) { ?>
                <p class="lead">
                    If this is your first time on our website, please <a href="./NewUser.php">sign up</a>.<br><br>
                    Already have an account? You can <a href="./Login.php">log in</a> now.<br>
                </p>
            <?php } ?>
        </div>
    </div>
</div>
<div class="container mt-5">
    <div class="row text-center">
        <div class="col-md-4 mb-4 mt-2">
            <i class="fas fa-users fa-3x mb-3 text-primary"></i>
            <h3>Connect</h3>
            <p>Find and connect with friends, family, and people who share your interests.</p>
        </div>
        <div class="col-md-4 mb-4 mt-2">
            <i class="fas fa-share-alt fa-3x mb-3 text-primary"></i>
            <h3>Share</h3>
            <p>Share your thoughts, photos, and experiences with your network.</p>
        </div>
        <div class="col-md-4 mb-4 mt-2">
            <i class="fas fa-globe fa-3x mb-3 text-primary"></i>
            <h3>Discover</h3>
            <p>Discover new content and expand your horizons through our platform.</p>
        </div>
    </div>
</div>

<?php include('./common/footer.php'); ?>