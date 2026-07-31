<?php 
$cart = isset($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];
?>

<?php if( @$cart ): ?>

<span class="badge bg-danger mb-2 text-uppercase p-2"><?php echo count($cart['items']); ?> Items</span>

<div class="fc-card-body fc-border-bottom fc-p-0 fc-border-0 fc-position-relative">
    <div class="fc-table-rounded-border mb-3">
        
        <table class="table table-hover fc-table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th class="text-center">QTY</th>
                    <th colspan="2">Description</th>
                    <th class="text-center d-md-table-cell d-none">In-stock</th>
                </tr>
            </thead>
            <tbody>

            	<?php $ci = 0; ?>
            	<?php foreach( @$cart['items'] as $cart_item ): ?>

                <input name="cart[original_qty][<?php echo $ci; ?>]" type="hidden" value="<?php echo @$cart_item['original_qty']; ?>" class="fc-form-control" min="1" required>

                <tr class="fc-position-relative">
                    <td class="text-center align-middle px-1">                        

                        <span class="fc-item-value fw-bold" data-original="<?php echo @$cart_item['original_qty']; ?>"><?php echo number_format(@$cart_item['qty']); ?></span>

                        <input name="cart[qty][<?php echo $ci; ?>]" type="number" value="<?php echo @$cart_item['qty']; ?>" class="fc-form-control fc-form-control no-enter" min="0">
                            

                        <?php if( @$cart_item['qty'] != @$cart_item['original_qty'] ): ?>
                        <div class="qty-edited" data-toggle="toggle" title="Edited">
                            <i class="fa fa-pencil"></i>       
                        </div>
                        <?php endif; ?>

                    </td>

                    <?php
                    $full_cart_image = function_exists( 'fc_cart_display_image_url' )
                        ? fc_cart_display_image_url( (string) ( $cart_item['image'] ?? '' ) )
                        : trim( (string) ( $cart_item['image'] ?? '' ) );
                    ?>
                    <td class="product-image fc-cart-product-image-cell align-middle p-1 d-sm-table-cell d-none">
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
                        <div class="fw-bold text-dark mb-2"><?php echo @$cart_item['name']; ?></div>

                        <div class="text-muted mb-1"><?php echo @$cart_item['sku']; ?></div>
                        <?php
                        if ( ! function_exists( 'fc_console_debug_mode' ) ) {
                            require_once dirname( __DIR__, 3 ) . '/config/console.php';
                        }
                        if ( ! empty( $cart_item['slug'] ) && fc_console_debug_mode() ) :
                        ?>
                        <div class="small text-muted mb-1 fc-cart-item-slug"><?php echo htmlspecialchars( (string) $cart_item['slug'], ENT_QUOTES, 'UTF-8' ); ?></div>
                        <?php endif; ?>
                        <?php
                        $fence_style_label = function_exists( 'fc_cart_item_fence_style_label' )
                            ? fc_cart_item_fence_style_label( $cart_item, isset( $fences ) ? $fences : array() )
                            : '';
                        if ( $fence_style_label !== '' ) :
                        ?>
                        <div class="small text-muted mb-2 fc-cart-fence-style"><?php echo htmlspecialchars( $fence_style_label, ENT_QUOTES, 'UTF-8' ); ?></div>
                        <?php endif; ?>

                        <div class="d-block d-md-none">
                            <div class="fw-bold d-flex align-items-center">
                            <?php if(@$cart_item['stock'] == 'yes'): ?>
                                 <i class="fa-solid fa-circle-check text-success me-2 fs-6"></i> In-stock: Yes
                            <?php else: ?>
                            <i class="fa-solid fa-circle-exclamation text-orange fs-6 me-2"></i> In-stock: Low
                            <?php endif; ?>
                            </div>
                        </div>

                    </td>
                    <!-- <td><?php echo @$cart_item['sku']; ?></td>
                    <td><s>$<?php echo @$cart_item['rrp']; ?></s></td>
                    <td>$<?php echo @$cart_item['trade_price']; ?></td>
                    <td>$<?php echo number_format(@$cart_item['subtotal'], 2); ?></td> -->
                    <td width="90" class="px-1 align-middle text-center text-center d-md-table-cell d-none">

                        <div class="fw-bold d-flex align-items-center justify-content-center">
                        <?php if(@$cart_item['stock'] == 'yes'): ?>
                             <i class="fa-solid fa-circle-check text-success me-2 fs-4"></i> Yes
                        <?php else: ?>
                        <i class="fa-solid fa-circle-exclamation text-orange fs-4 me-2"></i> Low
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