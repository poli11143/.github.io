<?php
// Запускаем сессию
session_start();

// Подключение к базе данных
$host = 'localhost';
$dbname = 'dental_clinic';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Создание таблицы пользователей если не существует
$createTableQuery = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($createTableQuery);

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /');
    exit;
}

// Обработка регистрации
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Валидация данных
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'Все обязательные поля должны быть заполнены';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Некорректный email адрес';
    } else {
        try {
            // Проверка существования пользователя
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = 'Пользователь с таким email уже существует';
            } else {
                // Хеширование пароля
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Сохранение пользователя в БД
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashedPassword, $phone]);
                
                // Автоматический вход после регистрации
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['success'] = 'Регистрация прошла успешно!';
                
                header('Location: /');
                exit;
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Ошибка при регистрации: ' . $e->getMessage();
        }
    }
}

// Обработка входа
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Все поля должны быть заполнены';
    } else {
        try {
            // Поиск пользователя в БД
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['success'] = 'Вход выполнен успешно!';
                
                header('Location: /');
                exit;
            } else {
                $_SESSION['error'] = 'Неверный email или пароль';
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Ошибка при входе: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Стоматология "Улыбка"</title>
    <style>
        /* CSS СТИЛИ */
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

        .logo-img:not([style*="background-image"]):before {
            display: flex;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #0288d1, #4fc3f7);
            border-radius: 50%;
            align-items: center;
            justify-content: center;
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

        /* КОНТЕЙНЕР ПРОФИЛЯ С ВЫПАДАЮЩИМ МЕНЮ */
        .profile-container {
            position: relative;
            display: inline-block;
        }

        /* ИКОНКА ПРОФИЛЯ */
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
            text-decoration: none;
            cursor: pointer;
        }

        .profile-icon:hover {
            transform: translateY(-2px) scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        /* ВЫПАДАЮЩЕЕ МЕНЮ ПРОФИЛЯ */
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
            animation: fadeIn 0.3s ease;
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
            transform: translateY(-2px);
        }

        /* МОДАЛЬНОЕ ОКНО */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .glass-modal {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(224,247,250,0.95) 100%);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255,255,255,0.8);
            box-shadow: 0 25px 50px rgba(2,136,209,0.3);
            margin: 10% auto;
            padding: 2.5rem;
            border-radius: 25px;
            width: 90%;
            max-width: 400px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 1.2rem;
            top: 0.8rem;
            font-size: 2rem;
            cursor: pointer;
            color: #0288d1;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #01579b;
        }

        .input-group {
            margin-bottom: 1.5rem;
            width: 100%;
        }

        .input-group input, .input-group textarea {
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

        .input-group input:focus, .input-group textarea:focus {
            outline: none;
            border-color: #4fc3f7;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 20px rgba(79, 195, 247, 0.3);
            transform: translateY(-2px);
        }

        .input-group input::placeholder, .input-group textarea::placeholder {
            color: #90a4ae;
        }

        .form-btn {
            width: 100%;
            padding: 1rem 2rem;
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
        }

        .form-btn:hover {
            background: linear-gradient(45deg, #039be5, #29b6f6);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(2, 136, 209, 0.4);
        }

        .switch-text {
            text-align: center;
            margin-top: 1.5rem;
            color: #546e7a;
        }

        .switch-text a {
            color: #0288d1;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .switch-text a:hover {
            color: #01579b;
            text-decoration: underline;
        }

        /* СТИЛИ ДЛЯ ВЫДЕЛЕННЫХ СЕКЦИЙ */
        .section-title {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(224,247,250,0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 20px 40px;
            border-radius: 50px;
            border: 3px solid rgba(2,136,209,0.3);
            margin-bottom: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(2,136,209,0.2);
            position: relative;
            overflow: hidden;
        }

        .section-title:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.4), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .section-title:hover:before {
            transform: translateX(100%);
        }

        .section-title h2 {
            color: #01579b;
            font-size: 2.2rem;
            margin: 0;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
        }

        /* УВЕДОМЛЕНИЯ */
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            z-index: 3000;
            animation: slideIn 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .notification.success {
            background: linear-gradient(45deg, #4CAF50, #66BB6A);
        }

        .notification.error {
            background: linear-gradient(45deg, #f44336, #ef5350);
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* ОСНОВНОЙ КОНТЕНТ */
        main {
            margin-top: 90px;
            padding: 20px;
        }

        section {
            padding: 5rem 2rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        /* ГЛАВНАЯ СЕКЦИЯ */
        .hero {
            background: 
                linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(224, 247, 250, 0.25) 100%);
            padding: 100px 20px;
            border-radius: 30px;
            margin: 20px auto;
            max-width: 1200px;
            box-shadow: 0 20px 60px rgba(2,136,209,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: #01579b;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
            backdrop-filter: blur(5px);
            position: relative;
            overflow: hidden;
        }

        .hero:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.1);
            z-index: 1;
        }

        .hero > * {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 3.5rem;
            color: #01579b;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(255,255,255,0.9);
        }

        .hero p {
            font-size: 1.3rem;
            color: #0277bd;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
        }

        .cta-button {
            background: linear-gradient(45deg, #0288d1, #4fc3f7);
            color: white;
            border: none;
            padding: 18px 35px;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(2,136,209,0.4);
            font-family: 'Montserrat', sans-serif;
            animation: pulse 2s infinite;
            border: 2px solid rgba(255,255,255,0.5);
            text-decoration: none;
            display: inline-block;
        }

        .cta-button:hover {
            background: linear-gradient(45deg, #039be5, #29b6f6);
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 40px rgba(2,136,209,0.6);
            color: white;
        }

        /* СЕТКА УСЛУГ */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            width: 100%;
            max-width: 1200px;
        }

        .service-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(224,247,250,0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 2.5rem 2rem;
            border-radius: 20px;
            border: 2px solid rgba(255,255,255,0.8);
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(2,136,209,0.15);
        }

        .service-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px rgba(2,136,209,0.25);
            border-color: rgba(79,195,247,0.5);
        }

        .service-card h3 {
            font-family: 'Montserrat', sans-serif;
            color: #01579b;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .service-card p {
            color: #546e7a;
            line-height: 1.5;
        }

        /* СЕКЦИЯ ВРАЧЕЙ */
        .doctors-scroll-wrapper {
            background: linear-gradient(135deg, rgba(255,255,255,0.85) 0%, rgba(224,247,250,0.9) 100%);
            padding: 50px 20px;
            border-radius: 25px;
            margin: 40px auto;
            max-width: 1200px;
            box-shadow: 0 15px 40px rgba(2,136,209,0.1);
            border: 1px solid rgba(255,255,255,0.6);
        }

       .doctors-scroll-container {
    display: flex;
    gap: 20px;
    padding: 10px;
    overflow-x: auto;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch; /* для iOS */
}

      /* Удали или закомментируй старые стили .doctor-card и добав эти: */

.doctor-card {
    flex: 0 0 auto;
    width: 280px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fdff 100%);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(2,136,209,0.1);
    border: 2px solid rgba(79,195,247,0.3);
    text-align: center;
   
}



        .doctor-photo {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin: 0 auto 20px auto;
    border: 4px solid white;
    box-shadow: 0 8px 25px rgba(2,136,209,0.3);
    overflow: hidden;
}

.doctor-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

        /* СЕКЦИЯ ОТЗЫВОВ */
        .reviews {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(224,247,250,0.2) 100%);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255,255,255,0.3);
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
            width: 100%;
            max-width: 1200px;
        }

        .review-card {
            padding: 2rem;
            border-radius: 20px;
            border: 2px solid rgba(255,255,255,0.5);
            transition: all 0.3s ease;
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(224,247,250,0.9) 100%);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(2,136,209,0.1);
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(2,136,209,0.2);
            border-color: rgba(79,195,247,0.5);
        }

        .review-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .review-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #0288d1, #4fc3f7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(2,136,209,0.3);
        }

        .review-rating {
            color: #ffb300;
            font-size: 1.2rem;
            margin-left: auto;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .review-text {
            color: #37474f;
            line-height: 1.6;
            font-style: italic;
            text-align: left;
        }

        /* ФУТЕР */
        .footer {
            background: linear-gradient(135deg, rgba(2, 136, 209, 0.95) 0%, rgba(4, 131, 185, 0.95) 100%);
            color: white;
            padding: 60px 20px 30px;
            margin-top: 50px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h3 {
            font-family: 'Montserrat', sans-serif;
            margin-bottom: 20px;
            font-size: 1.3rem;
            color: white;
        }

        .footer-section p, .footer-section a {
            color: rgba(255,255,255,0.8);
            margin-bottom: 10px;
            display: block;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: white;
        }

        .contact-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .contact-info i {
            font-size: 1.2rem;
            width: 20px;
        }

        .support-form {
            background: rgba(255,255,255,0.1);
            padding: 25px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .support-form .input-group {
            margin-bottom: 1rem;
        }

        .support-form textarea {
            height: 120px;
            resize: vertical;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.2);
            text-align: center;
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        /* СЕКЦИЯ ЗАЩИТЫ ДАННЫХ */
        .security-section {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(224,247,250,0.9) 100%);
            padding: 40px 20px;
            border-radius: 20px;
            margin: 40px auto;
            max-width: 1200px;
            text-align: center;
            border: 2px solid rgba(2,136,209,0.3);
        }

        .security-badges {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .security-badge {
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            border: 2px solid #4CAF50;
            color: #2E7D32;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* АНИМАЦИИ */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .fade-in {
            animation: fadeIn 1s ease-out;
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
            
            .hero h1 {
                font-size: 2.2rem;
            }
            
            .logo span {
                font-size: 1.1rem;
            }
            
            section {
                padding: 3rem 1rem;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .profile-dropdown {
                right: -50px;
                min-width: 200px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .footer-links {
                flex-direction: column;
                gap: 10px;
            }

            .section-title h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- УВЕДОМЛЕНИЯ -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="notification error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- ШАПКА -->
    <header class="glass-header">
        <nav>
            <div class="logo">
                <div class="logo-img"></div>
                <span>Стоматология "Улыбка"</span>
            </div>
            <ul class="nav-links">
                <li><a href="/">Главная</a></li>
                <li><a href="#about">О нас</a></li>
                <li><a href="#services">Услуги</a></li>
                <li><a href="#doctors">Врачи</a></li>
                <li><a href="/appointment.php">Запись</a></li>
                <li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- КОНТЕЙНЕР ПРОФИЛЯ С ВЫПАДАЮЩИМ МЕНЮ -->
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
                        <!-- КНОПКА ВХОДА/РЕГИСТРАЦИИ для неавторизованных -->
                        <a href="#" class="auth-btn" id="loginBtn">Вход                       </a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Секция Герой -->
        <section id="home" class="hero fade-in">
            <h1>Ваша здоровая улыбка — наша забота!</h1>
            <p>Современные технологии и лучшие специалисты для вашего комфорта</p>
            <a href="/appointment.php" class="cta-button">Записаться на прием</a>
        </section>

        <!-- Секция О нас -->
        <section id="about">
            <div class="section-title">
                <h2>О нашей клинике</h2>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <h3>🏥 Современное оборудование</h3>
                    <p>Используем только лучшее оборудование от мировых производителей</p>
                </div>
                <div class="service-card">
                    <h3>👨‍⚕️ Опытные врачи</h3>
                    <p>Наши специалисты с опытом работы от 8 лет</p>
                </div>
                <div class="service-card">
                    <h3>💰 Доступные цены</h3>
                    <p>Предоставляем качественные услуги по разумным ценам</p>
                </div>
            </div>
        </section>

        <!-- Секция Услуги -->
        <section id="services">
            <div class="section-title">
                <h2>Наши услуги</h2>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <h3>Диагностика</h3>
                    <p>от 3000 руб.</p>
                </div>
                <div class="service-card">
                    <h3>Лечение кариеса</h3>
                    <p>от 4000 руб.</p>
                </div>
                <div class="service-card">
                    <h3>Отбеливание в 3 сеанса</h3>
                    <p>15000 руб.</p>
                </div>
                <div class="service-card">
                    <h3>Профессиональная чистка</h3>
                    <p>4000 руб.</p>
                </div>
                <div class="service-card">
                    <h3>Протезирование</h3>
                    <p>от 30000 руб.</p>
                </div>
                 <div class="service-card">
                    <h3>Хирургическое лечение</h3>
                    <p>от 5000 руб.</p>
                </div>
            </div>
        </section>

        <!-- Секция Врачи -->
        <section id="doctors">
            <div class="section-title">
                <h2>Наши врачи</h2>
            </div>
            <div class="doctors-scroll-wrapper">
                <div class="doctors-scroll-container">
                    <div class="doctor-card">
                <div class="doctor-photo">
                <img src="/dok2.jpg" alt="Иванов Алексей Сергеевич">
                </div>
                <h3>Иванов Алексей Сергеевич</h3>
                <p>Врач-ортодонт</p>
                <p>Стаж: 12 лет</p>
                </div>
                    <div class="doctor-card">
                       <div class="doctor-photo">
                    <img src="/dok4.jpg" alt="Иванов Алексей Сергеевич">
                    </div>
                        <h3>Петрова Мария Игоревна</h3>
                        <p>Врач-терапевт</p>
                        <p>Стаж: 8 лет</p>
                    </div>
                    <div class="doctor-card">
                       <div class="doctor-photo">
                    <img src="/dok6.jpg" alt="Иванов Алексей Сергеевич">
                    </div>
                        <h3>Сидоров Дмитрий Петрович</h3>
                        <p>Хирург-имплантолог</p>
                        <p>Стаж: 15 лет</p>
                    </div>
                    <div class="doctor-card">
                        <div class="doctor-photo">
                    <img src="/dok1.jpg" alt="Иванов Алексей Сергеевич">
                    </div>
                        <h3>Алексеева Алина Викторовна</h3>
                        <p>Хирург-имплантолог</p>
                        <p>Стаж: 13 лет</p>
                    </div>
                     <div class="doctor-card">
                        <div class="doctor-photo">
                     <img src="/dok5.jpg" alt="Иванов Алексей Сергеевич">
                    </div>
                        <h3>Федоров Антон Владимирович</h3>
                        <p>Врач-ортодонт</p>
                        <p>Стаж: 6 лет</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Секция Отзывы -->
        <section class="reviews">
            <div class="section-title">
    <h2>Отзывы наших пациентов</h2>
</div>
            <div class="reviews-grid">
                <div class="review-card">
                    <div class="review-header">
                        <div class="review-avatar">АК</div>
                        <div>
                            <h4>Анна К.</h4>
                            <small>15.12.2024</small>
                        </div>
                        <div class="review-rating">★★★★★</div>
                    </div>
                    <p>"Отличный сервис! Врач очень внимательный, все объяснил и вылечил без боли. Спасибо!"</p>
                </div>
                <div class="review-card">
                    <div class="review-header">
                        <div class="review-avatar">МС</div>
                        <div>
                            <h4>Михаил С.</h4>
                            <small>10.12.2024</small>
                        </div>
                        <div class="review-rating">★★★★☆</div>
                    </div>
                    <p>"Хорошая клиника, современное оборудование. Цены немного высокие, но качество того стоит."</p>
                </div>
            </div>
        </section>


    <!-- ФУТЕР -->
    <footer class="footer">
        <div class="footer-content">
            <!-- Контактная информация -->
            <div class="footer-section">
                <h3> Контакты</h3>
                <div class="contact-info">
                    <i></i>
                    <span>+7 (495) 123-45-67</span>
                </div>
                <div class="contact-info">
                    <i></i>
                    <span>+7 (495) 765-43-21</span>
                </div>
                <div class="contact-info">
                    <i></i>
                    <span>info@stomatology-smile.ru</span>
                </div>
                <div class="contact-info">
                    <i>📍</i>
                    <span>г. Красноярск, ул. Победы, д. 15</span>
                </div>
            </div>

            <!-- Режим работы -->
            <div class="footer-section">
                <h3>🕒 Режим работы</h3>
                <p><strong>Пн-Пт:</strong> 9:00 - 21:00</p>
                <p><strong>Сб:</strong> 10:00 - 18:00</p>
                <p><strong>Вс:</strong> 10:00 - 16:00</p>
                <p>Принимаем по предварительной записи</p>
            </div>

            <!-- Форма обратной связи -->
            <div class="footer-section">
                <h3>Обратная связь</h3>
                <div class="support-form">
                    <form id="supportForm">
                        <div class="input-group">
                            <input type="text" placeholder="Ваше имя" required>
                        </div>
                        <div class="input-group">
                            <input type="email" placeholder="Ваш email" required>
                        </div>
                        <div class="input-group">
                            <textarea placeholder="Ваше сообщение..." required></textarea>
                        </div>
                        <button type="submit" class="form-btn">Отправить</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-links">
                <a href="/privacy.php">Политика конфиденциальности</a>
                <a href="/terms.php">Пользовательское соглашение</a>
                <a href="/security.php">Безопасность данных</a>
                <a href="/license.php">Лицензии</a>
            </div>
            <p>&copy; 2024 Стоматология "Улыбка". Все права защищены.</p>
            <p>Информация на сайте носит ознакомительный характер. Имеются противопоказания, необходима консультация специалиста.</p>
        </div>
    </footer>

    <!-- МОДАЛЬНОЕ ОКНО ВХОДА/РЕГИСТРАЦИИ -->
    <div id="authModal" class="modal">
        <div class="glass-modal">
            <span class="close">&times;</span>
            
            <!-- Форма входа -->
            <div id="loginForm">
                <h2>Вход в аккаунт</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Пароль" required>
                    </div>
                    <button type="submit" class="form-btn">Войти</button>
                </form>
                <div class="switch-text">
                    Нет аккаунта? <a href="#" id="showRegister">Зарегистрироваться</a>
                </div>
            </div>

            <!-- Форма регистрации -->
            <div id="registerForm" style="display: none;">
                <h2>Регистрация</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    <div class="input-group">
                        <input type="text" name="name" placeholder="ФИО" required>
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="input-group">
                        <input type="tel" name="phone" placeholder="Телефон">
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Пароль" required minlength="6">
                    </div>
                    <button type="submit" class="form-btn">Зарегистрироваться</button>
                </form>
                <div class="switch-text">
                    Уже есть аккаунт? <a href="#" id="showLogin">Войти</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Управление модальным окном авторизации
        const authModal = document.getElementById('authModal');
        const loginBtn = document.getElementById('loginBtn');
        const closeBtn = document.querySelector('.close');
        const showRegister = document.getElementById('showRegister');
        const showLogin = document.getElementById('showLogin');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        // Открытие модального окна
        if (loginBtn) {
            loginBtn.addEventListener('click', function(e) {
                e.preventDefault();
                authModal.style.display = 'block';
            });
        }

        // Закрытие модального окна
        closeBtn.addEventListener('click', function() {
            authModal.style.display = 'none';
        });

        // Закрытие при клике вне окна
        window.addEventListener('click', function(e) {
            if (e.target === authModal) {
                authModal.style.display = 'none';
            }
        });

        // Переключение между формами входа и регистрации
        showRegister.addEventListener('click', function(e) {
            e.preventDefault();
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
        });

        showLogin.addEventListener('click', function(e) {
            e.preventDefault();
            registerForm.style.display = 'none';
            loginForm.style.display = 'block';
        });

        // Управление выпадающим меню профиля
        const profileToggle = document.getElementById('profileToggle');
        const profileDropdown = document.getElementById('profileDropdown');

        if (profileToggle && profileDropdown) {
            profileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
            });

            // Закрытие меню при клике вне его
            document.addEventListener('click', function() {
                profileDropdown.classList.remove('show');
            });

            // Предотвращение закрытия при клике внутри меню
            profileDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        // Автоматическое скрытие уведомлений
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
        });

        // Плавная прокрутка к якорям
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Валидация формы обратной связи
        const supportForm = document.getElementById('supportForm');
        if (supportForm) {
            supportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                alert('Сообщение отправлено! Мы свяжемся с вами в ближайшее время.');
                this.reset();
            });
        }

        

       
    </script>
</body>
</html>