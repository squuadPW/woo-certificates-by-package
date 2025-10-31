<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="edusystem-invoices-header">
    <h2><?php echo __('Monthly invoice', 'edusystem'); ?></h2>
</div>

<div class="edusystem-row">
    <?php foreach ($cards as $card) : ?>
        <div class="edusystem-col-md-3" id="content-<?php echo esc_attr($card['id']); ?>" style="<?= $card['visible'] === false ? 'display: none;' : '' ?>">
            <div class="edusystem-card">
                <div class="content-title">
                    <span class="dashicons <?php echo esc_attr($card['icon']); ?>"></span>
                    <?php echo esc_html($card['title']); ?>
                </div>
                <div class="content-detail">
                    <strong id="<?php echo esc_attr($card['id']); ?>"><?php echo $card['value']; ?></strong>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="invoice-filters">
    <div class="edusystem-row edusystem-w100-jcontent-c">
        <div class="edusystem-col-md-4">
            <select id="alliance-typeFilter" name="typeFilter" autocomplete="off" class="">
                <?php foreach ($optionsFilter as $option) : ?>
                    <option value="<?= esc_attr($option['value']) ?>" <?= $option['selected'] ? 'selected' : '' ?>>
                    <?= esc_html(ucfirst($option['label'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="edusystem-content-custom" class="edusystem-col-md-4" style="display:none;">
            <input
                type="text"
                id="flatpickr-filter-custom"
                class="formdata"
                value="<?= esc_attr($start_date) ?>"
            >
        </div>
        <div class="edusystem-col-md-4">
            <button type="button" id="update_data" class="button button-edusystem-alliance edusystem-primary">
                <?= __('Update data', 'edusystem'); ?>
            </button>

            <button id="toggle-table" type="button" class="button button-edusystem-alliance">
                <?= __('Show payments', 'edusystem'); ?>
            </button>
        </div>
    </div>
</div>

<table class="woocommerce-MyAccount-invoices-table shop_table_responsive my_account_orders" id="table-invoices">
    <thead>
        <tr>
            <th class="woocommerce-MyAccount-invoices-table__header woocommerce-MyAccount-invoices-table__header--order_id">
                <span class="nobr"><?= __('Payment ID', 'edusystem'); ?></span>
            </th>
            <th class="woocommerce-MyAccount-invoices-table__header woocommerce-MyAccount-invoices-table__header--customer">
                <span class="nobr"><?= __('Customer', 'edusystem'); ?></span>
            </th>
            <th class="woocommerce-MyAccount-invoices-table__header woocommerce-MyAccount-invoices-table__header--fee">
                <span class="nobr"><?= __('Fee', 'edusystem'); ?></span>
            </th>
            <th class="woocommerce-MyAccount-invoices-table__header woocommerce-MyAccount-invoices-table__header--created_at">
                <span class="nobr"><?= __('Created', 'edusystem'); ?></span>
            </th>
        </tr>
    </thead>
    <tbody id="table_tbody-invoices">
        <?php if (!empty($current_invoice['orders'])) {
            foreach ($current_invoice['orders'] as $order) : ?>
                <tr class="woocommerce-MyAccount-invoices-table__row">
                    <td data-title="<?php echo esc_attr__('Payment ID', 'edusystem'); ?>">
                        # <?= esc_html($order['order_id']); ?>
                    </td>
                    <td data-title="<?php echo esc_attr__('Customer', 'edusystem'); ?>">
                        <?= esc_html($order['customer']); ?>
                    </td>
                    <td data-title="<?php echo esc_attr__('Fee', 'edusystem'); ?>">
                        <?= wc_price($order['fee']); ?>
                    </td>
                    <td data-title="<?php echo esc_attr__('Created', 'edusystem'); ?>">
                        <b><?= esc_html($order['created_at']); ?></b>
                    </td>
                </tr>
            <?php endforeach;
        } else { ?>
            <tr class="woocommerce-MyAccount-alliances-table__row">
                <td colspan="4" style="text-align:center;"><?= __('There are not records', 'edusystem') ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<table class="woocommerce-MyAccount-invoices-table shop_table_responsive my_account_orders" id="table-payments" style="display: none;">
    <thead>
        <tr>
            <th class="woocommerce-MyAccount-invoices-table__header woocommerce-MyAccount-invoices-table__header--status">
                <span class="nobr"><?= __('Status', 'edusystem'); ?></span>
            </th>
            <th class="woocommerce-MyAccount-invoices-table__header woocommerce-MyAccount-invoices-table__header--month">
                <span class="nobr"><?= __('Month', 'edusystem'); ?></span>
            </th>
            <th class="woocommerce-MyAccount-invoices-table__header woocommerce-MyAccount-invoices-table__header--amount">
                <span class="nobr"><?= __('Amount', 'edusystem'); ?></span>
            </th>
            <th class="woocommerce-MyAccount-invoices-table__header woocommerce-MyAccount-invoices-table__header--total_orders">
                <span class="nobr"><?= __('Total orders', 'edusystem'); ?></span>
            </th>
        </tr>
    </thead>
    <tbody id="table_tbody-payments">
        <?php if (!empty($transactions['orders'])) {
            foreach ($transactions['orders'] as $order) : ?>
                <tr class="woocommerce-MyAccount-invoices-table__row">
                    <td data-title="<?php echo esc_attr__('Status', 'edusystem'); ?>">
                        <?= esc_html($order['status']); ?>
                    </td>
                    <td data-title="<?php echo esc_attr__('Month', 'edusystem'); ?>">
                        <?= esc_html($order['month']); ?>
                    </td>
                    <td data-title="<?php echo esc_attr__('Amount', 'edusystem'); ?>">
                        <?= wc_price($order['amount']); ?>
                    </td>
                    <td data-title="<?php echo esc_attr__('Total orders', 'edusystem'); ?>">
                        <b><?= esc_html($order['total_orders']); ?></b>
                    </td>
                </tr>
            <?php endforeach;
        } else { ?>
            <tr class="woocommerce-MyAccount-alliances-table__row">
                <td colspan='4' style='text-align:center;'><?= __('There are not records', 'edusystem') ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
