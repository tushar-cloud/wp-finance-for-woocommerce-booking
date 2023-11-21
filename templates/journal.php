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
if ( ! $invoices || ! $start_date || ! $end_date ) {
	return;
} ?>

<div class="journal">
	<div class="journal-head">
		<table>
			<tr>
				<td class="logo">
					<img src="<?php echo $logo_url; ?>" height="35" width="auto" style="margin-bottom: 10px;">
					<h2><?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)); ?></h2>
				</td>
			</tr>
		</table>
	</div>
	<div class="journal-body">
		<table class="orders">
			<thead>
				<tr>
					<td><?php esc_html_e( 'Invoice No.', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Order No.', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Invoice Date', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Customer Info', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Total Before Vat', 'wpfinance' ); ?></td>
				</tr>
			</thead>
			<tbody>
				<?php 
				$sum_before_tax = [];
				$sum_after_tax = [];
				foreach ( $invoices as $invoice ) {
					if ( isset($invoice['order_id']) ) {
						$order_id = (int) $invoice['order_id'];
					}

					$order = wc_get_order( $invoice['order_id'] );
					if ( !$order ){
						continue;
					}

					$total_tax = 0;
					if ( wc_tax_enabled() ) {
						foreach ( $order->get_tax_totals() as $code => $tax_total ) {
							$total_tax += $tax_total->amount;
						}
					}

					$total_inc_tax = 0;
					foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
						$total_inc_tax += $item->get_total();
					}
					$total_with_tax = $order->get_total();
					$currency = $order->get_currency();

					if ( isset($sum_before_tax[$currency]) ) {
						$sum_before_tax[$currency] += $total_inc_tax;
					} else {
						$sum_before_tax[$currency] = $total_inc_tax;
					}

					$billing_address = str_replace(['<br />', '<br/>', '</br>', '</ br>'], ', ', $order->get_formatted_billing_address());

					$shipping_address = str_replace(['<br />', '<br/>', '</br>', '</ br>'], ', ', $order->get_formatted_shipping_address());

					?>
					<tr>
						<td>
							<?php echo $invoice['ID']; ?>
						</td>
						<td>
							<?php echo $order_id; ?>
						</td>
						<td>
							<?php echo date('d/m/Y', strtotime($invoice['date'])); ?>
						</td>
						<td width="50%">
							<?php 
							if ( $billing_address ) {
								echo $billing_address;
							} else {
								echo $shipping_address;
							} ?>
						</td>
						<td>
							<?php echo wc_price( $total_inc_tax, array( 'currency' => $order->get_currency() ) ); ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<td><?php esc_html_e( 'Total', 'wpfinance' ); ?></td>
					<td></td>
					<td></td>
					<td></td>
					<td>
						<?php 
						$html = '';
						foreach ($sum_before_tax as $currency => $price) {
							$html .= wc_price( $price, array( 'currency' => $currency ) ) . ', ';
						}
						echo rtrim($html, ', ');
						?>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>