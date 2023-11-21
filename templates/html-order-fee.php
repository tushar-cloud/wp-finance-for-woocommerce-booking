<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<tr>
	<td>
		<?php echo esc_html( $item->get_name() ? $item->get_name() : __( 'Fee', 'wpfinance' ) ); ?>
	</td>

	<?php do_action( 'woocommerce_admin_order_item_values', null, $item, absint( $item_id ) ); ?>

	<td>&nbsp;</td>
	<td>&nbsp;</td>

	<td>
		<?php
		echo wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) );

		if ( $refunded = $order->get_total_refunded_for_item( $item_id, 'fee' ) ) {
			echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_currency() ) ) . '</small>';
		}
		?>
	</td>

	<td>&nbsp;</td>

	<?php
	if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) {
		foreach ( $order_taxes as $tax_item ) {
			$tax_item_id    = $tax_item->get_rate_id();
			$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
			?>
			<td>
				<?php
				echo ( '' !== $tax_item_total ) ? wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $order->get_currency() ) ) : '&ndash;';

				if ( $refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id, 'fee' ) ) {
					echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_currency() ) ) . '</small>';
				}
				?>
			</td>
			<?php
		}
	} ?>
</tr>