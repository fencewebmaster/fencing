<?php 
	$cart = isset($_SESSION['fc_cart']) ? $_SESSION['fc_cart'] : [];
?>

<?php if( $cart ): ?>
<div class="fc-card-body fc-border-bottom fc-p-0 fc-border-0 fc-position-relative">
    <div class="fc-table-rounded-border">
        
        <table class="fc-table fc-table-bordered fc-table-striped">
            <thead class="fc-bg-dark fc-border">
                <tr>
                    <th>QTY</th>
                    <th>Description</th>
                    <!-- <th>SKU</th>
                    <th>RRP</th>
                    <th>Trade Price</th>
                    <th>Sub Total</th> -->
                    <th>In-stock</th>
                </tr>
            </thead>
            <tbody>

            	<?php $ci = 0; ?>
            	<?php foreach( $cart['items'] as $cart_item ): ?>
                <tr class="fc-position-relative">
                    <td width="54" class="valign-top">
                        <span class="fc-item-value"><?php echo $cart_item['qty']; ?></span>
                        <input name="cart[qty][<?php echo $ci; ?>]" type="number" value="<?php echo $cart_item['qty']; ?>" class="fc-form-control" min="1" required>
                    </td>
                    <td width="279"><p><strong><?php echo $cart_item['name']; ?></strong><br /><?php echo $cart_item['sku']; ?></p></td>
                    <!-- <td><?php echo $cart_item['sku']; ?></td>
                    <td><s>$<?php echo $cart_item['rrp']; ?></s></td>
                    <td>$<?php echo $cart_item['trade_price']; ?></td>
                    <td>$<?php echo number_format($cart_item['subtotal'], 2); ?></td> -->
                    <td>
                        <div class="fc-stock-status fc-stock-status--<?php echo $cart_item['stock']; ?>"><?php echo ucwords($cart_item['stock']); ?></div>
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
                <td>$<?php echo number_format($cart['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td class="fc-text-right"><b class="fc-mr-1">Trade Discount:</b></td>
                <td>$<?php echo number_format($cart['trade_discount'], 2); ?></td>
            </tr>
            <tr>
                <td class="fc-text-right"><b class="fc-mr-1">Delivery:</b></td>
                <td>$<?php echo number_format($cart['delivery_fee'], 2); ?></td>
            </tr>
            <tr>
                <td class="fc-text-right"><b class="fc-mr-1">GST:</b></td>
                <td>$<?php echo number_format($cart['gst'], 2); ?></td>
            </tr>
            <tr>
                <td class="fc-text-right"><b class="fc-mr-1">Total:</b></td>
                <td>$<?php echo number_format($cart['total'], 2); ?></td>
            </tr>
        </table>

    </div>

    <div style="clear: both;"></div>

</div>
<?php endif; ?>