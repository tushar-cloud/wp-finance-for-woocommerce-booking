<?php defined( 'ABSPATH' ) || exit;

if ( class_exists('WC_Booking_Data_Store') ) {
	$booking_ids = WC_Booking_Data_Store::get_booking_ids_from_order_item_id( $item_id );
} else {
	$booking_ids = [];
}
$product = $item->get_product();
?>

<tr>
	<td>
		<?php
		echo '<p>' . wp_kses_post( $item->get_name() ) . '</p>';

		if ( $show_booking && !empty($booking_ids) ) {
			foreach ( $booking_ids as $booking_id ) {
				echo '<p><strong>' . esc_html__( 'Booking:', 'wpfinance' ) . '</strong> ' . esc_html( '#' . (string) $booking->get_id() ) . '</p>';
			}
		}

		if ( $show_sku && $product && $product->get_sku() ) {
			echo '<p><strong>' . esc_html__( 'SKU:', 'wpfinance' ) . '</strong> ' . esc_html( $product->get_sku() ) . '</p>';
		}

		if ( $show_variation && $item->get_variation_id() ) {
			echo '<p><strong>' . esc_html__( 'Variation ID:', 'wpfinance' ) . '</strong> ';
			if ( 'product_variation' === get_post_type( $item->get_variation_id() ) ) {
				echo esc_html( $item->get_variation_id() );
			} else {
				printf( esc_html__( '%s (No longer exists)', 'wpfinance' ), esc_html( $item->get_variation_id() ) );
			}
			echo '</p>';
		} ?>
	</td>

	<td>
		<?php echo wc_price( $order->get_item_subtotal( $item, false, true ), array( 'currency' => $order->get_currency() ) );
		?>
	</td>
	<td>
		<?php
		echo '<small class="times">&times;</small> ' . esc_html( $item->get_quantity() );

		$refunded_qty = $order->get_qty_refunded_for_item( $item_id );

		if ( $refunded_qty ) {
			echo '<small class="refunded">-' . esc_html( $refunded_qty * -1 ) . '</small>';
		}
		?>
	</td>

	<td>
		<?php if ( $item->get_subtotal() !== $item->get_total() ) {
			echo wc_price( wc_format_decimal( $item->get_subtotal() - $item->get_total(), '' ), array( 'currency' => $order->get_currency() ) );
		} else {
			echo '&ndash;'; 
		} ?>
	</td>

	<?php
	$tax_data = wc_tax_enabled() ? $item->get_taxes() : false;

	if ( $tax_data ) {
		foreach ( $order_taxes as $tax_item ) {
			$tax_item_id       = $tax_item->get_rate_id();
			$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
			$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';

			if ( '' !== $tax_item_subtotal ) {
				$round_at_subtotal = 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
				$tax_item_total    = wc_round_tax_total( $tax_item_total, $round_at_subtotal ? wc_get_rounding_precision() : null );
				$tax_item_subtotal = wc_round_tax_total( $tax_item_subtotal, $round_at_subtotal ? wc_get_rounding_precision() : null );
			}
			?>
			<td>
				<?php
				if ( '' !== $tax_item_total ) {
					echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $order->get_currency() ) );
				} else {
					echo '&ndash;';
				}

				$refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id );

				if ( $refunded ) {
					echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_currency() ) ) . '</small>';
				}
				?>
			</td>
			<?php
		}
	} ?>

	<td>
		<?php echo wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) );

		$refunded = $order->get_total_refunded_for_item( $item_id );
		if ( $refunded ) {
			echo  '<p class="refund">' . sprintf( __('Refunded &mdash; %s', 'wpfinance'), wc_price( $refunded, array( 'currency' => $order->get_currency() ) )) . '</p>';
		} ?>
	</td>
</tr>
