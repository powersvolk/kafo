<?php do_action('wpo_wcpdf_before_document', $this->type, $this->order); ?>

<table class="head container">
    <tr>
        <td class="header">
            <?php
            if ($this->has_header_logo()) {
                $this->header_logo();
            } else {
                echo apply_filters('wpo_wcpdf_invoice_title', __('Invoice', 'woocommerce-pdf-invoices-packing-slips'));
            }
            ?>
        </td>
        <td class="shop-info">
            <table class="order-data-column">
                <tr>
                    <th class="order-data-label"><?php _e('Shop', 'woocommerce-pdf-invoices-packing-slips'); ?></th>
                    <td class="order-data-content">
                        <div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
                        <div class="shop-address"><?php $this->shop_address(); ?></div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<?php do_action('wpo_wcpdf_after_document_label', $this->type, $this->order); ?>

<table class="order-data-addresses">
    <tr>
        <td class="address billing-address">
            <table class="order-data-column">
                <tr>
                    <th class="order-data-label"><?php _e('Customer', 'woocommerce-pdf-invoices-packing-slips'); ?></th>
                    <td class="order-data-content">
                        <!-- <h3><?php _e('Billing Address:', 'woocommerce-pdf-invoices-packing-slips'); ?></h3> -->
                        <?php $this->billing_address(); ?>
                        <?php if (isset($this->settings['display_email'])) { ?>
                            <div class="billing-email"><?php $this->billing_email(); ?></div>
                        <?php } ?>
                        <?php if (isset($this->settings['display_phone'])) { ?>
                            <div class="billing-phone"><?php $this->billing_phone(); ?></div>
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </td>
        <td class="order-data">
            <table>
                <?php do_action('wpo_wcpdf_before_order_data', $this->type, $this->order); ?>

                <tr class="order-number">
                    <th class="order-data-label"><?php _e('Order Number:', 'woocommerce-pdf-invoices-packing-slips'); ?></th>
                    <td class="order-data-content"><?php $this->order_number(); ?></td>
                </tr>
                <?php if (isset($this->settings['display_number'])) { ?>
                    <tr class="invoice-number">
                        <th class="order-data-label"><?php _e('Invoice Number:', 'woocommerce-pdf-invoices-packing-slips'); ?></th>
                        <td class="order-data-content"><?php $this->invoice_number(); ?></td>
                    </tr>
                <?php } ?>
                <?php if (isset($this->settings['display_date'])) { ?>
                    <tr class="invoice-date">
                        <th class="order-data-label"><?php _e('Invoice Date:', 'woocommerce-pdf-invoices-packing-slips'); ?></th>
                        <td class="order-data-content"><?php $this->invoice_date(); ?></td>
                    </tr>
                <?php } ?>
                <tr class="order-date">
                    <th class="order-data-label"><?php _e('Order Date:', 'woocommerce-pdf-invoices-packing-slips'); ?></th>
                    <td class="order-data-content"><?php $this->order_date(); ?></td>
                </tr>
                <tr class="payment-method">
                    <th class="order-data-label"><?php _e('Payment Method:', 'woocommerce-pdf-invoices-packing-slips'); ?></th>
                    <td class="order-data-content"><?php $this->payment_method(); ?></td>
                </tr>
                <?php do_action('wpo_wcpdf_after_order_data', $this->type, $this->order); ?>
            </table>
        </td>
    </tr>
</table>

<?php do_action('wpo_wcpdf_before_order_details', $this->type, $this->order); ?>

<table class="order-details">
    <thead>
    <tr>
        <th class="product first"><?php _e('Product', 'woocommerce-pdf-invoices-packing-slips'); ?></th>
        <th class="quantity"><?php _e('Quantity', 'woocommerce-pdf-invoices-packing-slips'); ?></th>
        <th class="price last"><?php _e('Price', 'woocommerce-pdf-invoices-packing-slips'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $items = $this->get_order_items();
    if (sizeof($items) > 0) {
        foreach ($items as $item_id => $item) {
            ?>
            <tr class="<?php echo apply_filters('wpo_wcpdf_item_row_class', $item_id, $this->type, $this->order, $item_id); ?>">
                <td class="product first">
                    <?php $description_label = __('Description', 'woocommerce-pdf-invoices-packing-slips'); // registering alternate label translation ?>
                    <span class="item-name"><?php echo $item['name']; ?></span>
                    <?php do_action('wpo_wcpdf_before_item_meta', $this->type, $item, $this->order); ?>
                    <span class="item-meta"><?php echo $item['meta']; ?></span>
                    <dl class="meta">
                        <?php $description_label = __('SKU', 'woocommerce-pdf-invoices-packing-slips'); // registering alternate label translation ?>
                        <?php if (!empty($item['sku'])) { ?>
                            <dt class="sku"><?php _e('SKU:', 'woocommerce-pdf-invoices-packing-slips'); ?></dt>
                            <dd class="sku"><?php echo $item['sku']; ?></dd>
                        <?php } ?>
                        <?php if (!empty($item['weight'])) { ?>
                            <dt class="weight"><?php _e('Weight:', 'woocommerce-pdf-invoices-packing-slips'); ?></dt>
                            <dd class="weight"><?php echo $item['weight']; ?><?php echo get_option('woocommerce_weight_unit'); ?></dd>
                        <?php } ?>
                    </dl>
                    <?php do_action('wpo_wcpdf_after_item_meta', $this->type, $item, $this->order); ?>
                </td>
                <td class="quantity"><?php echo $item['quantity']; ?></td>
                <td class="price last"><?php echo $item['order_price']; ?></td>
            </tr>
            <?php
        }
    }
    ?>
    </tbody>
    <tfoot>
    <tr class="no-borders">
        <td class="no-borders">
            <div class="customer-notes">
                <?php do_action('wpo_wcpdf_before_customer_notes', $this->type, $this->order); ?>
                <?php if ($this->get_shipping_notes()) { ?>
                    <h3><?php _e('Customer Notes', 'woocommerce-pdf-invoices-packing-slips'); ?></h3>
                    <?php $this->shipping_notes(); ?>
                <?php } ?>
                <?php do_action('wpo_wcpdf_after_customer_notes', $this->type, $this->order); ?>
            </div>
            <?php if (isset($this->settings['display_shipping_address']) && $this->ships_to_different_address()) { ?>
                <div class="customer-notes">
                    <h3><?php _e('Ship To:', 'woocommerce-pdf-invoices-packing-slips'); ?></h3>
                    <?php $this->shipping_address(); ?>
                </div>
            <?php } ?>
            <?php if ($this->get_payment_method() == 'Check payments') { ?>
                <div class="general-notes">
                    <h3><?php _e('Payment instructions', 'woocommerce-pdf-invoices-packing-slips'); ?></h3><br>
                    <strong><?php _e('Reciever', 'woocommerce-pdf-invoices-packing-slips'); ?>
                        :</strong> <?php $this->shop_name(); ?><br>
                    <?php $this->extra_1(); ?><br>
                    <strong><?php _e('Notes', 'woocommerce-pdf-invoices-packing-slips'); ?>
                        :</strong> <?php $this->order_number(); ?><br>
                    <?php foreach ($this->get_woocommerce_totals() as $key => $total) {
                        if ($key == 'order_total') {
                            ?>
                            <strong><?php _e('Total', 'woocommerce-pdf-invoices-packing-slips'); ?>
                                :</strong> <?php echo $total['value']; ?>
                        <?php } ?>
                    <?php } ?>
                </div>
            <?php } ?>
            <?php if (wc_get_payment_gateway_by_order($this->order)->id == 'lhv_hire_purchase') { ?>
                <div class="general-notes">
                    <?php $this->extra_2(); ?>
                </div>
            <?php } ?>
        </td>
        <td class="no-borders" colspan="2">
            <table class="totals">
                <tfoot>
                <?php foreach ($this->get_woocommerce_totals() as $key => $total) { ?>
                    <?php if ($key == 'order_total') {
                        $totalSUM = $total['value']
                        ?>
                        <tr>
                            <td style="border: none">&nbsp;</td>
                            <td style="border: none">&nbsp;</td>
                            <td style="border: none">&nbsp;</td>
                        </tr>
                    <?php } ?>
                    <tr class="<?php echo $key; ?>">
                        <td class="no-borders"></td>
                        <th class="description"><?php echo $total['label']; ?></th>
                        <td class="price"><span class="totals-price"><?php echo $total['value']; ?></span></td>
                    </tr>
                <?php } ?>
                </tfoot>
            </table>
        </td>
    </tr>
    </tfoot>
</table>

<?php do_action('wpo_wcpdf_after_order_details', $this->type, $this->order); ?>

<?php if ($this->get_footer()) { ?>
    <div id="footer">
        <?php $this->footer(); ?>
    </div><!-- #letter-footer -->
<?php } ?>
<?php do_action('wpo_wcpdf_after_document', $this->type, $this->order); ?>
