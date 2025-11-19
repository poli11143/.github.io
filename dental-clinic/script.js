// Открытие/закрытие модального окна входа
const modal = document.getElementById('authModal');
const loginBtn = document.getElementById('loginBtn');
const closeBtn = document.querySelector('.close');
const switchLink = document.getElementById('switchLink');
const formAction = document.getElementById('formAction');
const modalTitle = document.getElementById('modalTitle');
const submitBtn = document.getElementById('submitBtn');
const nameField = document.getElementById('nameField');
const switchText = document.getElementById('switchText');

let isLogin = true;

function openModal() {
    modal.style.display = 'block';
}

function closeModal() {
    modal.style.display = 'none';
}

function switchMode() {
    isLogin = !isLogin;
    if (isLogin) {
        formAction.value = 'login';
        modalTitle.textContent = 'Вход';
        submitBtn.textContent = 'Войти';
        nameField.style.display = 'none';
        switchText.innerHTML = 'Нет аккаунта? <a href="#" id="switchLink">Зарегистрируйтесь</a>';
    } else {
        formAction.value = 'register';
        modalTitle.textContent = 'Регистрация';
        submitBtn.textContent = 'Зарегистрироваться';
        nameField.style.display = 'block';
        switchText.innerHTML = 'Уже есть аккаунт? <a href="#" id="switchLink">Войдите</a>';
    }
    // Перепривязываем событие после изменения HTML
    document.getElementById('switchLink').addEventListener('click', switchMode);
}

// События
loginBtn.addEventListener('click', openModal);
closeBtn.addEventListener('click', closeModal);
switchLink.addEventListener('click', switchMode);

// Закрытие модального окна при клике вне его
window.addEventListener('click', (event) => {
    if (event.target == modal) {
        closeModal();
    }
});

// Простая валидация и отправка формы записи (заглушка)
document.getElementById('appointmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Спасибо! Мы свяжемся с вами для подтверждения записи.');
    this.reset();
});

// Анимация появления элементов при скролле (улучшение)
const observerOptions = {
    threshold: 0.1
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = `fadeIn 1s ease-out forwards`;
        }
    });
}, observerOptions);

// Наблюдаем за всеми секциями
document.querySelectorAll('section').forEach(section => {
    observer.observe(section);
});