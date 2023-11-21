<?php
/**
 * The template for Sale report
 *
 * This template can be overridden by copying it to yourtheme/wpfinance/sale.php
 *
 * @package finance-for-wc-booking\templates
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

// Ensure visibility.
if ( ! $report_data || ! $start_date || ! $end_date ) {
	return;
}

$sub_without_tax = [];
$sum_tax = [];
$sum_stripe_fee = [];
$sum_stripe_net = [];
?>

<div class="sale">
	<div class="sale-head">
		<table>
			<tr>
				<td class="logo">
					<img src="<?php echo $logo_url; ?>" height="35" width="auto" style="margin-bottom: 10px;">
					<h2><?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)); ?></h2>
				</td>
			</tr>
		</table>
	</div>
	<div class="sale-body">
		<table class="orders">
			<thead>
				<tr>
					<td><?php esc_html_e( 'Order No.', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Bookable Product', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Discount', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Accessories Non-Vatable', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Accessories Vatable', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Prepayout', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Vat', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Stripe Fee', 'wpfinance' ); ?></td>
					<td><?php esc_html_e( 'Stripe', 'wpfinance' ); ?></td>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($report_data as $sale) :
					if ( !isset($sale['order_id'], $sale['currency']) ) {
						continue;
					} ?>
					<tr>
						<td>
							<?php echo $sale['order_id']; ?>
						</td>
						<td>
							<p><?php echo isset($sale['bookable_products']) ? implode( ', ', $sale['bookable_products'] ) : '&ndash;'; ?></p>
							<?php echo ( isset($sale['bookable_subtotal']) && $sale['bookable_subtotal'] > 0 ) ? wc_price( $sale['bookable_subtotal'], array( 'currency' => $sale['currency'] ) ) : '&ndash;'; ?>
						</td>
						<td>
							<?php echo ( isset($sale['discount']) && $sale['discount'] > 0 ) ? wc_price( $sale['discount'], array( 'currency' => $sale['currency'] ) ) : '&ndash;'; ?>
						</td>
						<td>
							<?php echo ( isset($sale['non_taxable']) && $sale['non_taxable'] > 0 ) ? wc_price( $sale['non_taxable'], array( 'currency' => $sale['currency'] ) ) : '&ndash;'; ?>
						</td>
						<td>
							<?php echo ( isset($sale['taxable']) && $sale['taxable'] > 0 ) ? wc_price( $sale['taxable'], array( 'currency' => $sale['currency'] ) ) : '&ndash;'; ?>
						</td>
						<td>
							<?php if ( isset($sale['total_with_tax']) && $sale['total_with_tax'] > 0 ) {
								echo wc_price( $sale['total_with_tax'], array( 'currency' => $sale['currency'] ) );

								if ( isset($sub_without_tax[$sale['currency']]) ) {
									$sub_without_tax[$sale['currency']] += $sale['total_with_tax'];
								} else {
									$sub_without_tax[$sale['currency']] = $sale['total_with_tax'];
								}
							} else {
								echo '&ndash;';
							} ?>
						</td>
						<td>
							<?php if ( isset($sale['total_tax']) && $sale['total_tax'] > 0 ) {
								echo wc_price( $sale['total_tax'], array( 'currency' => $sale['currency'] ) );

								if ( isset($sum_tax[$sale['currency']]) ) {
									$sum_tax[$sale['currency']] += $sale['total_tax'];
								} else {
									$sum_tax[$sale['currency']] = $sale['total_tax'];
								}
							} else {
								echo '&ndash;';
							} ?>
						</td>
						<td>
							<?php if ( isset($sale['stripe_fee']) && $sale['stripe_fee'] > 0 ) {
								echo wc_price( $sale['stripe_fee'], array( 'currency' => $sale['currency'] ) );

								if ( isset($sum_stripe_fee[$sale['currency']]) ) {
									$sum_stripe_fee[$sale['currency']] += $sale['stripe_fee'];
								} else {
									$sum_stripe_fee[$sale['currency']] = $sale['stripe_fee'];
								}
							} else {
								echo '&ndash;';
							} ?>
						</td>
						<td>
							<?php if ( isset($sale['stripe_net']) && $sale['stripe_net'] > 0 ) {
								echo wc_price( $sale['stripe_net'], array( 'currency' => $sale['currency'] ) );

								if ( isset($sum_stripe_net[$sale['currency']]) ) {
									$sum_stripe_net[$sale['currency']] += $sale['stripe_net'];
								} else {
									$sum_stripe_net[$sale['currency']] = $sale['stripe_net'];
								}
							} else {
								echo '&ndash;';
							} ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<td><?php esc_html_e( 'Total', 'wpfinance' ); ?></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td>
						<?php 
						$html = '';
						foreach ($sub_without_tax as $currency => $price) {
							$html .= wc_price( $price, array( 'currency' => $currency ) ) . ', ';
						}
						echo rtrim($html, ', ');
						?>
					</td>
					<td>
						<?php 
						$html = '';
						foreach ($sum_tax as $currency => $price) {
							$html .= wc_price( $price, array( 'currency' => $currency ) ) . ', ';
						}
						echo rtrim($html, ', ');
						?>
					</td>
					<td>
						<?php 
						$html = '';
						foreach ($sum_stripe_fee as $currency => $price) {
							$html .= wc_price( $price, array( 'currency' => $currency ) ) . ', ';
						}
						echo rtrim($html, ', ');
						?>
					</td>
					<td>
						<?php 
						$html = '';
						foreach ($sum_stripe_net as $currency => $price) {
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