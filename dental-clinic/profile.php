<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

// Подключение к базе данных
$host = 'localhost';
$dbname = 'dental_clinic';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Продолжаем работу даже без БД для заглушки
}

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль - Стоматология "Улыбка"</title>
    <style>
        /* ПОЛНЫЕ СТИЛИ КАК НА ГЛАВНОЙ СТРАНИЦЕ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;500&display=swap');

        body {
            font-family: 'Open Sans', sans-serif;
            background: url('/background.jpg') center/cover fixed no-repeat;
            color: #01579b;
            line-height: 1.6;
            min-height: 100vh;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
        }

        /* ШАПКА */
        .glass-header {
            background: linear-gradient(135deg, rgba(2, 136, 209, 0.95) 0%, rgba(79, 195, 247, 0.95) 100%);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            border-bottom: 3px solid rgba(255,255,255,0.4);
        }

        header nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: url('/logo.png') center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .logo span {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.4rem;
            color: white;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 15px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: white;
            font-weight: 500;
            padding: 10px 18px;
            border-radius: 25px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .auth-btn, .profile-btn {
            background: rgba(255,255,255,0.9);
            color: #0288d1;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
        }

        .auth-btn:hover, .profile-btn:hover {
            background: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        /* КОНТЕЙНЕР ПРОФИЛЯ */
        .profile-container {
            position: relative;
            display: inline-block;
        }

        .profile-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(45deg, #0288d1, #4fc3f7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
            border: 2px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .profile-icon:hover {
            transform: translateY(-2px) scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(224,247,250,0.95) 100%);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255,255,255,0.8);
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(2,136,209,0.3);
            padding: 15px;
            min-width: 180px;
            z-index: 1001;
            display: none;
        }

        .profile-dropdown.show {
            display: block;
        }

        .profile-info {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(2,136,209,0.2);
        }

        .profile-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: #01579b;
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .profile-email {
            color: #546e7a;
            font-size: 0.85rem;
        }

        .profile-menu {
            list-style: none;
        }

        .profile-menu li {
            margin-bottom: 8px;
        }

        .profile-menu a {
            display: block;
            padding: 8px 12px;
            color: #0277bd;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
        }

        .profile-menu a:hover {
            background: rgba(2,136,209,0.1);
            color: #01579b;
        }

        .logout-btn {
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            color: white !important;
            font-weight: 600;
            margin-top: 5px;
        }

        .logout-btn:hover {
            background: linear-gradient(45deg, #ff5252, #ff7b7b) !important;
        }

        /* ОСНОВНОЙ КОНТЕНТ */
        main {
            margin-top: 90px;
            padding: 40px 20px;
            min-height: calc(100vh - 90px);
        }

        .profile-container-page {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
        }

        /* ГЛАВНАЯ СЕКЦИЯ */
        .profile-hero {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(224, 247, 250, 0.25) 100%);
            padding: 60px 40px;
            border-radius: 30px;
            margin-bottom: 40px;
            box-shadow: 0 20px 60px rgba(2,136,209,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: #01579b;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
            backdrop-filter: blur(5px);
            text-align: center;
        }

        .profile-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .profile-hero p {
            font-size: 1.2rem;
            color: #0277bd;
        }

        /* КАРТОЧКА ПРОФИЛЯ */
        .profile-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(224,247,250,0.95) 100%);
            padding: 50px 40px;
            border-radius: 25px;
            box-shadow: 0 15px 40px rgba(2,136,209,0.15);
            border: 2px solid rgba(255,255,255,0.8);
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(45deg, #0288d1, #4fc3f7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto 30px auto;
            border: 4px solid white;
            box-shadow: 0 8px 25px rgba(2,136,209,0.3);
        }

        .profile-info-card {
            margin-bottom: 30px;
        }

        .profile-name-large {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: #01579b;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .profile-email-large {
            color: #546e7a;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .stat-item {
            background: rgba(255,255,255,0.8);
            padding: 25px;
            border-radius: 15px;
            border: 2px solid rgba(255,255,255,0.9);
        }

        .stat-number {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #0288d1;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #546e7a;
            font-size: 1rem;
        }

        /* КНОПКИ */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .action-btn {
            background: linear-gradient(45deg, #0288d1, #4fc3f7);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
            box-shadow: 0 10px 25px rgba(2,136,209,0.3);
            border: 2px solid rgba(255,255,255,0.5);
            text-decoration: none;
            display: inline-block;
        }

        .action-btn:hover {
            background: linear-gradient(45deg, #039be5, #29b6f6);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(2, 136, 209, 0.4);
            color: white;
        }

        .action-btn.secondary {
            background: linear-gradient(45deg, #78909c, #90a4ae);
        }

        .action-btn.secondary:hover {
            background: linear-gradient(45deg, #607d8b, #78909c);
        }

        /* ЗАГЛУШКА */
        .coming-soon {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,243,224,0.95) 100%);
            padding: 40px;
            border-radius: 20px;
            border: 2px solid rgba(255,193,7,0.3);
            text-align: center;
            margin-top: 40px;
        }

        .coming-soon-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ff9800;
        }

        .coming-soon h3 {
            color: #f57c00;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .coming-soon p {
            color: #546e7a;
            font-size: 1.1rem;
        }

        /* АНИМАЦИИ */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        /* МОБИЛЬНАЯ ВЕРСИЯ */
        @media (max-width: 768px) {
            header nav {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .profile-hero h1 {
                font-size: 2rem;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
            }
            
            .profile-card {
                padding: 30px 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .action-btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- ШАПКА -->
    <header class="glass-header">
        <nav>
            <div class="logo">
                <div class="logo-img"></div>
                <span>Стоматология "Улыбка"</span>
            </div>
            <ul class="nav-links">
                <li><a href="/">Главная</a></li>
                <li><a href="/#about">О нас</a></li>
                <li><a href="/#services">Услуги</a></li>
                <li><a href="/#doctors">Врачи</a></li>
                <li><a href="/appointment.php">Запись</a></li>
                <li>
                    <div class="profile-container">
                        <div class="profile-icon" id="profileToggle">
                            <?php echo mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8'); ?>
                        </div>
                        <div class="profile-dropdown" id="profileDropdown">
                            <div class="profile-info">
                                <div class="profile-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                                <div class="profile-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                            </div>
                            <ul class="profile-menu">
                                <li><a href="/profile.php" style="background: rgba(2,136,209,0.1);">📋 Мой профиль</a></li>
                                <li><a href="/my-appointments.php">📅 Мои записи</a></li>
                                <li><a href="?logout=1" class="logout-btn">🚪 Выйти</a></li>
                            </ul>
                        </div>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="profile-container-page">
            <div class="profile-hero fade-in">
                <h1>Мой профиль</h1>
                <p>Управление вашей учетной записью и настройками</p>
            </div>

            <div class="profile-card fade-in">
                <div class="profile-avatar">
                    <?php echo mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8'); ?>
                </div>
                
                <div class="profile-info-card">
                    <div class="profile-name-large"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                    <div class="profile-email-large"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                </div>

                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-number" id="appointmentsCount">0</div>
                        <div class="stat-label">Всего записей</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="pendingCount">0</div>
                        <div class="stat-label">Ожидают подтверждения</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="confirmedCount">0</div>
                        <div class="stat-label">Подтвержденные</div>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="/my-appointments.php" class="action-btn">📅 Мои записи</a>
                    <a href="/appointment.php" class="action-btn">➕ Новая запись</a>
                    <button class="action-btn secondary" onclick="showComingSoon()">⚙️ Настройки</button>
                    <button class="action-btn secondary" onclick="showComingSoon()">✏️ Редактировать профиль</button>
                </div>
            </div>

            <div class="coming-soon fade-in" id="comingSoonMessage" style="display: none;">
                <div class="coming-soon-icon">🚧</div>
                <h3>Раздел в разработке</h3>
                <p>Данный функционал будет доступен в ближайшее время. Спасибо за понимание!</p>
                <button class="action-btn" onclick="hideComingSoon()" style="margin-top: 20px;">Понятно</button>
            </div>
        </div>
    </main>

    <script>
        // Управление выпадающим меню профиля
        const profileToggle = document.getElementById('profileToggle');
        const profileDropdown = document.getElementById('profileDropdown');

        if (profileToggle && profileDropdown) {
            profileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
            });

            document.addEventListener('click', function() {
                profileDropdown.classList.remove('show');
            });

            profileDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        // Заглушка для кнопок
        function showComingSoon() {
            document.getElementById('comingSoonMessage').style.display = 'block';
        }

        function hideComingSoon() {
            document.getElementById('comingSoonMessage').style.display = 'none';
        }

        // Плавное появление элементов
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(element);
        });

        // Простая анимация счетчиков (можно заменить реальными данными)
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 30);
        }

        // Запускаем анимацию счетчиков (заглушка)
        setTimeout(() => {
            animateCounter(document.getElementById('appointmentsCount'), 3);
            animateCounter(document.getElementById('pendingCount'), 2);
            animateCounter(document.getElementById('confirmedCount'), 1);
        }, 500);
    </script>
</body>
</html>