<?php
session_start();
// Очищаем сессию
session_unset();
session_destroy();

echo "<script>alert('Вы вышли из аккаунта'); window.location.href='index.php';</script>";
exit();
?>