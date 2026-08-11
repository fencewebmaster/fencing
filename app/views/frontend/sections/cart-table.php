<?php
require_once dirname(__DIR__, 4) . '/app/src/Services/CartBuilderService.php';
require_once dirname(__DIR__, 4) . '/app/src/Services/FenceCatalogService.php';
require_once dirname(__DIR__, 4) . '/app/src/Services/WcProductCsvService.php';
$cart = isset($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];
$edited = FALSE;
?>

<?php if( @$cart ): ?>

<?php
$cart_included_count = \Fc\Admin\Services\CartBuilderService::cartIncludedItemCount(
    isset( $cart['items'] ) && is_array( $cart['items'] ) ? $cart['items'] : array()
);
?>

<span class="badge bg-danger mb-2 text-uppercase p-2"><?php echo (int) $cart_included_count; ?> Items</span>

<div class="fc-card-body fc-border-bottom fc-p-0 fc-border-0 fc-position-relative">
    <div class="fc-table-rounded-border mb-3">
        

        <table class="table-cart table table-hover fc-table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th class="d-none d-md-table-cell">QTY</th>
                    <th colspan="2">Description</th>
                    <th class="text-center d-md-table-cell d-none">Stock</th>
                </tr>
            </thead>
            <tbody>

            	<?php $ci = 0; ?>
            	<?php foreach( @$cart['items'] as $cart_item ): ?>

                <input name="cart[original_qty][<?php echo $ci; ?>]" type="hidden" value="<?php echo @$cart_item['original_qty']; ?>" class="fc-form-control" min="1" required>

                <tr class="fc-position-relative<?php echo ! empty( $cart_item['optional'] ) && empty( $cart_item['optional_included'] ) ? ' fc-cart-item--optional-pending' : ''; ?>" data-original="<?php echo $cart_item['original_qty']; ?>" data-cart-slug="<?php echo htmlspecialchars((string) (@$cart_item['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"<?php echo ! empty( $cart_item['optional'] ) ? ' data-fc-cart-optional="1"' : ''; ?>>

                    <td class="d-none d-md-table-cell align-middle text-center">

                        <input type="hidden" name="cart[qty][<?php echo $ci; ?>]" class="fc-form-field input-qty" value="<?php echo @$cart_item['qty']; ?>">

                        <?php if ( ! empty( $cart_item['optional'] ) && empty( $cart_item['optional_included'] ) ) : ?>
                        <div class="fc-item-value fw-bold text-muted">—</div>
                        <div class="small text-muted mt-1"><?php echo (int) ( $cart_item['suggested_qty'] ?? 0 ); ?> if added</div>
                        <?php else : ?>
                        <div class="fc-item-value fw-bold"><?php echo @$cart_item['qty']; ?></div>
                        <?php endif; ?>

                        <div class="fencing-mb-input md-qty bg-white mt-3" style="display: none;">
                            <div class="d-flex align-items-center">
                                <div class="fencing-qty-minus fencing-qty-btn px-2"> 
                                    <i class="fa fa-minus"></i>
                                </div>        
                                <input type="text" class="numeric fc-form-field text-center no-enter" input-type="number" data-min="0" maxlength="6" data-max="999999" value="<?php echo @$cart_item['qty']; ?>"> 
                                <div class="fencing-qty-plus fencing-qty-btn px-2">
                                    <i class="fa fa-plus"></i>
                                </div>
                            </div>
                        </div>
                        
                    </td>

                    <?php
                    $full_cart_image = \Fc\Admin\Services\WcProductCsvService::displayImageUrl( (string) ( $cart_item['image'] ?? '' ) );
                    $product_image_attrs = ' class="product-image fc-cart-product-image-cell align-middle"';
                    if ( $full_cart_image !== '' ) {
                        $product_image_attrs = ' class="product-image fc-cart-product-image-cell align-middle fc-cart-gallery-trigger" role="button" tabindex="0" aria-label="View larger image"'
                            . ' data-fc-gallery-src="' . htmlspecialchars( $full_cart_image, ENT_QUOTES, 'UTF-8' ) . '"'
                            . ' data-fc-gallery-title="' . htmlspecialchars( (string) @$cart_item['name'], ENT_QUOTES, 'UTF-8' ) . '"';
                    }
                    ?>
                    <td<?php echo $product_image_attrs; ?>>
                        <?php if ( $full_cart_image !== '' ) : ?>
                        <img src="<?php echo htmlspecialchars( $full_cart_image, ENT_QUOTES, 'UTF-8' ); ?>"
                             alt=""
                             class="fc-cart-product-thumb"
                             loading="lazy"
                             decoding="async"
                             width="100"
                             height="100">
                        <?php endif; ?>
                    </td>
                    <td class="align-top" style="width: max-content;">
                        <div class="fw-bold text-dark mb-2">
                            <?php echo @$cart_item['name']; ?>
                            <?php if ( ! empty( $cart_item['optional'] ) ) : ?>
                            <span class="badge rounded-pill bg-secondary ms-1 align-middle">Optional</span>
                            <?php endif; ?>
                        </div>

                        <div class="text-muted mb-1"><?php echo @$cart_item['sku']; ?></div>
                        <?php
                        if ( ! class_exists( '\Fc\Admin\Services\ConsoleSettings' ) ) {
                            require_once dirname( __DIR__, 4) . '/app/src/Services/ConsoleSettings.php';
                        }
                        if ( ! empty( $cart_item['slug'] ) && \Fc\Admin\Services\ConsoleSettings::debugMode() ) :
                        ?>
                        <div class="small text-muted mb-1 fc-cart-item-slug"><?php echo htmlspecialchars( (string) $cart_item['slug'], ENT_QUOTES, 'UTF-8' ); ?></div>
                        <?php endif; ?>
                        <?php
                        $fence_style_label = \Fc\Admin\Services\FenceCatalogService::cartItemFenceStyleLabel( $cart_item, isset( $fences ) ? $fences : array() );
                        if ( $fence_style_label !== '' ) :
                        ?>
                        <div class="small text-muted mb-2 fc-cart-fence-style"><?php echo htmlspecialchars( $fence_style_label, ENT_QUOTES, 'UTF-8' ); ?></div>
                        <?php endif; ?>

                        <div class="d-block d-md-none">
                            <div class="fw-bold d-flex align-items-center">
                            <?php if(@$cart_item['stock'] == 'yes'): ?>
                                 <i class="fa-solid fa-circle-check text-success me-2 fs-6"></i> In-Stock
                            <?php else: ?>
                            <i class="fa-solid fa-circle-exclamation text-orange fs-6 me-2"></i> Low-Stock
                            <?php endif; ?>
                            </div>
                        </div>                            

                        <div class="fc-item-value d-md-none d-block fw-bold border rounded bg-light text-center p-1<?php echo ! empty( $cart_item['optional'] ) && empty( $cart_item['optional_included'] ) ? ' text-muted' : ''; ?>" style="max-width: 128px;">
                            <?php echo ! empty( $cart_item['optional'] ) && empty( $cart_item['optional_included'] ) ? '—' : @$cart_item['qty']; ?>
                        </div>

                        <?php if ( ! empty( $cart_item['optional'] ) ) : ?>
                        <div class="fc-cart-optional-actions mt-2">
                            <?php if ( empty( $cart_item['optional_included'] ) ) : ?>
                            <button type="button"
                                class="btn btn-sm btn-orange text-uppercase fw-bold js-fc-optional-cart-toggle"
                                data-optional-key="<?php echo htmlspecialchars( (string) ( $cart_item['optional_key'] ?? '' ), ENT_QUOTES, 'UTF-8' ); ?>"
                                data-include="1">
                                Add to cart
                            </button>
                            <?php else : ?>
                            <button type="button"
                                class="btn btn-sm btn-danger text-uppercase fw-bold js-fc-optional-cart-toggle"
                                data-optional-key="<?php echo htmlspecialchars( (string) ( $cart_item['optional_key'] ?? '' ), ENT_QUOTES, 'UTF-8' ); ?>"
                                data-include="0">
                                Remove from cart
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="md-qty" style="display: none;">
                            <div class="d-md-none d-table-cell">
                                <div class="fencing-mb-input bg-white mt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="fencing-qty-minus fencing-qty-btn px-2"> 
                                            <i class="fa fa-minus"></i>
                                        </div>        
                                        <input type="text" class="numeric fc-form-field text-center no-enter" input-type="number" data-min="0" maxlength="6" data-max="999999" value="<?php echo @$cart_item['qty']; ?>"> 
                                        <div class="fencing-qty-plus fencing-qty-btn px-2">
                                            <i class="fa fa-plus"></i>
                                        </div>
                                    </div>
                                </div>                    
                            </div>
                        </div>

                        <?php if( @$cart_item['qty'] != @$cart_item['original_qty'] ): ?>
                        <div class="qty-edited" data-toggle="toggle" title="Edited">
                            <i class="fa fa-pencil text-orange"></i>       
                        </div>
                        <?php $edited = TRUE; ?>
                        <?php endif; ?>


                    </td>
                    <td width="120" class="px-1 align-middle d-md-table-cell d-none">

                        <div class="fw-boldx d-flex align-items-center">
                        <?php if(@$cart_item['stock'] == 'yes'): ?>
                             <i class="fa-solid fa-circle-check text-success me-2 fs-4"></i> In-Stock
                        <?php else: ?>
                        <i class="fa-solid fa-circle-exclamation text-orange fs-4 me-2"></i> Low-Stock
                        <?php endif; ?>                            
                        </div>

                    </td>
                </tr>
                <?php $ci++; ?>
                <?php endforeach; ?>

            </tbody>
        </table>

    </div>

    <div class="fc-float-r fc-mb-2 fc-d-none">
        
        <table>
            <tr>
                <td class="fc-text-right"><b class="fc-mr-1">Sub Total:</b></td>
                <td>$<?php echo number_format(@$cart['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td class="fc-text-right"><b class="fc-mr-1">Trade Discount:</b></td>
                <td>$<?php echo number_format(@$cart['trade_discount'], 2); ?></td>
            </tr>
            <tr>
                <td class="fc-text-right"><b class="fc-mr-1">Delivery:</b></td>
                <td>$<?php echo number_format(@$cart['delivery_fee'], 2); ?></td>
            </tr>
            <tr>
                <td class="fc-text-right"><b class="fc-mr-1">GST:</b></td>
                <td>$<?php echo number_format(@$cart['gst'], 2); ?></td>
            </tr>
            <tr>
                <td class="fc-text-right"><b class="fc-mr-1">Total:</b></td>
                <td>$<?php echo number_format(@$cart['total'], 2); ?></td>
            </tr>
        </table>

    </div>

    <div style="clear: both;"></div>

</div>
<?php endif; ?>