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
    
    // Создаем таблицу если не существует
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        user_name VARCHAR(100) NOT NULL,
        user_email VARCHAR(100),
        user_phone VARCHAR(20) NOT NULL,
        birthdate DATE,
        service_type VARCHAR(50) NOT NULL,
        preferred_doctor VARCHAR(50),
        appointment_date DATE NOT NULL,
        preferred_time VARCHAR(10),
        message TEXT,
        status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($createTableSQL);
    
} catch(PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Получаем записи пользователя
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC, created_at DESC");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Мои записи - Стоматология "Улыбка"</title>
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

        .appointment-container {
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
        }

        /* ГЛАВНАЯ СЕКЦИЯ */
        .appointment-hero {
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

        .appointment-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .appointment-hero p {
            font-size: 1.2rem;
            color: #0277bd;
        }

        /* СТИЛИ ДЛЯ СПИСКА ЗАПИСЕЙ */
        .appointments-list {
            margin-top: 30px;
        }
        
        .appointment-item {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(224,247,250,0.95) 100%);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(2,136,209,0.15);
            border: 2px solid rgba(255,255,255,0.8);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .appointment-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(2,136,209,0.25);
            border-color: rgba(79,195,247,0.5);
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(2,136,209,0.2);
        }
        
        .appointment-service {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: #01579b;
            font-size: 1.4rem;
        }
        
        .appointment-status {
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
        }
        
        .status-pending {
            background: linear-gradient(45deg, #fff3cd, #ffeaa7);
            color: #856404;
            border: 2px solid #ffeaa7;
        }
        
        .status-confirmed {
            background: linear-gradient(45deg, #d1ecf1, #bee5eb);
            color: #0c5460;
            border: 2px solid #bee5eb;
        }
        
        .status-cancelled {
            background: linear-gradient(45deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            margin-bottom: 12px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #546e7a;
            font-size: 0.95rem;
            margin-bottom: 5px;
            font-family: 'Montserrat', sans-serif;
        }
        
        .detail-value {
            color: #01579b;
            font-weight: 500;
            font-size: 1.1rem;
        }
        
        .no-appointments {
            text-align: center;
            padding: 80px 40px;
            color: #546e7a;
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(224,247,250,0.9) 100%);
            border-radius: 25px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.8);
            box-shadow: 0 10px 30px rgba(2,136,209,0.1);
        }
        
        .no-appointments-icon {
            font-size: 5rem;
            margin-bottom: 25px;
            opacity: 0.7;
        }

        .no-appointments h3 {
            font-family: 'Montserrat', sans-serif;
            color: #01579b;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .no-appointments p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            color: #546e7a;
        }

        /* КНОПКА */
        .form-btn {
            background: linear-gradient(45deg, #0288d1, #4fc3f7);
            color: white;
            border: none;
            padding: 15px 35px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(2,136,209,0.3);
            font-family: 'Montserrat', sans-serif;
            text-decoration: none;
            display: inline-block;
            border: 2px solid rgba(255,255,255,0.5);
        }

        .form-btn:hover {
            background: linear-gradient(45deg, #039be5, #29b6f6);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(2, 136, 209, 0.4);
            color: white;
            text-decoration: none;
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
            
            .appointment-hero h1 {
                font-size: 2rem;
            }
            
            .appointment-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .appointment-details {
                grid-template-columns: 1fr;
            }
            
            .appointment-item {
                padding: 20px;
            }
            
            .no-appointments {
                padding: 50px 20px;
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
                                <li><a href="/profile.php">📋 Мой профиль</a></li>
                                <li><a href="/my-appointments.php" style="background: rgba(2,136,209,0.1);">📅 Мои записи</a></li>
                                <li><a href="?logout=1" class="logout-btn">🚪 Выйти</a></li>
                            </ul>
                        </div>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="appointment-container">
            <div class="appointment-hero fade-in">
                <h1>Мои записи</h1>
                <p>Здесь отображаются все ваши записи на прием</p>
            </div>

            <div class="appointments-list">
                <?php if (empty($appointments)): ?>
                    <div class="no-appointments fade-in">
                        <div class="no-appointments-icon">📅</div>
                        <h3>У вас пока нет записей</h3>
                        <p>Запишитесь на прием, чтобы увидеть его здесь</p>
                        <a href="/appointment.php" class="form-btn">Записаться на прием</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($appointments as $appointment): ?>
                        <div class="appointment-item fade-in">
                            <div class="appointment-header">
                                <div class="appointment-service">
                                    <?php 
                                    $services = [
                                        'diagnostics' => 'Диагностика',
                                        'caries' => 'Лечение кариеса', 
                                        'whitening' => 'Отбеливание',
                                        'cleaning' => 'Профессиональная чистка',
                                        'prosthetics' => 'Протезирование',
                                        'surgery' => 'Хирургическое лечение'
                                    ];
                                    echo $services[$appointment['service_type']] ?? $appointment['service_type'];
                                    ?>
                                </div>
                                <div class="appointment-status status-<?php echo $appointment['status']; ?>">
                                    <?php 
                                    $statuses = [
                                        'pending' => 'Ожидание',
                                        'confirmed' => 'Подтверждена',
                                        'cancelled' => 'Отменена'
                                    ];
                                    echo $statuses[$appointment['status']] ?? $appointment['status'];
                                    ?>
                                </div>
                            </div>
                            <div class="appointment-details">
                                <div class="detail-item">
                                    <div class="detail-label">Дата и время:</div>
                                    <div class="detail-value">
                                        <?php echo date('d.m.Y', strtotime($appointment['appointment_date'])); ?>
                                        <?php if ($appointment['preferred_time']): ?>
                                            в <?php echo $appointment['preferred_time']; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Врач:</div>
                                    <div class="detail-value">
                                        <?php 
                                        $doctors = [
                                            'ivanov' => 'Иванов Алексей Сергеевич',
                                            'petrova' => 'Петрова Мария Игоревна',
                                            'sidorov' => 'Сидоров Дмитрий Петрович'
                                        ];
                                        echo $appointment['preferred_doctor'] ? ($doctors[$appointment['preferred_doctor']] ?? $appointment['preferred_doctor']) : 'Любой врач';
                                        ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Телефон:</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($appointment['user_phone']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Email:</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($appointment['user_email'] ?: 'Не указан'); ?></div>
                                </div>
                                <?php if ($appointment['message']): ?>
                                <div class="detail-item" style="grid-column: 1 / -1;">
                                    <div class="detail-label">Дополнительная информация:</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($appointment['message']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Скрипт для выпадающего меню профиля
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

        // Наблюдаем за карточками записей
        document.querySelectorAll('.appointment-item').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>