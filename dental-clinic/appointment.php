<?php
session_start();

// Включаем отладку
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключение к базе данных
$host = 'localhost';
$dbname = 'dental_clinic';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /');
    exit;
}

// Обработка отправки формы
$showSuccess = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name'])) {
    
    // Получаем данные
    $user_id = $_SESSION['user_id'] ?? 'guest_' . time();
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email'] ?? '');
    $birthdate = $_POST['birthdate'] ?? null;
    $service = $_POST['service'];
    $doctor = $_POST['doctor'] ?? '';
    $date = $_POST['date'];
    $time = $_POST['time'] ?? '';
    $message = trim($_POST['message'] ?? '');
    
    try {
        // Сохраняем запись в базу данных
        $stmt = $pdo->prepare("INSERT INTO appointments (user_id, user_name, user_email, user_phone, birthdate, service_type, preferred_doctor, appointment_date, preferred_time, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $email, $phone, $birthdate, $service, $doctor, $date, $time, $message]);
        
        $_SESSION['appointment_success'] = true;
        header('Location: /appointment.php?success=1');
        exit;
        
    } catch(PDOException $e) {
        $error = 'Ошибка при сохранении записи: ' . $e->getMessage();
    }
}

// Показываем сообщение об успехе если есть
$showSuccess = isset($_GET['success']) && $_GET['success'] == 1;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Запись на прием - Стоматология "Улыбка"</title>
    <style>
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
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .appointment-container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
        }

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

        /* ФОРМА ЗАПИСИ */
        .appointment-form {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(224,247,250,0.95) 100%);
            padding: 50px 40px;
            border-radius: 25px;
            box-shadow: 0 15px 40px rgba(2,136,209,0.15);
            border: 2px solid rgba(255,255,255,0.8);
        }

        .input-group {
            margin-bottom: 1.5rem;
            width: 100%;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #01579b;
            font-family: 'Montserrat', sans-serif;
        }

        .input-group input,
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(224,247,250,0.8);
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Open Sans', sans-serif;
            color: #01579b;
            box-shadow: 0 5px 15px rgba(2,136,209,0.1);
        }

        .input-group input:focus,
        .input-group textarea:focus,
        .input-group select:focus {
            outline: none;
            border-color: #4fc3f7;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 20px rgba(79, 195, 247, 0.3);
            transform: translateY(-2px);
        }

        .input-group input::placeholder {
            color: #90a4ae;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-btn {
            width: 100%;
            padding: 1.2rem 2rem;
            border: none;
            border-radius: 15px;
            background: linear-gradient(45deg, #0288d1, #4fc3f7);
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
            box-shadow: 0 10px 25px rgba(2,136,209,0.3);
            border: 2px solid rgba(255,255,255,0.5);
            margin-top: 1rem;
        }

        .form-btn:hover {
            background: linear-gradient(45deg, #039be5, #29b6f6);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(2, 136, 209, 0.4);
        }

        /* СООБЩЕНИЕ ОБ УСПЕХЕ */
        .success-message {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(224,247,250,0.95) 100%);
            padding: 50px 40px;
            border-radius: 25px;
            box-shadow: 0 15px 40px rgba(46, 125, 50, 0.2);
            border: 2px solid rgba(76, 175, 80, 0.3);
            text-align: center;
            margin-top: 20px;
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .success-message h2 {
            color: #2E7D32;
            margin-bottom: 15px;
            font-size: 1.8rem;
        }

        .success-message p {
            color: #546e7a;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }

        .back-link {
            display: inline-block;
            margin-top: 2rem;
            color: #0288d1;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 25px;
            border: 2px solid #0288d1;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: white;
            background: #0288d1;
            transform: translateY(-2px);
        }

        .error-message {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,235,238,0.95) 100%);
            padding: 20px;
            border-radius: 15px;
            border: 2px solid rgba(244,67,54,0.3);
            color: #c62828;
            margin-bottom: 20px;
            text-align: center;
        }

        /* АНИМАЦИИ */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .appointment-form {
                padding: 30px 20px;
            }
            
            .success-message {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
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
                <li><a href="/appointment.php" style="background: rgba(255,255,255,0.2);">Запись</a></li>
                <li>
                    <?php if (isset($_SESSION['user_id'])): ?>
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
                                    <li><a href="/my-appointments.php">📅 Мои записи</a></li>
                                    <li><a href="?logout=1" class="logout-btn">🚪 Выйти</a></li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/" class="auth-btn">Вход / Регистрация</a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="appointment-container">
            <div class="appointment-hero">
                <h1>Запись на прием</h1>
                <p>Заполните форму ниже и мы свяжемся с вами для подтверждения записи</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($showSuccess): ?>
                <div class="success-message fade-in">
                    <div class="success-icon">✓</div>
                    <h2>Запись успешно отправлена!</h2>
                    <p>Мы свяжемся с вами в ближайшее время для подтверждения записи.</p>
                    <p><strong>Спасибо, что выбрали нашу клинику!</strong></p>
                    <a href="/" class="back-link">Вернуться на главную</a>
                    <a href="/my-appointments.php" class="back-link" style="margin-left: 10px;">Мои записи</a>
                </div>
            <?php else: ?>
                <form class="appointment-form" method="POST" action="">
                    <div class="form-row">
                        <div class="input-group">
                            <label for="name">Ваше имя *</label>
                            <input type="text" id="name" name="name" placeholder="Иван Иванов" required value="<?php echo $_SESSION['user_name'] ?? ''; ?>">
                        </div>
                        <div class="input-group">
                            <label for="phone">Телефон *</label>
                            <input type="tel" id="phone" name="phone" placeholder="+7 (999) 999-99-99" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="ivan@example.com" value="<?php echo $_SESSION['user_email'] ?? ''; ?>">
                        </div>
                        <div class="input-group">
                            <label for="birthdate">Дата рождения</label>
                            <input type="date" id="birthdate" name="birthdate">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label for="service">Услуга *</label>
                            <select id="service" name="service" required>
                                <option value="">Выберите услугу</option>
                                <option value="diagnostics">Диагностика</option>
                                <option value="caries">Лечение кариеса</option>
                                <option value="whitening">Отбеливание</option>
                                <option value="cleaning">Профессиональная чистка</option>
                                <option value="prosthetics">Протезирование</option>
                                <option value="surgery">Хирургическое лечение</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="doctor">Предпочтительный врач</label>
                            <select id="doctor" name="doctor">
                                <option value="">Любой доступный врач</option>
                                <option value="ivanov">Иванов Алексей Сергеевич</option>
                                <option value="petrova">Петрова Мария Игоревна</option>
                                <option value="sidorov">Сидоров Дмитрий Петрович</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label for="date">Желаемая дата *</label>
                            <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="input-group">
                            <label for="time">Предпочтительное время</label>
                            <select id="time" name="time">
                                <option value="">Любое время</option>
                                <option value="09:00">09:00</option>
                                <option value="10:00">10:00</option>
                                <option value="11:00">11:00</option>
                                <option value="12:00">12:00</option>
                                <option value="14:00">14:00</option>
                                <option value="15:00">15:00</option>
                                <option value="16:00">16:00</option>
                                <option value="17:00">17:00</option>
                                <option value="18:00">18:00</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="message">Дополнительная информация</label>
                        <textarea id="message" name="message" placeholder="Опишите вашу проблему или пожелания..." rows="4"></textarea>
                    </div>

                    <button type="submit" class="form-btn">Записаться на прием</button>
                </form>
            <?php endif; ?>
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

        // Валидация телефона
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^\d+()-]/g, '');
            });
        }
    </script>
</body>
</html>