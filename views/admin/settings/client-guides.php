<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (isset($_POST['SaveClientGuides'])) {
    if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
        admin_msg_error('Phiên bảo mật đã hết hạn, vui lòng tải lại trang.', base_url_admin('settings&tab=client-guides'), 1200);
    }
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("This function cannot be used because this is a demo site")){window.history.back().location.reload();}</script>');
    }

    $titles = is_array($_POST['guide_title'] ?? null) ? $_POST['guide_title'] : [];
    $urls = is_array($_POST['guide_url'] ?? null) ? $_POST['guide_url'] : [];
    $enabled = is_array($_POST['guide_enabled'] ?? null) ? $_POST['guide_enabled'] : [];
    $guides = [];
    foreach ($titles as $index => $rawTitle) {
        $title = trim((string) $rawTitle);
        $url = trim((string) ($urls[$index] ?? ''));
        if ($title === '' && $url === '') {
            continue;
        }
        if ($title === '' || mb_strlen($title, 'UTF-8') > 120) {
            admin_msg_error('Tên hướng dẫn không được để trống và tối đa 120 ký tự.', base_url_admin('settings&tab=client-guides'), 1400);
        }
        if (mb_strlen($url, 'UTF-8') > 2048 || !caffemmo_client_guides_is_safe_url($url)) {
            admin_msg_error('Link tài liệu phải là URL http hoặc https hợp lệ.', base_url_admin('settings&tab=client-guides'), 1400);
        }

        $guides[] = [
            'title' => $title,
            'url' => $url,
            'enabled' => (int) ($enabled[$index] ?? 1) === 1 ? 1 : 0,
        ];
    }

    if (!caffemmo_client_guides_save($guides)) {
        admin_msg_error('Không thể lưu danh sách hướng dẫn. Vui lòng thử lại.', base_url_admin('settings&tab=client-guides'), 1400);
    }

    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => __('Cập nhật danh sách hướng dẫn khách'),
    ]);
    admin_msg_success('Đã lưu danh sách hướng dẫn.', base_url_admin('settings&tab=client-guides'), 900);
}

if (isset($_POST['SaveClientFaqs'])) {
    if (empty($_POST['csrf_token']) || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $_POST['csrf_token'])) {
        admin_msg_error('Phiên bảo mật đã hết hạn, vui lòng tải lại trang.', base_url_admin('settings&tab=client-guides'), 1200);
    }
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("This function cannot be used because this is a demo site")){window.history.back().location.reload();}</script>');
    }

    $questions = is_array($_POST['faq_question'] ?? null) ? $_POST['faq_question'] : [];
    $answers = is_array($_POST['faq_answer'] ?? null) ? $_POST['faq_answer'] : [];
    $enabled = is_array($_POST['faq_enabled'] ?? null) ? $_POST['faq_enabled'] : [];
    $faqs = [];
    foreach ($questions as $index => $rawQuestion) {
        $question = trim((string) $rawQuestion);
        $answer = trim((string) ($answers[$index] ?? ''));
        if ($question === '' && $answer === '') {
            continue;
        }
        if ($question === '' || mb_strlen($question, 'UTF-8') > 180) {
            admin_msg_error('Câu hỏi không được để trống và tối đa 180 ký tự.', base_url_admin('settings&tab=client-guides'), 1400);
        }
        if ($answer === '' || mb_strlen($answer, 'UTF-8') > 4000) {
            admin_msg_error('Câu trả lời không được để trống và tối đa 4000 ký tự.', base_url_admin('settings&tab=client-guides'), 1400);
        }

        $faqs[] = [
            'question' => $question,
            'answer' => $answer,
            'enabled' => (int) ($enabled[$index] ?? 1) === 1 ? 1 : 0,
        ];
    }

    if (!caffemmo_client_faqs_save($faqs)) {
        admin_msg_error('Không thể lưu danh sách câu hỏi thường gặp. Vui lòng thử lại.', base_url_admin('settings&tab=client-guides'), 1400);
    }

    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => __('Cập nhật danh sách câu hỏi thường gặp'),
    ]);
    admin_msg_success('Đã lưu danh sách câu hỏi thường gặp.', base_url_admin('settings&tab=client-guides'), 900);
}

$clientGuides = caffemmo_client_guides_get(true);
$clientFaqs = caffemmo_client_faqs_get(true);
?>

<style>
    .client-guides-intro { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:22px 24px; margin-bottom:20px; color:#172033; background:#f3fbfd; border:1px solid #cbe8ed; border-left:4px solid #08aebe; border-radius:10px; }
    .client-guides-intro h4 { margin:0 0 5px; font-weight:700; }
    .client-guides-intro p { margin:0; color:#64748b; }
    .client-guides-note { display:flex; gap:10px; padding:12px 14px; border:1px solid #dbe7ed; border-radius:8px; color:#36566b; background:#f8fcfd; font-size:12px; line-height:1.55; }
    .client-guides-note i { margin-top:2px; color:#0b95a0; }
    .client-guide-row { display:grid; grid-template-columns:minmax(0, 1fr) minmax(0, 1.3fr) 130px 42px; gap:12px; align-items:end; padding:16px; margin-bottom:12px; border:1px solid #e3ebf0; border-radius:9px; background:#fff; }
    .client-guide-row:last-child { margin-bottom:0; }
    .client-guide-row .form-label { font-size:12px; font-weight:700; color:#334155; }
    .client-guide-remove { width:38px; height:38px; padding:0; }
    .client-guides-empty { padding:28px 16px; border:1px dashed #cbd5e1; border-radius:9px; color:#64748b; text-align:center; }
    .client-faq-section { padding-top:24px; margin-top:28px; border-top:1px solid #e3ebf0; }
    .client-faq-heading { margin-bottom:16px; }
    .client-faq-heading h5 { margin:0 0 5px; color:#172033; font-weight:700; }
    .client-faq-heading p { margin:0; color:#64748b; font-size:12px; }
    .client-faq-row { display:grid; grid-template-columns:minmax(0, 1fr) minmax(0, 1.3fr) 130px 42px; gap:12px; align-items:end; padding:16px; margin-bottom:12px; border:1px solid #e3ebf0; border-radius:9px; background:#fff; }
    .client-faq-row textarea { min-height:88px; resize:vertical; }
    .client-faq-row .form-label { font-size:12px; font-weight:700; color:#334155; }
    .client-faq-remove { width:38px; height:38px; padding:0; }
    .client-faq-empty { padding:28px 16px; border:1px dashed #cbd5e1; border-radius:9px; color:#64748b; text-align:center; }
    @media (max-width:767.98px) { .client-guides-intro { align-items:flex-start; flex-direction:column; padding:18px; } .client-guide-row, .client-faq-row { grid-template-columns:1fr; } .client-guide-remove, .client-faq-remove { width:100%; } }
</style>

<div class="tab-pane text-muted show active" id="client-guides-settings" role="tabpanel">
    <div class="client-guides-intro">
        <div>
            <h4><i class="fa-solid fa-book-open me-2" aria-hidden="true"></i><?= __('Hướng dẫn khách'); ?></h4>
            <p><?= __('Quản lý hướng dẫn và câu hỏi thường gặp hiển thị trên trang Mua Proxy.'); ?></p>
        </div>
        <span class="badge bg-info-transparent text-info"><i class="fa-solid fa-display me-1" aria-hidden="true"></i><?= __('Hiển thị trực tiếp cho khách'); ?></span>
    </div>

    <form action="" method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="client-guides-note mb-3">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span><?= __('Thêm tên hướng dẫn và link Google Docs hoặc tài liệu HTTPS. Có thể tắt tạm từng nút; để xóa một nút, bấm Xóa dòng rồi lưu.'); ?></span>
        </div>

        <div id="client-guides-list">
            <?php foreach ($clientGuides as $index => $guide): ?>
                <div class="client-guide-row" data-guide-row>
                    <div>
                        <label class="form-label" for="guide-title-<?= (int) $index; ?>"><?= __('Tên nút hướng dẫn'); ?></label>
                        <input class="form-control" id="guide-title-<?= (int) $index; ?>" type="text" name="guide_title[]" value="<?= htmlspecialchars($guide['title'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="120" required>
                    </div>
                    <div>
                        <label class="form-label"><?= __('Link tài liệu'); ?></label>
                        <input class="form-control" type="url" name="guide_url[]" value="<?= htmlspecialchars($guide['url'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="2048" placeholder="https://docs.google.com/..." required>
                    </div>
                    <div>
                        <label class="form-label"><?= __('Trạng thái'); ?></label>
                        <select class="form-select" name="guide_enabled[]">
                            <option value="1" <?= (int) $guide['enabled'] === 1 ? 'selected' : ''; ?>><?= __('Đang bật'); ?></option>
                            <option value="0" <?= (int) $guide['enabled'] !== 1 ? 'selected' : ''; ?>><?= __('Đang tắt'); ?></option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline-danger client-guide-remove" data-remove-guide aria-label="<?= __('Xóa dòng'); ?>" title="<?= __('Xóa dòng'); ?>"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="client-guides-empty" class="client-guides-empty" <?= !empty($clientGuides) ? 'hidden' : ''; ?>>
            <i class="fa-solid fa-book-open fa-2x mb-2"></i>
            <div><?= __('Chưa có hướng dẫn nào. Hãy thêm nút đầu tiên.'); ?></div>
        </div>

        <div class="d-flex flex-wrap justify-content-between gap-2 mt-3">
            <button type="button" class="btn btn-outline-primary" id="add-client-guide"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i><?= __('Thêm hướng dẫn'); ?></button>
            <button type="submit" name="SaveClientGuides" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i><?= __('Lưu danh sách'); ?></button>
        </div>
    </form>
</div>

<section class="client-faq-section">
    <div class="client-faq-heading">
        <h5><i class="fa-solid fa-circle-question me-2" aria-hidden="true"></i><?= __('Câu hỏi thường gặp'); ?></h5>
        <p><?= __('Thêm câu hỏi và câu trả lời hiển thị trực tiếp cho khách, không cần link tài liệu.'); ?></p>
    </div>

    <form action="" method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="client-guides-note mb-3">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span><?= __('Mỗi câu hỏi có thể bật hoặc tắt; để xóa, bấm Xóa dòng rồi lưu danh sách FAQ.'); ?></span>
        </div>

        <div id="client-faq-list">
            <?php foreach ($clientFaqs as $index => $faq): ?>
                <div class="client-faq-row" data-faq-row>
                    <div>
                        <label class="form-label" for="faq-question-<?= (int) $index; ?>"><?= __('Câu hỏi'); ?></label>
                        <input class="form-control" id="faq-question-<?= (int) $index; ?>" type="text" name="faq_question[]" value="<?= htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="180" required>
                    </div>
                    <div>
                        <label class="form-label" for="faq-answer-<?= (int) $index; ?>"><?= __('Câu trả lời'); ?></label>
                        <textarea class="form-control" id="faq-answer-<?= (int) $index; ?>" name="faq_answer[]" maxlength="4000" required><?= htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div>
                        <label class="form-label"><?= __('Trạng thái'); ?></label>
                        <select class="form-select" name="faq_enabled[]">
                            <option value="1" <?= (int) $faq['enabled'] === 1 ? 'selected' : ''; ?>><?= __('Đang bật'); ?></option>
                            <option value="0" <?= (int) $faq['enabled'] !== 1 ? 'selected' : ''; ?>><?= __('Đang tắt'); ?></option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline-danger client-faq-remove" data-remove-faq aria-label="<?= __('Xóa dòng'); ?>" title="<?= __('Xóa dòng'); ?>"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="client-faq-empty" class="client-faq-empty" <?= !empty($clientFaqs) ? 'hidden' : ''; ?>>
            <i class="fa-solid fa-circle-question fa-2x mb-2"></i>
            <div><?= __('Chưa có câu hỏi thường gặp nào. Hãy thêm câu hỏi đầu tiên.'); ?></div>
        </div>

        <div class="d-flex flex-wrap justify-content-between gap-2 mt-3">
            <button type="button" class="btn btn-outline-primary" id="add-client-faq"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i><?= __('Thêm câu hỏi'); ?></button>
            <button type="submit" name="SaveClientFaqs" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i><?= __('Lưu danh sách FAQ'); ?></button>
        </div>
    </form>
</section>

<template id="client-guide-template">
    <div class="client-guide-row" data-guide-row>
        <div>
            <label class="form-label"><?= __('Tên nút hướng dẫn'); ?></label>
            <input class="form-control" type="text" name="guide_title[]" maxlength="120" required>
        </div>
        <div>
            <label class="form-label"><?= __('Link tài liệu'); ?></label>
            <input class="form-control" type="url" name="guide_url[]" maxlength="2048" placeholder="https://docs.google.com/..." required>
        </div>
        <div>
            <label class="form-label"><?= __('Trạng thái'); ?></label>
            <select class="form-select" name="guide_enabled[]">
                <option value="1" selected><?= __('Đang bật'); ?></option>
                <option value="0"><?= __('Đang tắt'); ?></option>
            </select>
        </div>
        <button type="button" class="btn btn-outline-danger client-guide-remove" data-remove-guide aria-label="<?= __('Xóa dòng'); ?>" title="<?= __('Xóa dòng'); ?>"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
    </div>
</template>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const list = document.getElementById('client-guides-list');
        const empty = document.getElementById('client-guides-empty');
        const template = document.getElementById('client-guide-template');
        const addButton = document.getElementById('add-client-guide');
        if (!list || !empty || !template || !addButton) return;

        const updateEmptyState = function() {
            empty.hidden = list.querySelector('[data-guide-row]') !== null;
        };

        list.addEventListener('click', function(event) {
            const button = event.target.closest('[data-remove-guide]');
            if (!button) return;
            const row = button.closest('[data-guide-row]');
            if (row) row.remove();
            updateEmptyState();
        });

        addButton.addEventListener('click', function() {
            list.appendChild(template.content.cloneNode(true));
            updateEmptyState();
            const newestInput = list.querySelector('[data-guide-row]:last-child input[name="guide_title[]"]');
            if (newestInput) newestInput.focus();
        });
    });
</script>

<template id="client-faq-template">
    <div class="client-faq-row" data-faq-row>
        <div>
            <label class="form-label"><?= __('Câu hỏi'); ?></label>
            <input class="form-control" type="text" name="faq_question[]" maxlength="180" required>
        </div>
        <div>
            <label class="form-label"><?= __('Câu trả lời'); ?></label>
            <textarea class="form-control" name="faq_answer[]" maxlength="4000" required></textarea>
        </div>
        <div>
            <label class="form-label"><?= __('Trạng thái'); ?></label>
            <select class="form-select" name="faq_enabled[]">
                <option value="1" selected><?= __('Đang bật'); ?></option>
                <option value="0"><?= __('Đang tắt'); ?></option>
            </select>
        </div>
        <button type="button" class="btn btn-outline-danger client-faq-remove" data-remove-faq aria-label="<?= __('Xóa dòng'); ?>" title="<?= __('Xóa dòng'); ?>"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
    </div>
</template>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const list = document.getElementById('client-faq-list');
        const empty = document.getElementById('client-faq-empty');
        const template = document.getElementById('client-faq-template');
        const addButton = document.getElementById('add-client-faq');
        if (!list || !empty || !template || !addButton) return;

        const updateEmptyState = function() {
            empty.hidden = list.querySelector('[data-faq-row]') !== null;
        };

        list.addEventListener('click', function(event) {
            const button = event.target.closest('[data-remove-faq]');
            if (!button) return;
            const row = button.closest('[data-faq-row]');
            if (row) row.remove();
            updateEmptyState();
        });

        addButton.addEventListener('click', function() {
            list.appendChild(template.content.cloneNode(true));
            updateEmptyState();
            const newestInput = list.querySelector('[data-faq-row]:last-child input[name="faq_question[]"]');
            if (newestInput) newestInput.focus();
        });
    });
</script>
