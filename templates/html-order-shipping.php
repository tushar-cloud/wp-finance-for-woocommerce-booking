<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<tr>
	<td>
		<?php echo esc_html( $item->get_name() ? $item->get_name() : __( 'Shipping', 'wpfinance' ) ); ?>
		
		<?php $hidden_order_itemmeta = apply_filters(
			'woocommerce_hidden_order_itemmeta',
			array(
				'_qty',
				'_tax_class',
				'_product_id',
				'_variation_id',
				'_line_subtotal',
				'_line_subtotal_tax',
				'_line_total',
				'_line_tax',
				'method_id',
				'cost',
				'_reduced_stock',
			)
		); 

		if ( $meta_data = $item->get_formatted_meta_data( '' ) ) : ?>
			<table cellspacing="0" class="display_meta">
				<?php
				foreach ( $meta_data as $meta_id => $meta ) :
					if ( in_array( $meta->key, $hidden_order_itemmeta, true ) ) {
						continue;
					}
					?>
					<tr>
						<td><?php echo wp_kses_post( $meta->display_key ); ?>:</td>
						<td><?php echo wp_kses_post( force_balance_tags( $meta->display_value ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
	</td>

	<td>&nbsp;</td>
	<td>&nbsp;</td>

	<td>
		<?php
		echo wp_kses_post( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) );
		$refunded = $order->get_total_refunded_for_item( $item_id, 'shipping' );
		if ( $refunded ) {
			echo wp_kses_post( '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_currency() ) ) . '</small>' );
		} ?>
	</td>

	<td>&nbsp;</td>

	<?php
	$tax_data = $item->get_taxes();
	if ( $tax_data && wc_tax_enabled() ) {
		foreach ( $order_taxes as $tax_item ) {
			$tax_item_id    = $tax_item->get_rate_id();
			$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
			?>
			<td>
				<?php
				echo wp_kses_post( ( '' !== $tax_item_total ) ? wc_price( $tax_item_total, array( 'currency' => $order->get_currency() ) ) : '&ndash;' );
				$refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id, 'shipping' );
				if ( $refunded ) {
					echo wp_kses_post( '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_currency() ) ) . '</small>' );
				} ?>
			</td>
			<?php
		}
	} ?>
</tr>
