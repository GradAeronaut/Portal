<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@100;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/menu/menu.css">

<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>

<nav class="sinbad-menu" data-location="shape-sinbad">
    <div class="menu-logo">
        <a href="/start/" style="text-decoration:none; display:block;">
            <img src="/menu/logo_Sinbad_menu.svg"
                 class="menu-logo-svg"
                 alt="Sinbad logo">
        </a>
    </div>

    <button class="burger-btn">
        <span></span><span></span><span></span>
    </button>

    <ul class="menu-items">
        <li data-page="start"><a href="/start/">Start</a></li>
        <li data-page="shape-sinbad"><a href="/shape-sinbad/">Shape Sinbad</a></li>
        <li data-page="about"><a href="/about/">About</a></li>
    </ul>
    <?php
    // Проверка авторизации
    $isAuthorized = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    $membershipUrl = $isAuthorized 
        ? '/shape-sinbad/#membership-cards-section' 
        : '/auth/login/?next=' . urlencode('/shape-sinbad/#membership-cards-section');
    ?>
    <a href="<?= htmlspecialchars($membershipUrl) ?>" class="menu-membership-btn">Membership</a>
</nav>

<div class="mobile-menu">
    <a href="/start/">Start</a>
    <a href="/shape-sinbad/">Shape Sinbad</a>
    <a href="/about/">About</a>
</div>

<script src="/menu/menu.js"></script>

