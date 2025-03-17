<?php
error_log('Displaying recommendations table. Count: ' . count($recommendations));
?>
<table class="woocommerce-table shop_table recommendations-table">
    <thead>
        <tr>
            <th class="product-thumbnail"><?php esc_html_e('Image', 'product-recommendations'); ?></th>
            <th><?php esc_html_e('Product', 'product-recommendations'); ?></th>
            <th><?php esc_html_e('Date Added', 'product-recommendations'); ?></th>
            <th><?php esc_html_e('Status', 'product-recommendations'); ?></th>
            <th><?php esc_html_e('Notes', 'product-recommendations'); ?></th>
            <th><?php esc_html_e('Actions', 'product-recommendations'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if (empty($recommendations)): ?>
            <tr>
                <td colspan="6" class="woocommerce-no-items"><?php esc_html_e('No recommendations found', 'product-recommendations'); ?></td>
            </tr>
        <?php else:
            foreach ($recommendations as $recommendation): 
                error_log('Processing recommendation in table: ' . print_r($recommendation, true));
                $product = wc_get_product($recommendation->product_id);
                $image_url = $product ? wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') : wc_placeholder_img_src('thumbnail');
                $price_html = $product ? $product->get_price_html() : '';
            ?>
                <tr data-id="<?php echo esc_attr($recommendation->id); ?>">
                    <td class="product-thumbnail">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($recommendation->product_name); ?>" />
                    </td>
                    <td>
                        <div class="product-info">
                            <div class="product-name"><?php echo esc_html($recommendation->product_name); ?></div>
                            <div class="product-price"><?php echo $price_html; ?></div>
                        </div>
                    </td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($recommendation->date_created))); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo esc_attr($recommendation->status); ?>">
                            <?php echo esc_html(ucfirst($recommendation->status)); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($recommendation->notes); ?></td>
                    <td>
                        <button class="button button-remove-recommendation" data-id="<?php echo esc_attr($recommendation->id); ?>">
                            <?php esc_html_e('Remove', 'product-recommendations'); ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach;
        endif; ?>
    </tbody>
</table>

<style>
.product-info {
    display: flex;
    flex-direction: column;
}
.product-name {
    font-weight: bold;
    margin-bottom: 5px;
}
.product-price {
    color: #666;
    font-size: 0.8em;
}
</style> 