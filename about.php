<?php
// about.php
require_once __DIR__.'/inc/functions.php';

$errors = $_SESSION['contact_errors'] ?? [];
$formData = $_SESSION['contact_data'] ?? [];

unset($_SESSION['contact_errors']);
unset($_SESSION['contact_data']);

$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О сайте - TeacherPage</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .error-container {
            margin-bottom: 2rem;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .error-alert {
            padding: 1.25rem 1.5rem;
            border-left: 5px solid #dc3545;
            background-color: #f8d7da;
            color: #721c24;
            animation: slideIn 0.3s ease-out;
        }
        
        .error-alert h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: #721c24;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .error-alert h3 i {
            font-size: 1.2rem;
        }
        
        .error-alert ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.5rem;
        }
        
        .error-alert li {
            margin-bottom: 0.25rem;
        }
        
        .error-alert li:last-child {
            margin-bottom: 0;
        }
        
        .success-container {
            margin-bottom: 2rem;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .success-alert {
            padding: 1.25rem 1.5rem;
            border-left: 5px solid #38a169;
            background-color: #f0fff4;
            color: #2d774a;
            animation: slideIn 0.3s ease-out;
        }
        
        .success-alert h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: #2d774a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-field {
            border-color: #dc3545 !important;
            background-color: rgba(220, 53, 69, 0.05) !important;
            transition: border-color 0.3s ease;
        }
        
        .error-field:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .error-label {
            color: #dc3545 !important;
            font-weight: 600;
        }
        
        .error-hint {
            font-size: 0.875rem;
            color: #dc3545;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .contact-form {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e9ecef;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .feature-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: var(--radius);
            border-left: 4px solid var(--primary);
        }
        
        .feature-card h4 {
            margin-top: 0;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .feature-card i {
            color: var(--primary);
        }
    </style>
</head>
<body class="minimal-page about-page">
    <?php require_once 'menu.php'; ?>

    <main class="main">
        <div class="container">
            <div class="card">
                <h1>О сайте</h1>
                <p>Информационный портал персональных страниц преподавателей. Мы предоставляем платформу для преподавателей, чтобы они могли создать свою персональную страницу и поделиться опытом с коллегами и студентами.</p>
                
                <div style="margin: 2rem 0;">
                    <h3>Наши преимущества:</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 0.5rem 0;"><i class="fa-solid fa-check"></i> Профессиональное представление</li>
                        <li style="padding: 0.5rem 0;"><i class="fa-solid fa-check"></i> Удобное управление контентом</li>
                        <li style="padding: 0.5rem 0;"><i class="fa-solid fa-check"></i> Быстрая модерация</li>
                    </ul>
                </div>

                <?php if (!empty($_SESSION['contact_success'])): ?>
                <div class="success-container">
                    <div class="success-alert">
                        <h3><i class="fas fa-check-circle"></i> Сообщение отправлено!</h3>
                        <p><?= e($_SESSION['contact_success']) ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['contact_success']); ?>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="error-container">
                    <div class="error-alert">
                        <h3><i class="fas fa-exclamation-triangle"></i> Ошибка отправки формы</h3>
                        <?php if (count($errors) === 1): ?>
                            <p><?= e($errors[0]) ?></p>
                        <?php else: ?>
                            <p>Пожалуйста, исправьте следующие ошибки:</p>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <h2>Связаться с нами</h2>
                <form action="contact_submit.php" method="post" class="contact-form" id="contact-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    
                    <div class="form-group">
                        <label class="form-label <?= in_array('Имя обязательно для заполнения', $errors) ? 'error-label' : '' ?>">Имя *</label>
                        <input type="text" 
                               name="name" 
                               class="form-control <?= in_array('Имя обязательно для заполнения', $errors) ? 'error-field' : '' ?>" 
                               required
                               placeholder="Ваше имя"
                               value="<?= e($formData['name'] ?? '') ?>">
                        <?php if (in_array('Имя обязательно для заполнения', $errors)): ?>
                            <div class="error-hint"><i class="fas fa-exclamation-circle"></i> Поле обязательно для заполнения</div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label <?= in_array('Email обязателен для заполнения', $errors) ? 'error-label' : '' ?>">Email *</label>
                        <input type="email" 
                               name="email" 
                               class="form-control <?= in_array('Email обязателен для заполнения', $errors) ? 'error-field' : '' ?>" 
                               required
                               placeholder="example@mail.com"
                               value="<?= e($formData['email'] ?? '') ?>">
                        <?php if (in_array('Email обязателен для заполнения', $errors)): ?>
                            <div class="error-hint"><i class="fas fa-exclamation-circle"></i> Введите корректный email</div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label <?= in_array('Сообщение обязательно для заполнения', $errors) ? 'error-label' : '' ?>">Сообщение *</label>
                        <textarea name="message" 
                                  rows="5" 
                                  class="form-control <?= in_array('Сообщение обязательно для заполнения', $errors) ? 'error-field' : '' ?>" 
                                  required
                                  placeholder="Ваше сообщение..."><?= e($formData['message'] ?? '') ?></textarea>
                        <?php if (in_array('Сообщение обязательно для заполнения', $errors)): ?>
                            <div class="error-hint"><i class="fas fa-exclamation-circle"></i> Поле обязательно для заполнения</div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label <?= in_array('Неверный код капчи. Пожалуйста, попробуйте снова.', $errors) ? 'error-label' : '' ?>">Капча *</label>
                        <?php echo captcha_html('contact'); ?>
                        <div style="margin-top: 1rem;">
                            <label class="form-label <?= in_array('Неверный код капчи. Пожалуйста, попробуйте снова.', $errors) ? 'error-label' : '' ?>">Введите текст с картинки:</label>
                            <input type="text" 
                                   name="captcha" 
                                   class="form-control <?= in_array('Неверный код капчи. Пожалуйста, попробуйте снова.', $errors) ? 'error-field' : '' ?>" 
                                   required 
                                   placeholder="Введите символы с изображения"
                                   value="<?= e($formData['captcha'] ?? '') ?>">
                            <?php if (in_array('Неверный код капчи. Пожалуйста, попробуйте снова.', $errors)): ?>
                                <div class="error-hint"><i class="fas fa-exclamation-circle"></i> Неверный код капчи. Пожалуйста, попробуйте снова.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Отправить сообщение
                        </button>
                        <button type="reset" class="btn btn-outline">
                            <i class="fas fa-undo"></i> Очистить форму
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php require_once 'site_footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (in_array('Неверный код капчи. Пожалуйста, попробуйте снова.', $errors)): ?>
            setTimeout(function() {
                const captchaImages = document.querySelectorAll('img[src*="captcha"]');
                captchaImages.forEach(function(img) {
                    img.src = img.src.split('?')[0] + '?t=' + new Date().getTime();
                });
            }, 500);
        <?php endif; ?>
        
        const firstErrorField = document.querySelector('.error-field');
        if (firstErrorField) {
            setTimeout(function() {
                firstErrorField.focus();
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
        
        const hasErrors = <?= !empty($errors) ? 'true' : 'false' ?>;
        const hasSuccess = <?= !empty($_SESSION['contact_success']) ? 'true' : 'false' ?>;
        
        if (hasErrors || hasSuccess) {
            setTimeout(function() {
                const target = document.querySelector('.error-container') || 
                              document.querySelector('.success-container') ||
                              document.getElementById('contact-form');
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 200);
        }
        
        const contactForm = document.getElementById('contact-form');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                const nameField = this.querySelector('[name="name"]');
                const emailField = this.querySelector('[name="email"]');
                const messageField = this.querySelector('[name="message"]');
                const captchaField = this.querySelector('[name="captcha"]');
                
                let hasError = false;
                
                if (nameField && nameField.value.trim() === '') {
                    markFieldError(nameField, 'Имя обязательно для заполнения');
                    hasError = true;
                } else {
                    clearFieldError(nameField);
                }
                
                if (emailField && emailField.value.trim() === '') {
                    markFieldError(emailField, 'Email обязателен для заполнения');
                    hasError = true;
                } else if (emailField && !isValidEmail(emailField.value)) {
                    markFieldError(emailField, 'Введите корректный email адрес');
                    hasError = true;
                } else {
                    clearFieldError(emailField);
                }
                
                if (messageField && messageField.value.trim() === '') {
                    markFieldError(messageField, 'Сообщение обязательно для заполнения');
                    hasError = true;
                } else {
                    clearFieldError(messageField);
                }
                
                if (captchaField && captchaField.value.trim() === '') {
                    markFieldError(captchaField, 'Введите код с картинки');
                    hasError = true;
                } else {
                    clearFieldError(captchaField);
                }
                
                if (hasError) {
                    e.preventDefault();
                    const firstError = this.querySelector('.error-field');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        }
        
        function markFieldError(field, message) {
            field.classList.add('error-field');
            let errorDiv = field.parentNode.querySelector('.field-error');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'field-error error-hint';
                field.parentNode.appendChild(errorDiv);
            }
            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
        }
        
        function clearFieldError(field) {
            field.classList.remove('error-field');
            const errorDiv = field.parentNode.querySelector('.field-error');
            if (errorDiv) {
                errorDiv.remove();
            }
        }
        
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        const resetBtn = contactForm?.querySelector('button[type="reset"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                const captchaImages = document.querySelectorAll('img[src*="captcha"]');
                captchaImages.forEach(function(img) {
                    img.src = img.src.split('?')[0] + '?t=' + new Date().getTime();
                });
            });
        }
    });
    </script>
</body>
</html>