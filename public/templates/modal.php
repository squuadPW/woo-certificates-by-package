<div id="modal-woorceti-confirm-delete-course" class="modal modal-woorceti" style="display: none;">
    <div class="modal-content">
        <div class="modal-header p-5">
            <h3><?php echo __('Confirm Deletion', 'woocertificatespackage'); ?></h3>
            <span class="modal-close-woorceti" id="close-delete-course">
                <span class="dashicons dashicons-no-alt"></span>
            </span>
        </div>
        <div class="modal-body">
            <div class="content">
                <p>
                    <?php echo __('Are you sure you want to delete this course? This action cannot be undone.', 'woocertificatespackage'); ?>
                </p>
            </div>
            <div class="content-footer">
                <a href="#"
                    class="woocommerce-button button button button-danger"
                    id="woorceti-confirm-delete-course"
                >
                    <?php echo __('Yes, Delete', 'woocertificatespackage'); ?>
                </a>
                <button type="button" class="woocommerce-button button button-cancel" id="btn-cancel-woorceti-modal">
                    <?php echo __('Cancel', 'woocertificatespackage'); ?>
                </button>
            </div>
        </div>
    </div>
</div>