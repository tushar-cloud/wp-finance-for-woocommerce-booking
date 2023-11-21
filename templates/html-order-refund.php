<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$who_refunded = new WP_User( $refund->get_refunded_by() );
?>
<tr>
	<td colspan="2">
		<?php
		if ( $who_refunded->exists() ) {
			printf(
				esc_html__( 'Refund #%1$s - %2$s by %3$s', 'wpfinance' ),
				esc_html( $refund->get_id() ),
				esc_html( wc_format_datetime( $refund->get_date_created(), get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) ) ),
				sprintf(
					'<abbr class="refund_by" title="%1$s">%2$s</abbr>',
					sprintf( esc_attr__( 'ID: %d', 'wpfinance' ), absint( $who_refunded->ID ) ),
					esc_html( $who_refunded->display_name )
				)
			);
		} else {
			printf(
				esc_html__( 'Refund #%1$s - %2$s', 'wpfinance' ),
				esc_html( $refund->get_id() ),
				esc_html( wc_format_datetime( $refund->get_date_created(), get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) ) )
			);
		}
		?>
		<?php if ( $refund->get_reason() ) : ?>
			<p class="description"><?php echo esc_html( $refund->get_reason() ); ?></p>
		<?php endif; ?>
	</td>

	<td>&nbsp;</td>
	<td>&nbsp;</td>

	<?php
	if ( wc_tax_enabled() ) :
		$total_taxes = count( $order_taxes ); ?>
		<?php for ( $i = 0;  $i < $total_taxes; $i++ ) : ?>
			<td>&nbsp;</td>
		<?php endfor; ?>
	<?php endif; ?>

	<td>
		<p class="refund">
			<?php echo wp_kses_post(
				wc_price( '-' . $refund->get_amount(), array( 'currency' => $refund->get_currency() ) )
			); ?>
		</p>
	</td>
</tr>
