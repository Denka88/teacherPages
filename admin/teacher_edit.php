<?php
// admin/teacher_edit.php
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/../inc/upload.php';

$id = intval($_GET['id'] ?? 0);
$isEdit = ($id > 0);
$teacher = null;
$socials = [];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM teacher_pages WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    $teacher = $stmt->fetch();
    
    if (!$teacher) {
        die('Преподаватель не найден');
    }
    
    $stmt2 = $pdo->prepare("SELECT type, url FROM social_links WHERE teacher_id = :id");
    $stmt2->execute([':id'=>$id]);
    $socials = $stmt2->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fio = trim($_POST['fio'] ?? '');
    $birth_date = $_POST['birth_date'] ?: null;
    $position = $_POST['position'] ?: null;
    $organization = $_POST['organization'] ?: null;
    $work_experience_years = is_numeric($_POST['work_experience_years']) ? intval($_POST['work_experience_years']) : null;
    $phone = $_POST['phone'] ?: null;
    $email = $_POST['email'] ?: null;
    $personal_site = $_POST['personal_site'] ?: null;
    $about = $_POST['about'] ?: null;
    
    if (empty($fio)) {
        $error = "ФИО обязательно для заполнения";
    } else {
        $pdo->beginTransaction();
        try {
            $photo_path = $teacher['photo_path'] ?? null;
            
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $new_photo_path = handle_photo_upload($_FILES['photo']);
                    if ($new_photo_path) {
                        if ($isEdit && $teacher['photo_path'] && file_exists(__DIR__ . '/../' . $teacher['photo_path'])) {
                            unlink(__DIR__ . '/../' . $teacher['photo_path']);
                        }
                        $photo_path = $new_photo_path;
                    }
                } catch (RuntimeException $e) {
                    throw new Exception('Ошибка загрузки фото: ' . $e->getMessage());
                }
            }
            
            $delete_photo = $_POST['delete_photo'] ?? '';
            if ($delete_photo === 'yes' && $isEdit && $teacher['photo_path']) {
                if (file_exists(__DIR__ . '/../' . $teacher['photo_path'])) {
                    unlink(__DIR__ . '/../' . $teacher['photo_path']);
                }
                $photo_path = null;
            }
            
            if ($isEdit) {
                $stmt = $pdo->prepare("UPDATE teacher_pages SET 
                    fio = :fio, birth_date = :bd, photo_path = :pp, position = :pos, 
                    organization = :org, work_experience_years = :we, phone = :phone, 
                    email = :email, personal_site = :site, about = :about 
                    WHERE id = :id");
                $stmt->execute([
                    ':fio' => $fio, ':bd' => $birth_date, ':pp' => $photo_path,
                    ':pos' => $position, ':org' => $organization, ':we' => $work_experience_years,
                    ':phone' => $phone, ':email' => $email, ':site' => $personal_site,
                    ':about' => $about, ':id' => $id
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO teacher_pages 
                    (fio, birth_date, photo_path, position, organization, work_experience_years, 
                     phone, email, personal_site, about, published_at) 
                    VALUES (:fio, :bd, :pp, :pos, :org, :we, :phone, :email, :site, :about, NOW())");
                $stmt->execute([
                    ':fio' => $fio, ':bd' => $birth_date, ':pp' => $photo_path,
                    ':pos' => $position, ':org' => $organization, ':we' => $work_experience_years,
                    ':phone' => $phone, ':email' => $email, ':site' => $personal_site,
                    ':about' => $about
                ]);
                $id = $pdo->lastInsertId();
            }
            
            if ($isEdit) {
                $delStmt = $pdo->prepare("DELETE FROM social_links WHERE teacher_id = :id");
                $delStmt->execute([':id' => $id]);
            }
            
            if (!empty($_POST['social_type']) && is_array($_POST['social_type'])) {
                $insStmt = $pdo->prepare("INSERT INTO social_links (teacher_id, type, url) VALUES (:tid, :type, :url)");
                
                for ($i = 0; $i < count($_POST['social_type']); $i++) {
                    $type = trim($_POST['social_type'][$i] ?? '');
                    $url = trim($_POST['social_url'][$i] ?? '');
                    
                    if ($type !== "" && $url !== "") {
                        $insStmt->execute([
                            ':tid' => $id, ':type' => $type, ':url' => $url
                        ]);
                    }
                }
            }
            
            $pdo->commit();
            header('Location: teachers.php');
            exit;
            
        } catch (Exception $ex) {
            $pdo->rollBack();
            $error = "Ошибка сохранения: " . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Редактирование' : 'Создание' ?> преподавателя - TeacherPage</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
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
    </style>
</head>
<body class="admin-body">
    <?php require_once 'menu.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-welcome"><?= $isEdit ? 'Редактирование' : 'Создание' ?> преподавателя</h1>
            <div>
                <a href="teachers.php" class="btn btn-secondary">← Назад к списку</a>
            </div>
        </div>

        <div class="card">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data" id="teacher-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <div class="form-group">
                            <label class="form-label">ФИО *</label>
                            <input type="text" name="fio" class="form-control" 
                                   value="<?= e($teacher['fio'] ?? '') ?>" required 
                                   placeholder="Иванов Иван Иванович">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Дата рождения</label>
                            <input type="date" name="birth_date" class="form-control" 
                                   value="<?= e($teacher['birth_date'] ?? '') ?>"
                                   max="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Должность</label>
                            <input type="text" name="position" class="form-control" 
                                   value="<?= e($teacher['position'] ?? '') ?>" 
                                   placeholder="Старший преподаватель">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Место работы</label>
                            <input type="text" name="organization" class="form-control" 
                                   value="<?= e($teacher['organization'] ?? '') ?>" 
                                   placeholder="Университет имени...">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Стаж (лет)</label>
                            <input type="number" name="work_experience_years" class="form-control" 
                                   value="<?= e($teacher['work_experience_years'] ?? '') ?>" 
                                   min="0" placeholder="5">
                        </div>
                    </div>

                    <div>
                        <div class="form-group">
                            <label class="form-label">Фотография</label>
                                
                            <?php if ($isEdit && !empty($teacher['photo_path'])): ?>
                            <?php
                            $fullPhotoPath = __DIR__ . '/../' . $teacher['photo_path'];
                            $webPhotoPath = '../' . $teacher['photo_path'];
                            $photoExists = file_exists($fullPhotoPath);
                            ?>
                            <div style="margin-bottom: 2rem;">
                                <strong>Текущее фото:</strong>
                                <div class="photo-preview-container" style="margin: 1rem 0;">
                                    <?php if ($photoExists): ?>
                                        <img src="<?= e($webPhotoPath) ?>?v=<?= time() ?>" 
                                             alt="Текущее фото" 
                                             class="photo-preview"
                                             style="display: block; max-width: 300px; max-height: 300px; border-radius: var(--radius);">
                                        <div class="photo-preview-actions">
                                            <button type="button" class="photo-action-btn" onclick="removeCurrentPhoto()" title="Удалить">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div style="background: #f8f9fa; padding: 2rem; text-align: center; border-radius: var(--radius); color: #666; border: 2px dashed #ddd;">
                                            <i class="fa-solid fa-image" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                            <div>Фото не найдено на сервере</div>
                                            <small>Путь: <?= e($teacher['photo_path']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                                    <input type="checkbox" name="delete_photo" value="yes" id="deletePhotoCheckbox">
                                    Удалить текущее фото
                                </label>
                            </div>
                            <label>Заменить фото:</label>
                            <?php endif; ?>
                                    
                            <div class="photo-upload-area" id="photoUploadArea">
                                <input type="file" name="photo" accept="image/jpeg,image/png" class="file-upload" data-crop="true" style="display: none;">
                                <div class="loading-overlay">
                                    <div class="loading-spinner"></div>
                                </div>
                                <div class="photo-upload-icon"><i class="fa-solid fa-camera"></i></div>
                                <div class="photo-upload-text">Нажмите или перетащите фото</div>
                                <div class="photo-upload-hint">Макс. размер: 2MB, форматы: JPG, PNG</div>
                                    
                                <div class="photo-preview-container">
                                    <img src="" class="photo-preview" style="display: none;">
                                    <div class="photo-preview-actions" style="display: none;">
                                        <button type="button" class="photo-action-btn" onclick="showCropperModal(this)" title="Обрезать">
                                            <i class="fa-solid fa-scissors"></i>
                                        </button>
                                        <button type="button" class="photo-action-btn" onclick="removePhoto(this)" title="Удалить">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Телефон</label>
                        <div class="phone-input-container">
                            <input type="tel" 
                                   name="phone" 
                                   id="phoneInput" 
                                   class="form-control phone-input" 
                                   value="<?= e($teacher['phone'] ?? '') ?>"
                                   placeholder="+7(999) 123-45-67"
                                   maxlength="18"
                                   autocomplete="tel">
                        </div>
                        <div id="phoneValidation" class="phone-validation"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= e($teacher['email'] ?? '') ?>" 
                               placeholder="ivanov@example.com">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Личный сайт</label>
                    <input type="url" name="personal_site" class="form-control" 
                           value="<?= e($teacher['personal_site'] ?? '') ?>" 
                           placeholder="https://example.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Социальные сети</label>
                    <div id="social-container">
                        <?php if (empty($socials)): ?>
                            <div class="social-row">
                                <input type="text" name="social_type[]" class="form-control" placeholder="Тип (VK, Telegram)">
                                <input type="url" name="social_url[]" class="form-control" placeholder="Ссылка">
                                <button type="button" class="remove-social" onclick="removeSocialRow(this)">✖</button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($socials as $social): ?>
                                <div class="social-row">
                                    <input type="text" name="social_type[]" class="form-control" 
                                           value="<?= e($social['type']) ?>" placeholder="Тип (VK, Telegram)">
                                    <input type="url" name="social_url[]" class="form-control" 
                                           value="<?= e($social['url']) ?>" placeholder="Ссылка">
                                    <button type="button" class="remove-social" onclick="removeSocialRow(this)">✖</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-outline" onclick="addSocialRow()" style="margin-top: 0.5rem;">
                        + Добавить социальную сеть
                    </button>
                </div>

                <div class="form-group">
                    <label class="form-label">О себе</label>
                    <textarea name="about" rows="6" class="form-control" 
                              placeholder="Расскажите о профессиональном опыте, достижениях и интересах..."><?= e($teacher['about'] ?? '') ?></textarea>
                </div>

                <div style="display: flex; gap: 1rem; align-items: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $isEdit ? 'Сохранить изменения' : 'Создать преподавателя' ?>
                    </button>
                    <a href="teachers.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Отмена
                    </a>
                    <?php if ($isEdit): ?>
                    <a href="../view.php?id=<?= e($id) ?>" target="_blank" class="btn btn-outline">
                        <i class="fas fa-external-link-alt"></i> Просмотр на сайте
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="cropper-modal" id="cropperModal">
        <div class="cropper-container">
            <div class="cropper-header">
                <h3 class="cropper-title">Обрезка фотографии</h3>
                <button type="button" class="cropper-close" onclick="cancelCrop()">&times;</button>
            </div>

            <div class="cropper-content">
                <div class="cropper-instructions">
                    <h4><i class="fa-solid fa-clipboard-list"></i> Инструкция:</h4>
                    <ul>
                        <li>Перетащите рамку для выбора области обрезки</li>
                        <li>Используйте ползунок для изменения масштаба</li>
                        <li>Рекомендуется квадратное изображение для профиля</li>
                    </ul>
                </div>

                <div class="cropper-preview-container strict">
                    <img id="cropperImage" class="cropper-preview">
                </div>
            </div>

            <div class="cropper-controls">
                <div class="cropper-actions">
                    <button type="button" class="btn btn-outline" onclick="rotateCropper(-90)"><i class="fa-solid fa-rotate-left"></i> Влево</button>
                    <button type="button" class="btn btn-outline" onclick="rotateCropper(90)"><i class="fa-solid fa-rotate-right"></i> Вправо</button>
                </div>
                <div class="cropper-actions">
                    <button type="button" class="btn btn-secondary" onclick="cancelCrop()">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="applyCrop()"><i class="fa-solid fa-check"></i> Применить обрезку</button>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/main.js"></script>
    <script>
    function addSocialRow() {
        const container = document.getElementById('social-container');
        const row = document.createElement('div');
        row.className = 'social-row';
        row.innerHTML = `
            <input type="text" name="social_type[]" class="form-control" placeholder="Тип (VK, Telegram)">
            <input type="url" name="social_url[]" class="form-control" placeholder="Ссылка">
            <button type="button" class="remove-social" onclick="removeSocialRow(this)">✖</button>
        `;
        container.appendChild(row);
    }

    function removeSocialRow(btn) {
        const rows = document.querySelectorAll('.social-row');
        if (rows.length > 1) {
            btn.parentElement.remove();
        }
    }

    function cancelCrop() {
        if (currentFileInput) {
            resetFileInput(currentFileInput);
        }
        hideCropper();
        showNotification('Обрезка отменена', 'info');
    }
    
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
        
        if (phoneInput.value) {
            const cleanNumbers = getCleanPhoneNumber(phoneInput.value);
            const formattedValue = formatPhoneNumber(cleanNumbers);
            phoneInput.value = formattedValue;
            
            const initialValidation = validatePhoneNumber(formattedValue);
            updatePhoneValidation(initialValidation);
        }
        
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
            
            clearButton.style.display = phoneInput.value && phoneInput.value !== '+7' ? 'flex' : 'none';
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
    });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
</body>
</html>