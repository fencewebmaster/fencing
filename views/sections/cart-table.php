<?php 
$cart = isset($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];
$edited = FALSE;
?>

<?php if( @$cart ): ?>

<span class="badge bg-danger mb-2 text-uppercase p-2"><?php echo count($cart['items']); ?> Items</span>

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

                <tr class="fc-position-relative" data-original="<?php echo $cart_item['original_qty']; ?>">

                    <td class="d-none d-md-table-cell align-middle text-center">

                        <input type="hidden" name="cart[qty][<?php echo $ci; ?>]" class="fc-form-field input-qty" value="<?php echo @$cart_item['qty']; ?>">

                        <div class="fc-item-value fw-bold"><?php echo @$cart_item['qty']; ?></div>

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

                    <?php $featured_image = add_filepath_last(@$cart_item['image'], '-150x150'); ?>
                    <td style="background:url(<?php echo @$featured_image; ?>);" class="product-image">

                    </td>
                    <td class="align-top" style="width: max-content;">
                        <div class="fw-bold text-dark mb-2"><?php echo @$cart_item['name']; ?></div>

                        <div class="text-muted mb-2"><?php echo @$cart_item['sku']; ?></div>

                        <div class="d-block d-md-none">
                            <div class="fw-bold d-flex align-items-center">
                            <?php if(@$cart_item['stock'] == 'yes'): ?>
                                 <i class="fa-solid fa-circle-check text-success me-2 fs-6"></i> In-Stock
                            <?php else: ?>
                            <i class="fa-solid fa-circle-exclamation text-orange fs-6 me-2"></i> Low-Stock
                            <?php endif; ?>
                            </div>
                        </div>                            

                        <?php if( in_uri_segment(demo_stages()) ):?>
                        <small class="fc-text-success d-nonex"><?php echo @$cart_item['slug']; ?></small>
                        <?php endif; ?>

                        <div class="fc-item-value d-md-none d-block fw-bold border rounded bg-light text-center p-1" style="max-width: 128px;">
                            <?php echo @$cart_item['qty']; ?>
                        </div>

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