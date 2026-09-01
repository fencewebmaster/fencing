<?php

use Fc\Admin\Services\PlannerSessionService;

$quote_id_dom_id = isset( $quote_id_dom_id ) ? (string) $quote_id_dom_id : 'quote-id-1';
$quote_card_class = isset( $quote_card_class ) ? (string) $quote_card_class : '';
$quote_id_val = isset( $quote_id_val ) ? (string) $quote_id_val : (string) ( @$_SESSION['planner_id'] ?? '' );
$quote_share_url = PlannerSessionService::qidShareUrl( $quote_id_val );
$quote_card_hide_head = ! empty( $quote_card_hide_head );
$qic_head_class = strpos( $quote_card_class, 'float-end' ) !== false ? 'qic-head px-3' : 'qic-head';
?>
<div class="quote-id-card <?php echo e($quote_card_class); ?>">
	<?php if ( ! $quote_card_hide_head ) : ?>
	<div class="<?php echo e($qic_head_class); ?>">Your Quote ID</div>
	<?php endif; ?>
	<div class="qic-row">
		<div class="qic-body btn-copy-link" data-id="<?php echo e($quote_id_dom_id); ?>">
			<div id="<?php echo e($quote_id_dom_id); ?>"><?php echo e($quote_id_val); ?></div>
		</div>
		<?php if ( $quote_share_url !== '' ) : ?>
		<button type="button" class="fc-copy-quote-link" data-copy-url="<?php echo e($quote_share_url); ?>" title="Copy quote link" aria-label="Copy quote link">
			<i class="fa-solid fa-link" aria-hidden="true"></i>
			<span class="fc-copy-quote-link__label">Copy Link</span>
		</button>
		<?php endif; ?>
	</div>
</div>
