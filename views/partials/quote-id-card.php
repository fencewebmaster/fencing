<?php
$quote_id_dom_id = isset( $quote_id_dom_id ) ? (string) $quote_id_dom_id : 'quote-id-1';
$quote_card_class = isset( $quote_card_class ) ? (string) $quote_card_class : '';
$quote_id_val = isset( $quote_id_val ) ? (string) $quote_id_val : (string) ( @$_SESSION['planner_id'] ?? '' );
$quote_share_url = function_exists( 'fc_planner_qid_share_url' ) ? fc_planner_qid_share_url( $quote_id_val ) : '';
$quote_card_hide_head = ! empty( $quote_card_hide_head );
$qic_head_class = strpos( $quote_card_class, 'float-end' ) !== false ? 'qic-head px-3' : 'qic-head';
?>
<div class="quote-id-card <?php echo htmlspecialchars( $quote_card_class, ENT_QUOTES, 'UTF-8' ); ?>">
	<?php if ( ! $quote_card_hide_head ) : ?>
	<div class="<?php echo htmlspecialchars( $qic_head_class, ENT_QUOTES, 'UTF-8' ); ?>">Your Quote ID</div>
	<?php endif; ?>
	<div class="qic-row">
		<div class="qic-body btn-copy-link" data-id="<?php echo htmlspecialchars( $quote_id_dom_id, ENT_QUOTES, 'UTF-8' ); ?>">
			<div id="<?php echo htmlspecialchars( $quote_id_dom_id, ENT_QUOTES, 'UTF-8' ); ?>"><?php echo htmlspecialchars( $quote_id_val, ENT_QUOTES, 'UTF-8' ); ?></div>
		</div>
		<?php if ( $quote_share_url !== '' ) : ?>
		<button type="button" class="fc-copy-quote-link" data-copy-url="<?php echo htmlspecialchars( $quote_share_url, ENT_QUOTES, 'UTF-8' ); ?>" title="Copy quote link">
			<i class="fa-solid fa-link" aria-hidden="true"></i>
			<span class="fc-copy-quote-link__label">Copy Link</span>
		</button>
		<?php endif; ?>
	</div>
</div>
