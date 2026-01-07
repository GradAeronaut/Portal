<?php
session_start();

$user_id = $_SESSION['user_id'];      // ID пользователя Портала
$xf_id = $_SESSION['xf_user_id'];   // ID того же пользователя в XenForo

// Путь хранения аватара в Портале
$target = __DIR__ . "/../../uploads/avatars/$user_id.png";

// Проверка файла
if (!isset($_FILES['avatar'])) {
    header("Location: /shape-sinbad");
    exit;
}

$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($_FILES['avatar']['type'], $allowed)) {
    header("Location: /shape-sinbad?error=format");
    exit;
}

// Загружаем изображение в память
$img = imagecreatefromstring(file_get_contents($_FILES['avatar']['tmp_name']));
if (!$img) {
    header("Location: /shape-sinbad?error=invalid");
    exit;
}

// Обрезаем до квадрата
$w = imagesx($img);
$h = imagesy($img);
$size = min($w, $h);

$crop = imagecrop($img, [
    'x' => ($w - $size) / 2,
    'y' => ($h - $size) / 2,
    'width' => $size,
    'height' => $size
]);

// Создаем финальный холст 256x256
$final = imagecreatetruecolor(256, 256);
imagesavealpha($final, true);
$trans = imagecolorallocatealpha($final, 0, 0, 0, 127);
imagefill($final, 0, 0, $trans);

// Ресайз
imagecopyresampled($final, $crop, 0, 0, 0, 0, 256, 256, $size, $size);

// Сохраняем финальный PNG
imagepng($final, $target);

// ---------- СИНХРОНИЗАЦИЯ В XENFORO ----------
if ($xf_id) {
    $sync = __DIR__ . "/sync_avatar_to_xf.php";
    exec("php $sync $target $xf_id > /dev/null 2>&1 &");
}

// Возврат на страницу Shape
header("Location: /shape-sinbad");
exit;

