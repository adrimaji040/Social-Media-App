<?php
include_once 'src/Functions.php';
include("./Common/Header.php");

$user = $_SESSION['user'] ?? null;

?>
<div class="container mb-2">
    <div class="card border-0 shadow-sm">
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
                <p class="lead text-muted">
                    If this is your first time on our website, please <a href="./pages/NewUser.php"
                        class="link-primary fw-bold">sign up</a>.<br><br>
                    Already have an account? You can <a href="./pages/Login.php" class="link-primary fw-bold">log in</a>
                    now.<br>
                </p>
            <?php } ?>
        </div>
    </div>
</div>
<div class="container mt-5">
    <div class="row text-center g-4">
        <div class="col-md-4">
            <div class="feature-card">
                <i class="fas fa-users fa-3x mb-3 feature-icon text-primary"></i>
                <h3 class="h4 mb-3">Connect</h3>
                <p class="text-muted">Find and connect with friends, family, and people who share your interests.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <i class="fas fa-share-alt fa-3x mb-3 feature-icon text-success"></i>
                <h3 class="h4 mb-3">Share</h3>
                <p class="text-muted">Share your thoughts, photos, and experiences with your network.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <i class="fas fa-globe fa-3x mb-3 feature-icon text-info"></i>
                <h3 class="h4 mb-3">Discover</h3>
                <p class="text-muted">Discover new content and expand your horizons through our platform.</p>
            </div>
        </div>
    </div>
</div>

<?php include('./Common/Footer.php'); ?>