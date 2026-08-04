(function () {
    'use strict';

    var app = document.querySelector('[data-up-app]');
    if (!app) return;

    var endpoint = app.getAttribute('data-endpoint') || '';
    var token = app.getAttribute('data-token') || '';
    var loginUrl = app.getAttribute('data-login-url') || ((window.baseUrl || '/') + 'client/login');
    var service = app.getAttribute('data-service') || '';
    var configured = app.getAttribute('data-configured') === '1';
    var historyUrl = app.getAttribute('data-history-url') || '';
    var form = app.querySelector('[data-up-form]');
    var image = form ? form.querySelector('[name="image"]') : null;
    var submit = app.querySelector('[data-up-submit]');
    var notice = app.querySelector('[data-up-notice]');
    var result = app.querySelector('[data-up-result]');
    var imageMeta = app.querySelector('[data-up-image-meta]');
    var imageTitle = app.querySelector('[data-up-image-title]');
    var imagePreview = app.querySelector('[data-up-image-preview]');
    var imagePreviewImage = app.querySelector('[data-up-image-preview-image]');
    var imagePreviewName = app.querySelector('[data-up-image-preview-name]');
    var imagePreviewInfo = app.querySelector('[data-up-image-preview-info]');
    var imageRemove = app.querySelector('[data-up-image-remove]');
    var imagePreviewUrl = '';
    var imageValidationToken = 0;

    function redirectToLogin() {
        window.location.href = loginUrl;
    }

    function setMessage(element, message, type) {
        if (!element) return;
        element.hidden = !message;
        element.setAttribute('data-type', type || 'info');
        element.textContent = message || '';
    }

    function setLoading(loading) {
        if (!submit) return;
        if (loading) {
            submit.disabled = true;
            submit.dataset.originalText = submit.innerHTML;
            submit.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i> Đang xử lý';
        } else {
            submit.disabled = false;
            if (submit.dataset.originalText) {
                submit.innerHTML = submit.dataset.originalText;
                delete submit.dataset.originalText;
            }
        }
    }

    function showResult(data) {
        if (!result) return;
        var html = '<strong>Yêu cầu đã hoàn tất</strong>'
            + '<span>Chi phí dịch vụ: ' + String(data.charged_label || '') + '</span>';
        if (data.link) {
            html += '<a href="' + String(data.link).replace(/"/g, '&quot;') + '" target="_blank" rel="noopener noreferrer">Mở link xác minh <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>';
        }
        if (historyUrl) {
            html += '<a href="' + historyUrl + '">Xem lịch sử yêu cầu <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>';
        }
        result.innerHTML = html;
        result.hidden = false;
    }

    function imageDimensions(file) {
        return new Promise(function (resolve) {
            var url = URL.createObjectURL(file);
            var image = new Image();
            image.onload = function () {
                URL.revokeObjectURL(url);
                resolve({ width: image.naturalWidth, height: image.naturalHeight });
            };
            image.onerror = function () {
                URL.revokeObjectURL(url);
                resolve(null);
            };
            image.src = url;
        });
    }

    function setImageState(state, message) {
        if (!imageMeta) return;
        imageMeta.dataset.state = state;
        imageMeta.textContent = message || '';
    }

    function clearImagePreview() {
        if (imagePreviewUrl) {
            URL.revokeObjectURL(imagePreviewUrl);
            imagePreviewUrl = '';
        }
        if (imagePreviewImage) {
            imagePreviewImage.removeAttribute('src');
            imagePreviewImage.alt = 'Ảnh giấy tờ đã chọn';
        }
        if (imagePreviewName) imagePreviewName.textContent = '';
        if (imagePreviewInfo) imagePreviewInfo.textContent = '';
        if (imagePreview) imagePreview.hidden = true;
    }

    function showImagePreview(file) {
        if (!imagePreview || !imagePreviewImage) return;
        clearImagePreview();
        imagePreviewUrl = URL.createObjectURL(file);
        imagePreviewImage.src = imagePreviewUrl;
        imagePreviewImage.alt = 'Ảnh giấy tờ đã chọn: ' + file.name;
        if (imagePreviewName) imagePreviewName.textContent = file.name;
        if (imagePreviewInfo) imagePreviewInfo.textContent = 'Đang đọc ảnh...';
        imagePreview.hidden = false;
    }

    function setImagePreviewInfo(message) {
        if (imagePreviewInfo) imagePreviewInfo.textContent = message || '';
    }

    function formatFileSize(bytes) {
        if (bytes < 1024 * 1024) return Math.max(1, Math.round(bytes / 1024)) + 'KB';
        return (bytes / (1024 * 1024)).toFixed(2).replace(/\.00$/, '') + 'MB';
    }

    function hasAllowedImageType(file) {
        var allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];
        if (allowedTypes.indexOf(file.type) !== -1) return true;
        var fileName = String(file.name || '').toLowerCase();
        return /\.(png|jpe?g|webp)$/.test(fileName);
    }

    async function validateImageFile(file) {
        if (!file) {
            return { error: 'Vui lòng chọn ảnh giấy tờ xác minh.' };
        }
        if (!hasAllowedImageType(file)) {
            return { error: 'Chỉ nhận ảnh PNG, JPG hoặc WEBP.' };
        }
        if (file.size > 10 * 1024 * 1024) {
            return { error: 'Ảnh không được vượt quá 10MB.' };
        }
        var dimensions = await imageDimensions(file);
        if (!dimensions) {
            return { error: 'Không thể đọc ảnh, vui lòng chọn file PNG, JPG hoặc WEBP hợp lệ.' };
        }
        if (dimensions.width < 1500 || dimensions.height < 1000) {
            return { error: 'Ảnh phải có kích thước tối thiểu 1500×1000px.' };
        }
        return { error: '', dimensions: dimensions };
    }

    async function validateSelectedImage(file) {
        setImageState('checking', 'Đang kiểm tra ảnh...');
        if (imageTitle) imageTitle.textContent = 'Đang kiểm tra ảnh...';
        var validation = await validateImageFile(file);
        if (validation.error) {
            setImageState('error', validation.error);
            setImagePreviewInfo(validation.error);
            if (imageTitle) imageTitle.textContent = 'Ảnh chưa hợp lệ';
            return validation.error;
        }
        setImageState('valid', 'Ảnh hợp lệ · ' + validation.dimensions.width + '×' + validation.dimensions.height + 'px · ' + formatFileSize(file.size));
        setImagePreviewInfo(validation.dimensions.width + '×' + validation.dimensions.height + 'px · ' + formatFileSize(file.size));
        if (imageTitle) imageTitle.textContent = 'Ảnh đã sẵn sàng';
        return '';
    }

    async function submitForm() {
        if (!configured || !form || !submit) return;
        if (!token) {
            redirectToLogin();
            return;
        }
        var cookie = form.querySelector('[name="cookie"]');
        if (!cookie || cookie.value.trim().length < 10) {
            setMessage(notice, 'Vui lòng nhập cookie đầy đủ trước khi gửi.', 'error');
            cookie && cookie.focus();
            return;
        }
        if ((service === 'up-fb' || service === 'up-ig') && (!image || !image.files.length)) {
            setMessage(notice, 'Vui lòng chọn ảnh giấy tờ xác minh.', 'error');
            return;
        }
        var imageError = image && image.files.length ? await validateSelectedImage(image.files[0]) : '';
        if (imageError) {
            setMessage(notice, imageError, 'error');
            return;
        }
        var body = new FormData(form);
        body.append('action', 'submit');
        body.append('service', service);
        body.append('token', token);
        setLoading(true);
        setMessage(notice, 'Đang tiếp nhận yêu cầu...', 'info');
        if (result) result.hidden = true;

        try {
            var response = await fetch(endpoint, { method: 'POST', credentials: 'same-origin', body: body, headers: { 'Accept': 'application/json' } });
            var data = await response.json().catch(function () { return { success: false, message: 'Phản hồi máy chủ không hợp lệ.' }; });
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Không thể hoàn tất yêu cầu.');
            }
            setMessage(notice, data.message || 'Yêu cầu đã được xử lý thành công.', 'success');
            showResult(data.data || {});
            form.reset();
            clearImagePreview();
            setImageState('empty', 'Chưa chọn ảnh');
            if (imageTitle) imageTitle.textContent = 'Chọn ảnh giấy tờ';
        } catch (error) {
            setMessage(notice, error.message || 'Không thể hoàn tất yêu cầu.', 'error');
        } finally {
            setLoading(false);
        }
    }

    if (!configured) {
        if (submit) submit.disabled = true;
        return;
    }
    if (notice) notice.hidden = false;
    if (image) image.addEventListener('change', async function () {
        var currentToken = ++imageValidationToken;
        var file = image.files && image.files[0];
        if (!file) {
            clearImagePreview();
            setImageState('empty', 'Chưa chọn ảnh');
            if (imageTitle) imageTitle.textContent = 'Chọn ảnh giấy tờ';
            return;
        }
        showImagePreview(file);
        var error = await validateSelectedImage(file);
        if (currentToken !== imageValidationToken) return;
        if (error) {
            image.value = '';
            clearImagePreview();
            setMessage(notice, error, 'error');
        } else {
            setMessage(notice, 'Ảnh đã đạt yêu cầu, bạn có thể gửi yêu cầu.', 'success');
        }
    });
    if (imageRemove) imageRemove.addEventListener('click', function () {
        imageValidationToken += 1;
        if (image) image.value = '';
        clearImagePreview();
        setImageState('empty', 'Chưa chọn ảnh');
        if (imageTitle) imageTitle.textContent = 'Chọn ảnh giấy tờ';
        setMessage(notice, '', 'info');
    });
    if (form) form.addEventListener('submit', function (event) {
        event.preventDefault();
        submitForm();
    });
}());
