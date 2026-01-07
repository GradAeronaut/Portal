<?php
session_start();
$user_id = $_SESSION['user_id'];
$file = __DIR__ . "/../../uploads/avatars/$user_id.png";
if (file_exists($file))
    unlink($file);
header("Location: /shape-sinbad");
