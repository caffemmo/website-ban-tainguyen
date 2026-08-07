<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$clientResourceService = caffemmo_client_resource_service_key($clientResourceService ?? 'proxy-buy');
$clientResourceContainerClass = trim((string) ($clientResourceContainerClass ?? ''));
$clientResourceGuides = caffemmo_client_guides_get(false, $clientResourceService);
$clientResourceFaqs = caffemmo_client_faqs_get(false, $clientResourceService);
$clientResourceId = preg_replace('/[^a-z0-9-]+/i', '-', $clientResourceService);
?>

<div class="client-resource-grid<?= $clientResourceContainerClass !== '' ? ' ' . htmlspecialchars($clientResourceContainerClass, ENT_QUOTES, 'UTF-8') : ''; ?>" data-client-resources="<?= htmlspecialchars($clientResourceService, ENT_QUOTES, 'UTF-8'); ?>">
    <section class="client-resource-card">
        <button class="client-resource-toggle" type="button" data-client-resource-toggle aria-expanded="false" aria-controls="client-guides-panel-<?= htmlspecialchars($clientResourceId, ENT_QUOTES, 'UTF-8'); ?>">
            <span class="client-resource-toggle-icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
            <span class="client-resource-toggle-copy"><strong><?= __('Hướng dẫn sử dụng'); ?></strong></span>
            <i class="fa-solid fa-arrow-circle-down client-resource-toggle-arrow" aria-hidden="true"></i>
        </button>
        <div class="client-resource-content" id="client-guides-panel-<?= htmlspecialchars($clientResourceId, ENT_QUOTES, 'UTF-8'); ?>" hidden>
            <?php if (!empty($clientResourceGuides)): ?>
                <p class="client-resource-description"><?= __('Chọn tài liệu phù hợp để xem hướng dẫn cài đặt và sử dụng.'); ?></p>
                <div class="client-resource-link-grid">
                    <?php foreach ($clientResourceGuides as $guide): ?>
                        <a class="client-resource-link" href="<?= htmlspecialchars($guide['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                            <span class="client-resource-link-icon"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                            <span class="client-resource-link-copy">
                                <strong><?= htmlspecialchars($guide['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?= __('Mở tài liệu'); ?></small>
                            </span>
                            <i class="fa-solid fa-chevron-right client-resource-link-arrow" aria-hidden="true"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="client-resource-empty"><?= __('Chưa có tài liệu hướng dẫn cho dịch vụ này.'); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="client-resource-card">
        <button class="client-resource-toggle" type="button" data-client-resource-toggle aria-expanded="false" aria-controls="client-faq-panel-<?= htmlspecialchars($clientResourceId, ENT_QUOTES, 'UTF-8'); ?>">
            <span class="client-resource-toggle-icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
            <span class="client-resource-toggle-copy"><strong><?= __('Các câu hỏi thường gặp'); ?></strong></span>
            <i class="fa-solid fa-arrow-circle-down client-resource-toggle-arrow" aria-hidden="true"></i>
        </button>
        <div class="client-resource-content client-resource-faq-content" id="client-faq-panel-<?= htmlspecialchars($clientResourceId, ENT_QUOTES, 'UTF-8'); ?>" hidden>
            <?php if (!empty($clientResourceFaqs)): ?>
                <div class="client-resource-faq-list">
                    <?php foreach ($clientResourceFaqs as $faq): ?>
                        <article class="client-resource-faq-item">
                            <h3><?= htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?= nl2br(htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8'), false); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="client-resource-empty"><?= __('Chưa có câu hỏi thường gặp cho dịch vụ này.'); ?></p>
            <?php endif; ?>
        </div>
    </section>
</div>
