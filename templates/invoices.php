<?php
/**
 * The template for Booking Invoice
 *
 * This template can be overridden by copying it to yourtheme/wpfinance/invoices.php
 *
 * @package finance-for-wc-booking\templates
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

// Ensure visibility.
if ( ! $booking || ! $order ) {
	return;
}

global $wpdb;

$payment_gateway     = wc_get_payment_gateway_by_order( $order );
$line_items          = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) );
$discounts           = $order->get_items( 'discount' );
$line_items_fee      = $order->get_items( 'fee' );
$line_items_shipping = $order->get_items( 'shipping' );

if ( wc_tax_enabled() ) {
	$order_taxes      = $order->get_taxes();
	$tax_classes      = WC_Tax::get_tax_classes();
	$classes_options  = wc_get_product_tax_class_options();
	$show_tax_columns = count( $order_taxes ) === 1;
} ?>

<div class="invoice">
	<div class="invoice-head">
		<table>
			<tr>
				<td class="logo" colspan="3">
					<img src="<?php echo $logo_url; ?>" height="35" width="auto">
				</td>
			</tr>
			<tr>
				<td>
					<h3><?php printf(__('Invoice %s', 'wpfinance'), '#'.$invoice_id); ?></h3>
					<p><?php printf(__('Invoice date: %s', 'wpfinance'), date('F j, Y', strtotime($time)) ); ?></p>
					<p><?php printf(__('Order date: %s', 'wpfinance'), $order->get_date_created()->format ('F j, Y')); ?></p>
					<p><?php printf(__('Order number: %d', 'wpfinance'), $order->get_id()); ?></p>
					<p><?php printf(__('Payment method: %s', 'wpfinance'), $order->get_payment_method_title()); ?></p>
				</td>
				<td>
					<h3><?php _e('Company Info', 'wpfinance'); ?></h3>
					<?php if ( isset($wpffwcb_option['company_info']) && $wpffwcb_option['company_info'] !== false ) {
						echo nl2br($wpffwcb_option['company_info']);
					} ?>
				</td>
				<td>
					<h3><?php _e('To', 'wpfinance'); ?></h3>
					<p><?php 
					if ( $billing_address = $order->get_formatted_billing_address() ) {
						echo $billing_address;
					} else {
						echo $order->get_formatted_shipping_address();
					} ?></p>
				</td>
			</tr>
		</table>
	</div>
	<div class="invoice-body">
		<table class="products">
			<thead>
				<tr>
					<td><?php esc_html_e( 'Item', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Cost', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Qty', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Discount', 'wpfinance' ); ?></td>
					<?php
					if ( ! empty( $order_taxes ) ) :
						foreach ( $order_taxes as $tax_id => $tax_item ) :
							$tax_class      = wc_get_tax_class_by_tax_id( $tax_item['rate_id'] );
							$tax_class_name = isset( $classes_options[ $tax_class ] ) ? $classes_options[ $tax_class ] : __( 'Tax', 'wpfinance' );
							$column_label   = ! empty( $tax_item['label'] ) ? $tax_item['label'] : __( 'Tax', 'wpfinance' );
							$column_tip = sprintf( esc_html__( '%1$s (%2$s)', 'wpfinance' ), $tax_item['name'], $tax_class_name );
							?>
							<td>
								<?php echo esc_attr( $column_label ); ?>
							</td>
							<?php
						endforeach;
					endif;
					?>
					<td><?php esc_html_e( 'Total', 'wpfinance' ); ?></td>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $line_items as $item_id => $item ) {
					include __DIR__ . '/html-order-item.php';
				} ?>
			</tbody>
			<tbody>
				<?php foreach ( $line_items_fee as $item_id => $item ) {
					include __DIR__ . '/html-order-fee.php';
				} ?>
			</tbody>
			<tbody>
				<?php
				$shipping_methods = WC()->shipping() ? WC()->shipping()->load_shipping_methods() : array();
				foreach ( $line_items_shipping as $item_id => $item ) {
					include __DIR__ . '/html-order-shipping.php';
				} ?>
			</tbody>
			<tfoot>
				<?php $refunds = $order->get_refunds();
				if ( $refunds ) {
					foreach ( $refunds as $refund ) {
						include __DIR__ . '/html-order-refund.php';
					}
				} ?>
			</tfoot>
		</table>

		<table class="shipping-cost">
			<tr>
				<td>
				<?php $coupons = $order->get_items( 'coupon' );
					if ( $coupons ) : ?>
						<strong><?php esc_html_e( 'Coupon(s)', 'wpfinance' ); ?></strong>
						<ol>
							<?php
							foreach ( $coupons as $item_id => $item ) :
								$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1;", $item->get_code() ) ); ?>
								<li>
									<?php echo ( $post_id ) ? esc_html( $item->get_code() ) : esc_html( $item->get_code() ); ?>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</td>
				<td>
					<table class="order-detials">
						<thead>
							<tr class="firstrow">
								<td><?php esc_html_e( 'Items Subtotal:', 'wpfinance' ); ?></td>
								<td></td>
								<td><?php echo wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ); ?></td>
							</tr>
							<?php if ( 0 < $order->get_total_discount() ) : ?>
								<tr>
									<td><?php esc_html_e( 'Coupon(s):', 'wpfinance' ); ?></td>
									<td></td>
									<td>-
										<?php echo wc_price( $order->get_total_discount(), array( 'currency' => $order->get_currency() ) ); ?>
									</td>
								</tr>
							<?php endif; ?>
							<?php if ( 0 < $order->get_total_fees() ) : ?>
								<tr>
									<td><?php esc_html_e( 'Fees:', 'wpfinance' ); ?></td>
									<td></td>
									<td>
										<?php echo wc_price( $order->get_total_fees(), array( 'currency' => $order->get_currency() ) ); ?>
									</td>
								</tr>
							<?php endif; ?>
							<?php if ( $order->get_shipping_methods() ) : ?>
								<tr>
									<td><?php esc_html_e( 'Shipping:', 'wpfinance' ); ?></td>
									<td></td>
									<td>
										<?php echo wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ); ?>
									</td>
								</tr>
							<?php endif; ?>
							<?php if ( wc_tax_enabled() ) : ?>
								<?php foreach ( $order->get_tax_totals() as $code => $tax_total ) : ?>
									<tr>
										<td><?php echo esc_html( $tax_total->label ); ?>:</td>
										<td></td>
										<td>
											<?php echo wc_price( $tax_total->amount, array( 'currency' => $order->get_currency() ) ); ?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
							<tr>
								<td><?php esc_html_e( 'Order Total', 'wpfinance' ); ?>:</td>
								<td></td>
								<td><?php echo wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ); ?></td>
							</tr>
						</tbody>

						<?php if ( in_array( $order->get_status(), array( 'processing', 'completed', 'refunded' ), true ) && ! empty( $order->get_date_paid() ) ) : ?>
						<tbody>
							<tr class="firstrow">
								<td><?php esc_html_e( 'Paid', 'wpfinance' ); ?>: <br /></td>
								<td></td>
								<td>
									<?php echo wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ); ?>
								</td>
							</tr>
							<tr>
								<td>
									<span>
									<?php
									if ( $order->get_payment_method_title() ) {
										echo esc_html( sprintf( __( '%1$s via %2$s', 'wpfinance' ), $order->get_date_paid()->date_i18n( get_option( 'date_format' ) ), $order->get_payment_method_title() ) );
									} else {
										echo esc_html( $order->get_date_paid()->date_i18n( get_option( 'date_format' ) ) );
									}
									?>
								</td>
								<td></td>
							</tr>
						</tbody>
						<?php endif; ?>

						<?php if ( $order->get_total_refunded() ) : ?>
						<tbody>
							<tr class="firstrow">
								<td><?php esc_html_e( 'Refunded', 'wpfinance' ); ?>:</td>
								<td></td>
								<td>-<?php echo wc_price( $order->get_total_refunded(), array( 'currency' => $order->get_currency() ) ); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Net Payment', 'wpfinance' ); ?>:</td>
								<td></td>
								<td>
								<?php echo wc_price( $order->get_total() - $order->get_total_refunded(), array( 'currency' => $order->get_currency() ) ); ?>
								</td>
							</tr>
						</tbody>
						<?php endif; ?>
					</table>
				</td>
			</tr>
		</table>
	</div>
	<div class="invoice-footer">
		<table>
			<tr>
				<td>
					<?php if ( isset($wpffwcb_option['left_info']) && $wpffwcb_option['left_info'] !== false ) {
						echo nl2br($wpffwcb_option['left_info']);
					} ?>
				</td>
				<td>
					<?php if ( isset($wpffwcb_option['right_info']) && $wpffwcb_option['right_info'] !== false ) {
						echo nl2br($wpffwcb_option['right_info']);
					} ?>
				</td>
			</tr>
		</table>
	</div>
</div>