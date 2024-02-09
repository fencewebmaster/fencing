<?php 
$cart = isset($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];
?>

<?php if( @$cart ): ?>
<div class="fc-card-body fc-border-bottom fc-p-0 fc-border-0 fc-position-relative">
    <div class="fc-table-rounded-border">
        
        <table class="table table-hover fc-table fc-table-bordered fc-table-striped">
            <thead class="table-dark fc-border">
                <tr>
                    <th class="text-center">QTY</th>
                    <th colspan="2">Description</th>
                    <th class="text-center">In-stock</th>
                </tr>
            </thead>
            <tbody>

            	<?php $ci = 0; ?>
            	<?php foreach( @$cart['items'] as $cart_item ): ?>

                <input name="cart[orignal_qty][<?php echo $ci; ?>]" type="hidden" value="<?php echo @$cart_item['orignal_qty']; ?>" class="fc-form-control" min="1" required>

                <tr class="fc-position-relative">
                    <td width="90" class="text-center align-middle">                        

                        <span class="fc-item-value fw-bold" data-original="<?php echo @$cart_item['orignal_qty']; ?>"><?php echo @$cart_item['qty']; ?></span>
                        <input name="cart[qty][<?php echo $ci; ?>]" type="number" value="<?php echo @$cart_item['qty']; ?>" class="fc-form-control" min="0">

                        <?php if( @$cart_item['qty'] != @$cart_item['orignal_qty'] ): ?>
                        <div class="qty-edited">
                            <i class="fa fa-pencil"></i>       
                        </div>
                        <?php endif; ?>

                    </td>
                    <td style="min-width:50px;max-width:100px;" class="p-1">
                        <img src="<?php echo @$cart_item['image']; ?>">
                    </td>
                    <td class="align-top">
                        <h6 class="fw-bold"><?php echo @$cart_item['name']; ?></h6>
                        <?php echo @$cart_item['sku']; ?>

                        <small class="fc-text-success d-none"><?php echo @$cart_item['slug']; ?></small>
                    </td>
                    <!-- <td><?php echo @$cart_item['sku']; ?></td>
                    <td><s>$<?php echo @$cart_item['rrp']; ?></s></td>
                    <td>$<?php echo @$cart_item['trade_price']; ?></td>
                    <td>$<?php echo number_format(@$cart_item['subtotal'], 2); ?></td> -->
                    <td width="115">
                        <div class="fc-stock-status fc-stock-status--<?php echo @$cart_item['stock']; ?>"><?php echo ucwords(@$cart_item['stock']); ?></div>
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