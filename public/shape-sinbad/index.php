<?php
// ============================================================
// GATE АВТОРИЗАЦИИ - первая строка, до любого другого кода
// ============================================================
session_start();

/**
 * Рендерит пустую страницу ожидания и при необходимости запускает редирект.
 */
function renderInitializingPage(string $message, ?string $redirectUrl = null): void {
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Initializing Control Panel</title>
        <style>
            :root { color-scheme: light dark; }
            body {
                margin: 0;
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #0f1114;
                color: #e5e7eb;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }
            .shape-init-text {
                font-size: 16px;
                letter-spacing: 0.4px;
                text-align: center;
                opacity: 0.85;
            }
        </style>
        <?php if ($redirectUrl): ?>
            <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
    </head>
    <body>
        <div class="shape-init-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php if ($redirectUrl): ?>
            <script>
                window.location.replace('<?= htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') ?>');
            </script>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}

// Проверка авторизации
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
    // Редирект на форму логина с возвратом на Shape
    header('Location: /auth/login/?next=' . urlencode('/shape-sinbad/'));
    exit;
}

// ============================================================
// ШАГ 2: Загрузка данных пользователя из БД
// ============================================================
require_once __DIR__ . '/../php/log_visit.php';

$user = [
    'id' => 0,
    'display_name' => '',
    'username' => '',
    'public_id' => '',
    'role' => 'standard'
];

try {
    $config = require __DIR__ . '/../config/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Загрузка данных пользователя из БД
    $userId = (int)$_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT id, display_name, username, public_id, role, email_verified FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        // Проверка подтверждения email - если не подтвержден, редирект на логин
        $emailVerified = (int)($row['email_verified'] ?? 0);
        if ($emailVerified !== 1) {
            // Редирект на логин с сообщением о необходимости подтверждения email
            header('Location: /auth/login/?error=email_not_verified&message=' . urlencode('Please confirm your email'));
            exit;
        }
        
        $user['id'] = (int)$row['id'];
        $user['display_name'] = $row['display_name'] ?? '';
        $user['username'] = $row['username'] ?? '';
        $user['public_id'] = $row['public_id'] ?? '';
        $user['role'] = $row['role'] ?? 'standard';
        
        // Обновляем public_id в сессии если нужно
        if (!isset($_SESSION['public_id']) || $_SESSION['public_id'] !== $user['public_id']) {
            $_SESSION['public_id'] = $user['public_id'];
        }
    } else {
        // Пользователь не найден в БД - редирект на логин
        header('Location: /auth/login/?next=' . urlencode('/shape-sinbad/'));
        exit;
    }
} catch (Exception $e) {
    // Ошибка БД - редирект на логин
    header('Location: /auth/login/?next=' . urlencode('/shape-sinbad/'));
    exit;
}

$pid = $user['public_id'];

// ============================================================
// ШАГ 3: Инициация форумной сессии через новое SSO перед рендером Shape
// ============================================================
$hasXfSession = !empty($_COOKIE['xf_session']) || !empty($_COOKIE['xf_user']);

if (!$hasXfSession) {
    // Загружаем конфигурацию SSO (новый путь с fallback на старый)
    $ssoConfigPathNew = __DIR__ . '/../config/sso/sso_config.php';
    $ssoConfigPathOld = __DIR__ . '/../config/sso_config.php';
    $ssoConfig = file_exists($ssoConfigPathNew) ? require $ssoConfigPathNew : (file_exists($ssoConfigPathOld) ? require $ssoConfigPathOld : []);

    $tokenLifetime = isset($ssoConfig['token_lifetime']) ? (int)$ssoConfig['token_lifetime'] : 300;
    $portalBase = rtrim($ssoConfig['portal_url'] ?? $ssoConfig['portal_base'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? '')), '/');
    $xfBase = rtrim($ssoConfig['xenforo_url'] ?? $ssoConfig['xf_base'] ?? '/forum', '/');

    try {
        $ssoToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + max(60, $tokenLifetime));

        // Сохраняем одноразовый токен в portal.sso_tokens (тем же способом, что /app/sso/xf_generate_token.php)
        $insert = $pdo->prepare("
            INSERT INTO sso_tokens (user_id, token, expires_at, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $insert->execute([(int)$user['id'], $ssoToken, $expiresAt]);

        // Чистим протухшие токены опционально
        try {
            $cleanup = $pdo->prepare("DELETE FROM sso_tokens WHERE expires_at < NOW()");
            $cleanup->execute();
        } catch (Exception $cleanupError) {
            // Не блокируем рендер при ошибке очистки
        }

        $returnUrl = $portalBase . '/shape-sinbad/';
        $ssoForwardUrl = $xfBase . '/sso/forward'
            . '?token=' . urlencode($ssoToken)
            . '&sso=1'
            . '&return_url=' . urlencode($returnUrl);

        // Показываем пустую страницу и моментально уводим в SSO forward
        renderInitializingPage('Initializing Control Panel', $ssoForwardUrl);
    } catch (Exception $e) {
        error_log('SSO initialization failed: ' . $e->getMessage());
        renderInitializingPage('SSO initialization failed. Please try again later.');
    }
}

// Log visit
if (!empty($pid)) {
    log_visit($pid, $_SERVER['REQUEST_URI'] ?? '/shape-sinbad');
}

include $_SERVER['DOCUMENT_ROOT'].'/menu/menu.php';
?>
<link rel="stylesheet" href="/shape-sinbad/style.css?v=<?= time() ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500&family=Playfair+Display:opsz,wght@8..144,100..900&display=swap" rel="stylesheet">

<!-- PayPal SDK (подключен один раз) -->
<?php
// Получаем PayPal Client ID из конфигурации (нужно будет настроить)
$paypalClientId = 'AalTLip3WibUP_ZYQYczSQW5XZ0JeIMxhoNsr3OanvrpAamrs43bVgk3KWhFpRhMjLOdtvGBkYINMcEc';
?>
<script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars($paypalClientId) ?>&currency=USD"></script>

<style>
    body {
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #f8f9fa;
    }



    /* БЛОК 2: Iframe KNEEBOARD */
    .shape-kneeboard-section {
        height: 80vh;
        min-height: 700px;
        background: #ffffff;
        position: relative;
    }

    .shape-kneeboard-frame {
        width: 100%;
        height: 100%;
        border: none;
        display: block;
    }

    /* KNEEBOARD Initialization Overlay */
    .kneeboard-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #1A1D21;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10;
        pointer-events: none;
    }

    .kneeboard-overlay.hidden {
        display: none;
    }

    .kneeboard-cross {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    .kneeboard-cross line {
        stroke-width: 2.5px;
        vector-effect: non-scaling-stroke;
    }

    .kneeboard-init-text {
        position: relative;
        color: #B8BDC6;
        font-size: 13px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        margin-top: 20px;
        text-align: center;
        z-index: 1;
    }

    /* БЛОК 3: Карта */
    .shape-map-section {
        height: 500px;
        background: #e9ecef;
    }

    .shape-map-section .map-container {
        height: 100%;
    }

    /* БЛОК 4: Футер/контент */
    .shape-footer-section {
        min-height: 1000px;
    }

    /* Контейнер для Timeline и Roadmap */
    .shape-timeline-roadmap-container {
        display: flex;
        width: 100%;
        margin-top: 300px;
        padding: 0;
        box-sizing: border-box;
    }

    /* Левая колонка - Timeline и Calculator */
    .shape-timeline-column {
        width: 50%;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: flex-start;
        box-sizing: border-box;
    }

    /* Правая колонка - Roadmap (резерв) */
    .shape-roadmap-column {
        width: 50%;
        flex-shrink: 0;
        box-sizing: border-box;
    }

    /* Ограничение ширины Timeline внутри колонки */
    .shape-timeline-column #timeline-card-12345 {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }

    /* Стили для Calculator */
    .shape-calculator-wrapper {
        width: 100%;
        box-sizing: border-box;
        margin-top: 0;
    }

    .shape-calculator-wrapper #aircraft-cost-calc {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }

    /* Стили для Roadmap */
    .roadmap-container {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
    }

    .roadmap-header {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 30px;
        padding-top: 20px;
    }

    .roadmap-title {
        font-family: 'Playfair Display', serif;
        font-size: 36px;
        font-weight: 500;
        color: #000;
        text-align: center;
        margin: 0;
    }

    .roadmap-svg-wrapper {
        flex: 1 1 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: auto;
        padding: 20px;
        box-sizing: border-box;
    }

    .roadmap-svg-wrapper svg {
        width: 100%;
        height: auto;
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    @media (max-width: 1024px) {
        .roadmap-container {
            height: auto;
            min-height: auto;
        }
        
        .roadmap-title {
            font-size: 28px;
        }
        
        .roadmap-svg-wrapper {
            padding: 20px 10px;
        }
    }

    /* Блок-заголовок перед картой */
    .shape-map-banner {
        height: 150px;
        width: 100%;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .shape-map-banner-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 40px;
    }

    .shape-map-banner-title {
        margin: 0;
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        font-weight: 300;
        color: #333333;
    }

    .shape-map-banner-subtitle {
        margin: 6px 0 0;
        font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        font-size: 16px;
        opacity: 0.8;
    }

    /* Адаптивность для мобильных */
    @media (max-width: 768px) {
        .shape-top-panel {
            height: auto;
            min-height: auto;
            padding: 20px;
        }

        .shape-top-panel h1 {
            font-size: 28px;
        }

        .shape-top-panel p {
            font-size: 16px;
        }

        .shape-kneeboard-section {
            min-height: 700px;
        }

        .shape-map-section {
            height: 350px;
        }

        .shape-map-banner-inner {
            padding: 0 20px;
        }

        .shape-map-banner-title {
            font-size: 26px;
        }

        .shape-map-banner-subtitle {
            font-size: 14px;
        }

        .shape-footer-section {
            min-height: 800px;
        }
    }
</style>

<!-- БЛОК 1: Верхняя информационная панель -->
<!-- БЛОК 1: Верхняя информационная панель -->
<section class="shape-top-panel">
    <!-- Меню будет поверх благодаря position: absolute в CSS меню или мы его тут не трогаем, но стили shape-top-panel настроены -->

    <div class="banner-inner">
        <div class="left-block">
            <div class="user-text">
                <?php
                // Определяем отображаемое имя (приоритет: display_name > username > public_id)
                $displayName = '';
                if (!empty($user['display_name']) && trim($user['display_name']) !== '') {
                    $displayName = trim($user['display_name']);
                } elseif (!empty($user['username']) && trim($user['username']) !== '') {
                    $displayName = trim($user['username']);
                } elseif (!empty($user['public_id'])) {
                    $displayName = $user['public_id'];
                } else {
                    $displayName = 'User';
                }
                
                // Определяем роль для отображения
                $roleDisplay = strtoupper($user['role']);
                ?>
                <div class="user-name"><?= htmlspecialchars($displayName) ?></div>
                <div class="user-meta"><?= htmlspecialchars(strtoupper($displayName)) ?> · <?= htmlspecialchars($user['public_id']) ?> · <?= htmlspecialchars($roleDisplay) ?></div>
            </div>
        </div>
        <div class="right-block">
            <!-- БАЛАНС И АВАТАР -->
            <div class="fuel-button" onclick="openLevelMatrixModal()">
                <img src="/assets/svg/fuel_level.svg?v=<?= time() ?>" class="balance-card" alt="Fuel Level">
            </div>

            <!-- Avatar container with local panel -->
            <div class="avatar-wrapper">
                <!-- Local panel for avatar change (left of avatar) -->
                <form action="/app/avatar/upload_avatar.php" method="post" enctype="multipart/form-data" class="avatar-panel">
                    <input type="file" id="avatar-upload" name="avatar" accept="image/*" required style="display: none;"
                        onchange="this.form.submit()">
                    <label id="avatarChangeBtn" for="avatar-upload" class="avatar-change">
                        Change avatar
                    </label>
                </form>

                <!-- Avatar Logic -->
                <?php
                $userId = $user['id'] ?? 0;
                // Get display_name for hash (use actual value from DB, not empty string)
                $displayName = '';
                if (isset($user['display_name']) && $user['display_name'] !== null && trim($user['display_name']) !== '') {
                    $displayName = trim($user['display_name']);
                } elseif (isset($user['username']) && $user['username'] !== null && trim($user['username']) !== '') {
                    $displayName = trim($user['username']);
                } elseif (isset($user['public_id']) && $user['public_id'] !== null) {
                    $displayName = $user['public_id'];
                }
                $avatarHash = md5($displayName);
                $avatarUrl = "/app/avatar/view_avatar.php?id={$userId}&h={$avatarHash}&v=2";
                echo "<img src='{$avatarUrl}' class='avatar-image' onclick='onAvatarClick()'>";
                ?>
            </div>

            <!-- Avatar Interaction Script -->
            <script>
                let hideTimer;
                function onAvatarClick() {
                    const panel = document.querySelector('.avatar-panel');
                    // Toggle panel
                    if (panel.classList.contains('show')) {
                        panel.classList.remove('show');
                    } else {
                        panel.classList.add('show');
                    }

                    // Reset timer
                    clearTimeout(hideTimer);
                    hideTimer = setTimeout(() => {
                        panel.classList.remove('show');
                    }, 5000); // 5 seconds as requested
                }
            </script>
        </div>
    </div>
</section>

<!-- БЛОК 2: Iframe KNEEBOARD -->
<section class="shape-kneeboard-section">
    <iframe src="https://gradaeronaut.com/forum/" class="shape-kneeboard-frame" id="kneeboard-iframe"></iframe>
    <!-- Overlay for KNEEBOARD initialization -->
    <div class="kneeboard-overlay" id="kneeboard-overlay">
        <svg class="kneeboard-cross" viewBox="0 0 100 100" preserveAspectRatio="none">
            <line x1="0" y1="0" x2="100" y2="100" stroke="#8B2A2A" opacity="0.65"/>
            <line x1="100" y1="0" x2="0" y2="100" stroke="#8B2A2A" opacity="0.65"/>
        </svg>
        <div class="kneeboard-init-text">Initializing Control Panel</div>
    </div>
</section>

<!-- Блок-заголовок перед картой -->
<section class="shape-map-banner">
    <div class="shape-map-banner-inner">
        <h2 class="shape-map-banner-title">Sinbad Beacons Map</h2>
        <p class="shape-map-banner-subtitle">Navigation lights of the community</p>
    </div>
</section>

<!-- БЛОК 3: Карта -->
<section class="shape-map-section">
    <div class="map-container">
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/map/index.php'; ?>
    </div>
</section>

<!-- Контейнер Timeline и Roadmap -->
<section class="shape-timeline-roadmap-container">
    <!-- Левая колонка - Timeline и Calculator -->
    <div class="shape-timeline-column">
        <?php include __DIR__ . '/timeline.php'; ?>
        <div class="shape-calculator-wrapper">
            <?php include __DIR__ . '/calculator.php'; ?>
        </div>
    </div>
    <!-- Правая колонка - Roadmap -->
    <div class="shape-roadmap-column">
        <div class="roadmap-container">
            <div class="roadmap-header">
                <h2 class="roadmap-title">Roadmap to Receiving Your Sinbad</h2>
            </div>
            <div class="roadmap-svg-wrapper">
                <?php 
                $svgPath = __DIR__ . '/roadmap.svg';
                if (file_exists($svgPath)) {
                    echo file_get_contents($svgPath);
                }
                ?>
            </div>
        </div>
    </div>
</section>

<!-- Membership Cards Section -->
<?php include __DIR__ . '/membership.php'; ?>

<!-- БЛОК 4: Футер/контент -->
<section class="shape-footer-section">
    <?php include __DIR__ . '/../footer/index.php'; ?>
</section>

<!-- Модальное окно Level Matrix -->
<div id="levelMatrixModal" class="modal-overlay" onclick="closeLevelMatrixModalOnOverlay(event)">
    <div class="modal-content wide">
        <button class="modal-close-btn" id="matrixCloseBtn">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="#888" stroke-width="1" stroke-linecap="round">
                <line x1="1" y1="1" x2="13" y2="13"/>
                <line x1="13" y1="1" x2="1" y2="13"/>
            </svg>
        </button>
        <img src="/assets/svg/level_matrix.svg" class="level-matrix-modal-img" alt="Level Matrix">
    </div>
</div>

<script>
function openLevelMatrixModal() {
    const modal = document.getElementById('levelMatrixModal');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeLevelMatrixModal() {
    const modal = document.getElementById('levelMatrixModal');
    modal.classList.remove('open');
    document.body.style.overflow = '';
}

function closeLevelMatrixModalOnOverlay(event) {
    if (event.target.id === 'levelMatrixModal') {
        closeLevelMatrixModal();
    }
}

// Закрытие по крестику
document.getElementById('matrixCloseBtn')
    .addEventListener('click', () => {
        closeLevelMatrixModal();
    });

// Закрытие по ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('levelMatrixModal');
        if (modal.classList.contains('open')) {
            closeLevelMatrixModal();
        }
    }
});
</script>

<!-- PayPal Cards Buttons -->
<script src="/shape-sinbad/paypal-cards.js?v=<?= time() ?>"></script>

<script>
// KNEEBOARD overlay - hide on iframe load
(function() {
    const iframe = document.getElementById('kneeboard-iframe');
    const overlay = document.getElementById('kneeboard-overlay');
    
    if (iframe && overlay) {
        // Hide overlay when iframe loads
        iframe.addEventListener('load', function() {
            overlay.classList.add('hidden');
        });
        
        // If iframe already loaded (cached), hide immediately
        if (iframe.contentDocument && iframe.contentDocument.readyState === 'complete') {
            overlay.classList.add('hidden');
        }
    }
})();
</script>

</body>

</html>