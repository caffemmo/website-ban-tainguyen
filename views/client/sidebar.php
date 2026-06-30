<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>

<a class="sidebar_profile <?= active_sidebar_client(['profile']); ?>" href="<?= base_url('client/profile'); ?>">
    <h6><i class="fas fa-user"></i> <span><?= __('Thông tin cá nhân'); ?></span></h6>
</a>
<?php if ($CMSNT->site('sidebar_vat_invoice_status') == 1): ?>
    <a class="sidebar_profile <?= active_sidebar_client(['vat-invoice']); ?>" href="<?= base_url('client/vat-invoice'); ?>">
        <h6><i class="fa-solid fa-receipt"></i> <span><?= __('Thông tin xuất hóa đơn VAT'); ?></span></h6>
    </a>
<?php endif; ?>
<a class="sidebar_profile <?= active_sidebar_client(['security']); ?>" href="<?= base_url('client/security'); ?>">
    <h6><i class="fa-solid fa-shield-halved"></i> <span><?= __('Bảo mật'); ?></span></h6>
</a>
<a class="sidebar_profile <?= active_sidebar_client(['logs']); ?>" href="<?= base_url('?action=logs'); ?>">
    <h6><i class="fa fa-history" aria-hidden="true"></i> <span><?= __('Nhật ký hoạt động'); ?></span></h6>
</a>
<a class="sidebar_profile <?= active_sidebar_client(['transactions']); ?>" href="<?= base_url('?action=transactions'); ?>">
    <h6><i class="fa-solid fa-wallet"></i> <span><?= __('Biến động số dư'); ?></span></h6>
</a>
<a class="sidebar_profile <?= active_sidebar_client(['change-password']); ?>" href="<?= base_url('client/change-password'); ?>">
    <h6><i class="fa fa-key" aria-hidden="true"></i> <span><?= __('Thay đổi mật khẩu'); ?></span></h6>
</a>
<a class="sidebar_profile" onclick="logout()" href="javascript:void(0)">
    <h6><i class="fa-solid fa-right-from-bracket"></i> <span><?= __('Đăng xuất'); ?></span></h6>
</a>

<script type="text/javascript">
    function logout() {
        Swal.fire({
            title: '<?= __('Bạn có chắc không?'); ?>',
            text: "<?= __('Bạn sẽ bị đăng xuất khỏi tài khoản khi nhấn Đồng Ý'); ?>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<?= __('Đồng ý'); ?>',
            cancelButtonText: '<?= __('Huỷ bỏ'); ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = "<?= base_url('client/logout'); ?>";
            }
        })
    }
</script>