<?php
// add.php
require_once __DIR__.'/inc/functions.php';

$errors = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];

unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);

$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить страницу - TeacherPage</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .phone-input-container {
            position: relative;
        }
        
        .phone-prefix {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-weight: 500;
            pointer-events: none;
            z-index: 2;
        }
        
        .phone-hint {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .phone-hint i {
            color: var(--primary);
        }
        
        .phone-valid {
        }
        
        .phone-invalid {
            border-color: var(--danger) !important;
            background-color: rgba(220, 53, 69, 0.05) !important;
        }
        
        .phone-validation {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border-radius: var(--radius);
            font-size: 0.85rem;
            display: none;
        }
        
        .phone-validation.invalid {
            display: block;
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
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
    </style>
</head>
<body class="minimal-page add-page">
    <?php require_once 'menu.php'; ?>

    <main class="main">
        <div class="container">
            <div class="card">
                <h1>Добавить свою страницу</h1>
                <p>Заполните форму ниже, чтобы создать персональную страницу преподавателя. После модерации ваша страница будет опубликована.</p>
                
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

                <form action="submit.php" method="post" enctype="multipart/form-data" id="teacher-form">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    
                    <div class="form-group">
                        <label class="form-label <?= in_array('ФИО обязательно.', $errors) ? 'error-label' : '' ?>">ФИО *</label>
                        <input type="text" 
                               name="fio" 
                               class="form-control <?= in_array('ФИО обязательно.', $errors) ? 'error-field' : '' ?>" 
                               required 
                               placeholder="Иванов Иван Иванович"
                               value="<?= e($formData['fio'] ?? '') ?>">
                        <?php if (in_array('ФИО обязательно.', $errors)): ?>
                            <div class="error-hint"><i class="fas fa-exclamation-circle"></i> Поле обязательно для заполнения</div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Дата рождения</label>
                        <input type="date" 
                               name="birth_date" 
                               class="form-control" 
                               value="<?= e($formData['birth_date'] ?? '') ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Фотография</label>
                        <div class="photo-upload-area">
                            <input type="file" name="photo" accept="image/jpeg,image/png" class="file-upload" data-crop="true" style="display: none;">
                            <div class="loading-overlay">
                                <div class="loading-spinner"></div>
                            </div>
                            <div class="photo-upload-icon"><i class="fa-solid fa-camera"></i></div>
                            <div class="photo-upload-text">Нажмите или перетащите фото</div>
                            <div class="photo-upload-hint">Макс. размер: 10MB, форматы: JPG, PNG</div>

                            <div class="photo-preview-container">
                                <img src="" class="photo-preview" style="display: none;">
                                <div class="photo-preview-actions" style="display: none;">
                                    <button type="button" class="photo-action-btn" onclick="showCropperModal(this)" title="Обрезать"><i class="fa-solid fa-scissors"></i></button>
                                    <button type="button" class="photo-action-btn" onclick="removePhoto(this)" title="Удалить"><i class="fa-solid fa-trash-can"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Должность</label>
                        <input type="text" 
                               name="position" 
                               class="form-control" 
                               placeholder="Преподаватель высшей математики"
                               value="<?= e($formData['position'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Место работы</label>
                        <input type="text" 
                               name="organization" 
                               class="form-control" 
                               placeholder="Университет имени..."
                               value="<?= e($formData['organization'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Стаж (лет)</label>
                        <input type="number" 
                               name="work_experience_years" 
                               class="form-control" 
                               min="0" 
                               placeholder="5"
                               value="<?= e($formData['work_experience_years'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Телефон</label>
                        <div class="phone-input-container">
                            <input type="tel" 
                                   name="phone" 
                                   id="phoneInput" 
                                   class="form-control phone-input" 
                                   placeholder="+7(999) 123-45-67"
                                   maxlength="18"
                                   autocomplete="tel"
                                   value="<?= e($formData['phone'] ?? '') ?>">
                        </div>
                        <div id="phoneValidation" class="phone-validation"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="ivanov@example.com"
                               value="<?= e($formData['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Личный сайт</label>
                        <input type="url" 
                               name="personal_site" 
                               class="form-control" 
                               placeholder="https://example.com"
                               value="<?= e($formData['personal_site'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Социальные сети</label>
                        <div id="social-container">
                            <?php
                            $socialTypes = $formData['social_type'] ?? [''];
                            $socialUrls = $formData['social_url'] ?? [''];
                            
                            for ($i = 0; $i < max(count($socialTypes), count($socialUrls)); $i++):
                            ?>
                                <div class="social-row">
                                    <input type="text" 
                                           name="social_type[]" 
                                           class="form-control" 
                                           placeholder="Тип (VK, Telegram)"
                                           value="<?= e($socialTypes[$i] ?? '') ?>">
                                    <input type="url" 
                                           name="social_url[]" 
                                           class="form-control" 
                                           placeholder="Ссылка"
                                           value="<?= e($socialUrls[$i] ?? '') ?>">
                                    <button type="button" class="remove-social" onclick="removeSocialRow(this)">✖</button>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <button type="button" class="btn btn-outline" onclick="addSocialRow()" style="margin-top: 0.5rem;">
                            + Добавить социальную сеть
                        </button>
                    </div>

                    <div class="form-group">
                        <label class="form-label">О себе</label>
                        <textarea name="about" 
                                  rows="6" 
                                  class="form-control" 
                                  placeholder="Расскажите о своем профессиональном опыте, достижениях и интересах..."><?= e($formData['about'] ?? '') ?></textarea>
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

                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; justify-content: center;">
                        <button type="submit" class="btn btn-primary">
                            Отправить на модерацию
                        </button>
                        <a href="index.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php require_once 'site_footer.php'; ?>

    <div class="cropper-modal" id="cropperModal">
        <div class="cropper-container">
            <div class="cropper-header">
                <h3 class="cropper-title">Обрезка фотографии</h3>
                <button type="button" class="cropper-close" onclick="cancelCrop()" aria-label="Закрыть">&times;</button>
            </div>

            <div class="cropper-content">
                <div class="cropper-instructions">
                    <h4><i class="fa-solid fa-clipboard-list"></i> Инструкция:</h4>
                    <ul>
                        <li>Перетащите рамку для выбора области обрезки</li>
                        <li>Используйте пальцы для масштабирования на мобильных</li>
                        <li>Рекомендуется квадратное изображение для профиля</li>
                    </ul>
                </div>

                <div class="cropper-preview-container">
                    <img id="cropperImage" class="cropper-preview" alt="Обрезка изображения">
                </div>
            </div>

            <div class="cropper-controls">
                <div class="cropper-actions">
                    <button type="button" class="btn btn-outline" onclick="rotateCropper(-90)">
                        <span class="mobile-hidden"><i class="fa-solid fa-rotate-left"></i> Влево</span>
                        <span class="mobile-only">↺</span>
                    </button>
                    <button type="button" class="btn btn-outline" onclick="rotateCropper(90)">
                        <span class="mobile-hidden"><i class="fa-solid fa-rotate-right"></i> Вправо</span>
                        <span class="mobile-only">↻</span>
                    </button>
                </div>
                <div class="cropper-actions">
                    <button type="button" class="btn btn-secondary" onclick="cancelCrop()">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="applyCrop()">
                        <span class="mobile-hidden"><i class="fa-solid fa-check"></i> Применить обрезку</span>
                        <span class="mobile-only"><i class="fa-solid fa-check"></i> Готово</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('phoneInput');
        const phoneValidation = document.getElementById('phoneValidation');
        
        if (!phoneInput) return;
        
        function formatPhoneNumber(value) {
            let numbers = value.replace(/\D/g, '');
            
            if (numbers.startsWith('8')) {
                numbers = '7' + numbers.substring(1);
            } else if (numbers.startsWith('9')) {
                numbers = '7' + numbers;
            }
            
            numbers = numbers.substring(0, 11);
            
            let formatted = '';
            if (numbers.length > 0) {
                formatted = '+7';
                if (numbers.length > 1) {
                    formatted += '(' + numbers.substring(1, 4);
                }
                if (numbers.length >= 4) {
                    formatted += ') ' + numbers.substring(4, 7);
                }
                if (numbers.length >= 7) {
                    formatted += ' ' + numbers.substring(7, 9);
                }
                if (numbers.length >= 9) {
                    formatted += '-' + numbers.substring(9, 11);
                }
            }
            
            return formatted;
        }
        
        function getCleanPhoneNumber(value) {
            return value.replace(/\D/g, '');
        }
        
        function validatePhoneNumber(value) {
            const numbers = getCleanPhoneNumber(value);
            
            if (numbers.length === 0) {
                return {
                    isValid: true,
                    message: ''
                };
            }
            
            if (numbers.length === 11 && numbers.startsWith('7')) {
                return {
                    isValid: true,
                };
            } else {
                return {
                    isValid: false,
                    message: '✗ Номер должен содержать 10 цифр после +7'
                };
            }
        }
        
        phoneInput.addEventListener('keydown', function(e) {
            if (e.keyCode !== 8 && e.keyCode !== 46) {
                return;
            }
            
            e.preventDefault();
            
            const cursorStart = this.selectionStart;
            const cursorEnd = this.selectionEnd;
            let value = this.value;
            
            if (cursorStart !== cursorEnd) {
                const before = value.substring(0, cursorStart);
                const after = value.substring(cursorEnd);
                const newValue = before + after;
                
                const cleanNumbers = getCleanPhoneNumber(newValue);
                const formattedValue = formatPhoneNumber(cleanNumbers);
                this.value = formattedValue;
                
                const newCursorPos = Math.min(cursorStart, formattedValue.length);
                this.setSelectionRange(newCursorPos, newCursorPos);
                
                const validation = validatePhoneNumber(formattedValue);
                updatePhoneValidation(validation);
                return;
            }
            
            if (e.keyCode === 8 && cursorStart > 0) {
                let deletePos = cursorStart - 1;
                
                while (deletePos >= 0 && !/\d/.test(value[deletePos])) {
                    deletePos--;
                }
                
                if (deletePos >= 0 && /\d/.test(value[deletePos])) {
                    const before = value.substring(0, deletePos);
                    const after = value.substring(deletePos + 1);
                    const newValue = before + after;
                    
                    const cleanNumbers = getCleanPhoneNumber(newValue);
                    const formattedValue = formatPhoneNumber(cleanNumbers);
                    this.value = formattedValue;
                    
                    let newCursorPos = deletePos;
                    if (newCursorPos >= formattedValue.length) {
                        newCursorPos = formattedValue.length;
                    }
                    this.setSelectionRange(newCursorPos, newCursorPos);
                    
                    const validation = validatePhoneNumber(formattedValue);
                    updatePhoneValidation(validation);
                }
            }
            
            if (e.keyCode === 46 && cursorStart < value.length) {
                let deletePos = cursorStart;
                
                while (deletePos < value.length && !/\d/.test(value[deletePos])) {
                    deletePos++;
                }
                
                if (deletePos < value.length && /\d/.test(value[deletePos])) {
                    const before = value.substring(0, deletePos);
                    const after = value.substring(deletePos + 1);
                    const newValue = before + after;
                    
                    const cleanNumbers = getCleanPhoneNumber(newValue);
                    const formattedValue = formatPhoneNumber(cleanNumbers);
                    this.value = formattedValue;
                    
                    let newCursorPos = cursorStart;
                    if (newCursorPos >= formattedValue.length) {
                        newCursorPos = formattedValue.length;
                    }
                    this.setSelectionRange(newCursorPos, newCursorPos);
                    
                    const validation = validatePhoneNumber(formattedValue);
                    updatePhoneValidation(validation);
                }
            }
        });
        
        phoneInput.addEventListener('input', function(e) {
            if (e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward') {
                return;
            }
            
            const cursorPosition = e.target.selectionStart;
            const oldValue = e.target.value;
            
            const cleanNumbers = getCleanPhoneNumber(oldValue);
            
            const formattedValue = formatPhoneNumber(cleanNumbers);
            
            e.target.value = formattedValue;
            
            let newCursorPosition = cursorPosition;
            
            if (formattedValue.length > oldValue.length) {
                const addedChars = formattedValue.length - oldValue.length;
                newCursorPosition = Math.min(cursorPosition + addedChars, formattedValue.length);
            }
            
            e.target.setSelectionRange(newCursorPosition, newCursorPosition);
            
            const validation = validatePhoneNumber(formattedValue);
            updatePhoneValidation(validation);
        });
        
        phoneInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cleanNumbers = getCleanPhoneNumber(pastedText);
            const formattedValue = formatPhoneNumber(cleanNumbers);
            this.value = formattedValue;
            
            const validation = validatePhoneNumber(formattedValue);
            updatePhoneValidation(validation);
            
            this.setSelectionRange(formattedValue.length, formattedValue.length);
        });
        
        phoneInput.addEventListener('cut', function(e) {
            setTimeout(() => {
                const cleanNumbers = getCleanPhoneNumber(this.value);
                const formattedValue = formatPhoneNumber(cleanNumbers);
                this.value = formattedValue;
                
                const validation = validatePhoneNumber(formattedValue);
                updatePhoneValidation(validation);
            }, 0);
        });
        
        phoneInput.addEventListener('focus', function() {
            if (this.value === '') {
                this.value = '+7';
                this.classList.add('phone-valid');
            }
            
            setTimeout(() => {
                if (this.value.length > 2) {
                    this.setSelectionRange(2, this.value.length);
                }
            }, 10);
        });
        
        phoneInput.addEventListener('blur', function() {
            const validation = validatePhoneNumber(this.value);
            updatePhoneValidation(validation);
            
            if (this.value === '+7') {
                this.value = '';
                this.classList.remove('phone-valid', 'phone-invalid');
                phoneValidation.className = 'phone-validation';
                phoneValidation.textContent = '';
            }
        });
        
        function updatePhoneValidation(validation) {
            phoneInput.classList.remove('phone-valid', 'phone-invalid');
            
            if (validation.isValid === true) {
                phoneInput.classList.add('phone-valid');
                phoneValidation.className = 'phone-validation';
            } else if (validation.isValid === false) {
                phoneInput.classList.add('phone-invalid');
                phoneValidation.className = 'phone-validation invalid';
                phoneValidation.textContent = validation.message;
            } else {
                phoneValidation.className = 'phone-validation';
                phoneValidation.textContent = '';
            }
        }
        
        const initialValidation = validatePhoneNumber(phoneInput.value);
        updatePhoneValidation(initialValidation);
        
        const form = document.getElementById('teacher-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const phoneValue = phoneInput.value;
                const validation = validatePhoneNumber(phoneValue);
                
                if (phoneValue && !validation.isValid) {
                    e.preventDefault();
                    phoneInput.classList.add('phone-invalid');
                    phoneValidation.className = 'phone-validation invalid';
                    phoneValidation.textContent = 'Пожалуйста, введите корректный номер телефона';
                    phoneInput.focus();
                }
            });
        }
        
        addClearButton();
        
        function addClearButton() {
            const clearButton = document.createElement('button');
            clearButton.type = 'button';
            clearButton.innerHTML = '&times;';
            clearButton.title = 'Очистить поле';
            clearButton.style.cssText = `
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                font-size: 1.5rem;
                color: #999;
                cursor: pointer;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 3;
            `;
            
            clearButton.addEventListener('mouseenter', function() {
                this.style.color = '#dc3545';
            });
            
            clearButton.addEventListener('mouseleave', function() {
                this.style.color = '#999';
            });
            
            clearButton.addEventListener('click', function() {
                phoneInput.value = '';
                phoneInput.classList.remove('phone-valid', 'phone-invalid');
                phoneValidation.className = 'phone-validation';
                phoneValidation.textContent = '';
                phoneInput.focus();
            });
            
            phoneInput.addEventListener('input', function() {
                if (this.value && this.value !== '+7') {
                    clearButton.style.display = 'flex';
                } else {
                    clearButton.style.display = 'none';
                }
            });
            
            phoneInput.parentElement.style.position = 'relative';
            phoneInput.parentElement.appendChild(clearButton);
            
            clearButton.style.display = 'none';
        }
        
        phoneInput.addEventListener('keydown', function(e) {
            const navigationKeys = [37, 38, 39, 40];
            if (navigationKeys.includes(e.keyCode)) {
                return;
            }
            
            const controlKeys = [
                8,9,13,16,17,18,20,27,33,34,35,36,45,46,91,92,93,112,113,114,115,116,117,118,119,120,121,122,123
            ];
            
            if (controlKeys.includes(e.keyCode)) {
                return;
            }
            
            const isDigit = (e.keyCode >= 48 && e.keyCode <= 57) ||
                            (e.keyCode >= 96 && e.keyCode <= 105);
            
            const isLetterWithCtrl = (e.keyCode >= 65 && e.keyCode <= 90) && (e.ctrlKey || e.metaKey);
            
            if (!isDigit && !isLetterWithCtrl && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
            }
        });
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
            }, 100);
        }
        
        if (<?= !empty($errors) ? 'true' : 'false' ?>) {
            setTimeout(function() {
                window.scrollTo({
                    top: document.querySelector('.error-hint')?.offsetTop - 100 || 0,
                    behavior: 'smooth'
                });
            }, 150);
        }
    });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
</body>
</html>