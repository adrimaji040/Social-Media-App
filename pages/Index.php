<?php
include_once __DIR__ . '/../src/Functions.php';
include(__DIR__ . "/../Common/Header.php");

$user = $_SESSION['user'] ?? null;

?>
<div class="container mb-2">
    <!-- Welcome Section -->
    <div class="card border-0 shadow-lg">
        <div class="card-header">
            <i class="fas fa-home me-2"></i>Welcome to Algonquin Social Media
        </div>
        <div class="card-body text-center">
            <?php if (isset($user)) { ?>
                <div class="mb-4">
                    <div class="display-6 animated-border mb-3">
                        <i class="fas fa-user-circle me-2"></i>Hello, <?= $user->getName() ?>!
                    </div>
                    <p class="lead text-muted">Welcome back! Ready to explore and share?</p>
                </div>
            <?php } ?>

            <h1 class="text-start mb-4 display-6 animated-border">
                <i class="fas fa-users me-2"></i>Welcome to Algonquin Social Media<span class="text-primary">!</span>
            </h1>

            <?php if (!isset($user)) { ?>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="alert alert-info border-0 shadow-sm">
                            <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Get Started</h5>
                            <p class="mb-3">Join our community to connect, share, and discover amazing content!</p>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="NewUser.php" class="btn btn-primary btn-lg me-md-2">
                                    <i class="fas fa-user-plus me-2"></i>Sign Up
                                </a>
                                <a href="Login.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Log In
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="container mt-5">
    <div class="row text-center">
        <div class="col-12 mb-4">
            <h2 class="display-6 mb-3">
                <span class="animated-border">Discover Amazing Features</span>
            </h2>
        </div>
    </div>
    <div class="row text-center g-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow">
                <div class="card-body">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-users fa-3x text-primary"></i>
                    </div>
                    <h4 class="card-title">Connect</h4>
                    <p class="card-text text-muted">Find and connect with friends, family, and people who share your
                        interests.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow">
                <div class="card-body">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-share-alt fa-3x text-success"></i>
                    </div>
                    <h4 class="card-title">Share</h4>
                    <p class="card-text text-muted">Share your thoughts, photos, and experiences with your network.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow">
                <div class="card-body">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-globe fa-3x text-info"></i>
                    </div>
                    <h4 class="card-title">Discover</h4>
                    <p class="card-text text-muted">Discover new content and expand your horizons through our platform.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../Common/Footer.php'); ?>