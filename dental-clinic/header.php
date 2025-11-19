<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Стоматология "Улыбка"</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header class="glass-header">
        <nav>
            <div class="logo">
                <div class="logo-img">Л</div>
                <span>Стоматология "Улыбка"</span>
            </div>
            <ul class="nav-links">
                <li><a href="/">Главная</a></li>
                <li><a href="#about">О нас</a></li>
                <li><a href="#services">Услуги</a></li>
                <li><a href="#doctors">Врачи</a></li>
                <li><a href="#appointment">Запись</a></li>
                <li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/profile.php" class="profile-btn">Профиль</a>
                    <?php else: ?>
                        <a href="#" class="auth-btn" id="loginBtn">Вход</a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </header>