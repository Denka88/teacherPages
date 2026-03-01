let cropper = null;
let currentFileInput = null;
let currentPreview = null;
let currentUploadIcon = null;
let currentUploadText = null;

function addSocialRow() {
    const container = document.getElementById('social-container');
    const row = document.createElement('div');
    row.className = 'social-row';
    row.innerHTML = `
        <input type="text" name="social_type[]" placeholder="Тип (VK, Telegram)" class="form-control">
        <input type="url" name="social_url[]" placeholder="Ссылка" class="form-control">
        <button type="button" class="remove-social" onclick="removeSocialRow(this)">✖</button>
    `;
    container.appendChild(row);
}

function removeSocialRow(btn) {
    const rows = document.querySelectorAll('.social-row');
    if (rows.length > 1) {
        btn.parentElement.remove();
    } else {
        showNotification('Должна быть хотя бы одна социальная сеть', 'warning');
    }
}

function showCropperModal(btn) {
    const container = btn.closest('.photo-preview-container');
    const preview = container.querySelector('.photo-preview');
    const fileInput = container.closest('.photo-upload-area').querySelector('input[type="file"]');
    const uploadIcon = container.closest('.photo-upload-area').querySelector('.photo-upload-icon');
    const uploadText = container.closest('.photo-upload-area').querySelector('.photo-upload-text');
    
    if (preview && preview.src) {
        showCropper(preview.src, fileInput, preview, uploadIcon, uploadText);
    }
}

function removeCurrentPhoto() {
    const checkbox = document.getElementById('deletePhotoCheckbox');
    if (checkbox) {
        checkbox.checked = true;
        const currentPhotoContainer = checkbox.closest('div').previousElementSibling;
        if (currentPhotoContainer) {
            currentPhotoContainer.style.display = 'none';
        }
    }
    showNotification('Текущее фото будет удалено после сохранения', 'info');
}

function removePhoto(btn) {
    const container = btn.closest('.photo-preview-container');
    if (!container) return;
    
    const fileInput = container.closest('.photo-upload-area').querySelector('input[type="file"]');
    const preview = container.querySelector('.photo-preview');
    const uploadIcon = container.closest('.photo-upload-area').querySelector('.photo-upload-icon');
    const uploadText = container.closest('.photo-upload-area').querySelector('.photo-upload-text');
    const previewActions = container.querySelector('.photo-preview-actions');
    
    if (fileInput) {
        fileInput.value = '';
    }
    
    if (preview) {
        preview.style.display = 'none';
        preview.src = '';
    }
    
    if (uploadIcon) {
        uploadIcon.style.display = 'block';
    }
    
    if (uploadText) {
        uploadText.style.display = 'block';
    }
    
    if (previewActions) {
        previewActions.style.display = 'none';
    }
    
    showNotification('Фотография удалена', 'info');
}

function cancelCrop() {
    if (currentFileInput) {
        resetFileInput(currentFileInput);
    }
    hideCropper();
    showNotification('Обрезка отменена', 'info');
}

function showCropper(imageSrc, fileInput, preview, uploadIcon, uploadText) {
    currentFileInput = fileInput;
    currentPreview = preview;
    currentUploadIcon = uploadIcon;
    currentUploadText = uploadText;
    
    const modal = document.getElementById('cropperModal');
    const image = document.getElementById('cropperImage');
    
    if (!modal || !image) {
        console.error('Cropper modal or image not found');
        return;
    }
    
    document.body.classList.add('modal-open');
    image.src = imageSrc;
    modal.classList.add('active');
    
    if (cropper) {
        cropper.destroy();
    }
    
    setTimeout(() => {
        try {
            cropper = new Cropper(image, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 0.8,
                responsive: true,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                zoomOnWheel: false,
                minContainerWidth: 200,
                minContainerHeight: 200,
                minCanvasWidth: 200,
                minCanvasHeight: 200,
                dragMode: 'move',
                checkCrossOrigin: false,
                checkOrientation: true,
                modal: true,
                background: false
            });
        } catch (error) {
            console.error('Cropper initialization error:', error);
            hideCropper();
            showNotification('Ошибка инициализации редактора фото', 'error');
        }
    }, 100);
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            cancelCrop();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            cancelCrop();
        }
    });
}

function hideCropper() {
    const modal = document.getElementById('cropperModal');
    if (modal) {
        modal.classList.remove('active');
    }
    document.body.classList.remove('modal-open');
    
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    
    currentFileInput = null;
    currentPreview = null;
    currentUploadIcon = null;
    currentUploadText = null;
}

function rotateCropper(degrees) {
    if (cropper) {
        cropper.rotate(degrees);
    }
}

function applyCrop() {
    if (!cropper) {
        showNotification('Редактор фото не инициализирован', 'error');
        return;
    }
    
    const applyBtn = document.querySelector('#cropperModal .btn-primary');
    if (!applyBtn) return;
    
    const originalText = applyBtn.innerHTML;
    applyBtn.innerHTML = '<div class="loading"></div>';
    applyBtn.disabled = true;
    
    const isMobile = window.innerWidth <= 768;
    const canvasSize = isMobile ? 400 : 500;
    
    setTimeout(() => {
        try {
            const canvas = cropper.getCroppedCanvas({
                width: canvasSize,
                height: canvasSize,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'medium',
                fillColor: '#fff'
            });
            
            if (!canvas) {
                throw new Error('Canvas creation failed');
            }
            
            const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.8);
            updatePreview(croppedDataUrl, currentPreview, currentUploadIcon, currentUploadText);
            
            const blob = dataURLtoBlob(croppedDataUrl);
            if (!blob) {
                throw new Error('Blob conversion failed');
            }
            
            const fileName = `cropped_${Date.now()}.jpg`;
            const file = new File([blob], fileName, { type: 'image/jpeg' });
            
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            if (currentFileInput) {
                currentFileInput.files = dataTransfer.files;
            }
            
            const previewActions = currentPreview?.closest('.photo-preview-container')?.querySelector('.photo-preview-actions');
            if (previewActions) {
                previewActions.style.display = 'flex';
            }
            
            hideCropper();
            showNotification('Фотография успешно обрезана!', 'success');
            
        } catch (error) {
            console.error('Cropping error:', error);
            showNotification('Ошибка при обрезке изображения', 'error');
        } finally {
            applyBtn.innerHTML = originalText;
            applyBtn.disabled = false;
        }
    }, 500);
}

function updatePreview(dataUrl, preview, uploadIcon, uploadText) {
    if (preview) {
        preview.src = dataUrl;
        preview.style.display = 'block';
    }
    
    if (uploadIcon) {
        uploadIcon.style.display = 'none';
    }
    
    if (uploadText) {
        uploadText.style.display = 'none';
    }
}

function dataURLtoBlob(dataUrl) {
    try {
        const arr = dataUrl.split(',');
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        
        return new Blob([u8arr], { type: mime });
    } catch (error) {
        console.error('Error converting dataURL to blob:', error);
        return null;
    }
}

function resetFileInput(fileInput) {
    if (!fileInput) return;
    
    fileInput.value = '';
    
    const area = fileInput.closest('.photo-upload-area');
    if (!area) return;
    
    const preview = area.querySelector('.photo-preview');
    const uploadIcon = area.querySelector('.photo-upload-icon');
    const uploadText = area.querySelector('.photo-upload-text');
    const previewActions = area.querySelector('.photo-preview-actions');
    
    if (preview) {
        preview.style.display = 'none';
        preview.src = '';
    }
    
    if (previewActions) {
        previewActions.style.display = 'none';
    }
    
    if (uploadIcon) {
        uploadIcon.style.display = 'block';
    }
    
    if (uploadText) {
        uploadText.style.display = 'block';
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        max-width: 300px;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

function initPhotoUpload() {
    const photoAreas = document.querySelectorAll('.photo-upload-area');
    
    photoAreas.forEach(area => {
        const fileInput = area.querySelector('input[type="file"]');
        const preview = area.querySelector('.photo-preview');
        const uploadIcon = area.querySelector('.photo-upload-icon');
        const uploadText = area.querySelector('.photo-upload-text');
        const loadingOverlay = area.querySelector('.loading-overlay');
        
        if (!fileInput) return;
        
        area.addEventListener('click', (e) => {
            if (!e.target.closest('.photo-preview-actions')) {
                fileInput.click();
            }
        });
        
        area.addEventListener('dragover', (e) => {
            e.preventDefault();
            area.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', (e) => {
            e.preventDefault();
            area.classList.remove('dragover');
        });
        
        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('image/')) {
                handleFileSelect(files[0], fileInput, preview, uploadIcon, uploadText, loadingOverlay);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0], fileInput, preview, uploadIcon, uploadText, loadingOverlay);
            }
        });
        
        area.style.cursor = 'pointer';
        fileInput.style.cursor = 'pointer';
    });
}

function handleFileSelect(file, fileInput, preview, uploadIcon, uploadText, loadingOverlay) {
    if (!file.type.match('image.*')) {
        showNotification('Пожалуйста, выберите изображение', 'error');
        return;
    }
    
    const maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
        showNotification(`Файл слишком большой. Максимальный размер: ${maxSize / 1024 / 1024}MB`, 'error');
        return;
    }
    
    if (loadingOverlay) loadingOverlay.style.display = 'flex';
    
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const shouldCrop = fileInput.hasAttribute('data-crop');
        
        if (shouldCrop) {
            if (window.innerWidth <= 768) {
                if (confirm('Хотите обрезать фотографию для лучшего отображения?')) {
                    showCropper(e.target.result, fileInput, preview, uploadIcon, uploadText);
                } else {
                    updatePreview(e.target.result, preview, uploadIcon, uploadText);
                    const blob = dataURLtoBlob(e.target.result);
                    if (blob) {
                        const newFile = new File([blob], file.name, { type: file.type });
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(newFile);
                        fileInput.files = dataTransfer.files;
                    }
                    
                    const previewActions = preview?.closest('.photo-preview-container')?.querySelector('.photo-preview-actions');
                    if (previewActions) {
                        previewActions.style.display = 'flex';
                    }
                }
            } else {
                showCropper(e.target.result, fileInput, preview, uploadIcon, uploadText);
            }
        } else {
            updatePreview(e.target.result, preview, uploadIcon, uploadText);
            const previewActions = preview?.closest('.photo-preview-container')?.querySelector('.photo-preview-actions');
            if (previewActions) {
                previewActions.style.display = 'flex';
            }
        }
        
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    };
    
    reader.onerror = function() {
        showNotification('Ошибка при чтении файла', 'error');
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    };
    
    reader.readAsDataURL(file);
}

function initAdminSearch() {
    const searchInputs = document.querySelectorAll('.search-input[data-live-search]');
    
    searchInputs.forEach(input => {
        const table = input.closest('.card').querySelector('table');
        if (!table) return;
        
        input.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    function showTooltip(e) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = this.getAttribute('data-tooltip');
        tooltip.style.cssText = `
            position: fixed;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
        `;
        document.body.appendChild(tooltip);
        
        const rect = this.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    }
    
    function hideTooltip() {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }
    
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', showTooltip);
        tooltip.addEventListener('mouseleave', hideTooltip);
    });

    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!valid) {
                e.preventDefault();
                showNotification('Пожалуйста, заполните все обязательные поля', 'error');
            }
        });
    });

    const searchInput = document.querySelector('.search-input');
    if (searchInput && !searchInput.hasAttribute('data-live-search')) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.teacher-card');
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    const deleteLinks = document.querySelectorAll('a[onclick*="confirm"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm') || 'Вы уверены?')) {
                e.preventDefault();
            }
        });
    });
    
    initPhotoUpload();
    initAdminSearch();
});

function refreshCaptcha(formId) {
    const captchaImage = document.getElementById(formId + '_image');
    if (captchaImage) {
        captchaImage.src = 'captcha.php?v=' + Date.now();
    }
}