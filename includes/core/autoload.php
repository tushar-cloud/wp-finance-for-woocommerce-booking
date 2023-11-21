<?php if ( ! defined( 'ABSPATH' ) ) exit;

class WPFFWCB_init {
	protected static $instance = null;

	public static function instance() {
		return null == self::$instance ? new self : self::$instance;
	}

	public function __construct() {
		register_activation_hook( WPFFWCB_FILE, array( $this, 'install' ) );
		register_deactivation_hook( WPFFWCB_FILE, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_plugin' ) );
		add_filter( 'plugin_action_links_' . plugin_basename(WPFFWCB_FILE), array( $this, 'link_in_plugin' ), 10, 1 );
		add_action( 'init', array( $this, 'actions' ) );

		/**
		* Inportant: do not overwrite/edit those hooks
		* @since 1.1
		**/
		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 4 );
		add_action( 'woocommerce_new_booking', array( $this, 'save_wc_bookings' ), 10, 1 );
		add_action( 'woocommerce_booking_process_meta', array( $this, 'save_wc_bookings' ), 10, 1 );
		add_action( 'wpffwcb_woocommerce_booking', array( $this, 'save_wc_bookings' ), 10, 1 );
		add_action( 'wcb_start_event', array( $this, 'wc_booking_start' ), 10, 1 );
		add_action( 'wpfinance_wc_booking_start', array( $this, 'generate_invoices' ), 10, 3 );

		add_action( 'wpfinance_daily_event', array( $this, 'action_daily_event' ), 10, 2 );
		add_action( 'update_option_gmt_offset', array( $this, 'update_timezone' ), 10, 2 );
		add_action( 'update_option_timezone_string', array( $this, 'update_timezone' ), 10, 2 );
		add_action( 'wpfinance_daily_event_start', array( $this, 'start_daily_event_action' ), 10, 1 );
	}

	/**
	 * Get Booking Schedule events
	 * @since 1.8
	 */
	public function get_booking_start_events() {
		if ( !function_exists( '_get_cron_array' ) ) {
			require_once ABSPATH . WPINC . '/cron.php'; 
		}
		$crons  = _get_cron_array();
		$events = array();

		if ( !empty( $crons ) ) {
			foreach ( $crons as $time => $cron ) {
				foreach ( $cron as $hook => $dings ) {
					foreach ( $dings as $sig => $data ) {
						if ( $hook == "wcb_start_event" && isset($data['args'][0]) ) {
							$events[] = $data['args'][0];
						}
					}
				}
			}
		}

		return $events;
	}

	/**
	 * Returns a display value for a UTC offset.
	 * @source https://wordpress.org/plugins/wp-crontrol/
	 * @since 1.1
	 */
	public static function get_utc_offset() {
		$offset = get_option( 'gmt_offset', 0 );

		if ( empty( $offset ) ) {
			return 'UTC';
		}

		if ( 0 <= $offset ) {
			$formatted_offset = '+' . (string) $offset;
		} else {
			$formatted_offset = (string) $offset;
		}
		$formatted_offset = str_replace(
			array( '.25', '.5', '.75' ),
			array( ':15', ':30', ':45' ),
			$formatted_offset
		);
		return 'UTC' . $formatted_offset;
	}

	/**
	 * Get the display name for the site's timezone.
	 * @source https://wordpress.org/plugins/wp-crontrol/
	 * @since 1.1
	 */
	public static function get_timezone_name() {
		$timezone_string = get_option( 'timezone_string', '' );
		$gmt_offset      = get_option( 'gmt_offset', 0 );

		if ( 'UTC' === $timezone_string || ( empty( $gmt_offset ) && empty( $timezone_string ) ) ) {
			return 'UTC';
		}

		if ( '' === $timezone_string ) {
			return self::get_utc_offset();
		}

		return sprintf(
			'%s, %s',
			str_replace( '_', ' ', $timezone_string ),
			self::get_utc_offset()
		);
	}

	/**
	* Show setting
	* @since 1.1
	**/
	public static function get_settings($name = ''){
		$save_data = get_option( "wpffwcb_option", array() );
		$output = [];
		if ( $save_data && is_array($save_data) ) {
			foreach ($save_data as $key => $value) {
				if ( ( isset($value['enable'], $value['value']) && $value['enable'] == 1) || ( isset($value['value']) && !isset($value['enable']) ) ) {
					$output[$key] = $value['value'];
				} else {
					$output[$key] = false;
				}
			}
		}
		if ( !empty($name) ) {
			if ( isset($output[$name]) ) {
				return $output[$name];
			} else {
				return false;
			}
		}
		return $output;
	}

	/**
	* Init action
	* @since 1.1
	**/
	public function actions() {
		// Schedule hooks
		$datetime = current_datetime();
		if ( ! $datetime->getOffset() ) {
			$time_offset = 0;
		} else {
			$time_offset = $datetime->getOffset();
		}

		$next_datetime = new DateTime( 'tomorrow', wp_timezone() );
		$next_date = (int) $next_datetime->format('U');
		$universal = (int) ($next_date + $time_offset);
		$args = array( $universal, 'future' );
		if ( ! wp_next_scheduled( 'wpfinance_daily_event', $args ) ) {
			wp_schedule_single_event( $next_date, 'wpfinance_daily_event', $args );
		}

		if ( isset($_GET['wpffwcb_invoice'], $_GET['invoice_id']) ) {
			// Actions Invoice

			if ( ! is_user_logged_in() ) {
				wp_die( 'Access denied' );
			}

			$query = $_GET['wpffwcb_invoice'];
			$id = $_GET['invoice_id'];
			global $wpdb;
			$table_name = $wpdb->prefix . WPFFWCB_TABLE;

			if ( $query == 'create' ) {
				$nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
				if ( ! wp_verify_nonce( $nonce, '__create_inv' ) ) {
					wp_die( 'Invalid request.' );
				}

				$data = $wpdb->get_row("SELECT * FROM $table_name WHERE `ID` = '$id'");
				if ( isset($data->booking_id, $data->ID) && !empty($data->booking_id) ) {
					$time = date_i18n('Y-m-d H:i:s');
					$booking = get_wc_booking($data->booking_id);
					$invoice_data = WPFFWCB_generate::invoice($data->booking_id, $booking, $time, 'S', false);

					if ( $invoice_data ) {
						wp_redirect(admin_url('admin.php?page=wpffwcb_option&invoice=create'));
					} else {
						wp_die( 'Failed to generate Invoice.' );
					}
				} else {
					wp_die( 'Invalid request.' );
				}
			} else if ( $query == 'update' ) {
				$nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
				if ( ! wp_verify_nonce( $nonce, '__update_inv' ) ) {
					wp_die( 'Invalid request.' );
				}

				$data = $wpdb->get_row("SELECT * FROM $table_name WHERE `ID` = '$id'");
				if ( isset($data->booking_id, $data->ID) && !empty($data->booking_id) ) {
					$time = date_i18n('Y-m-d H:i:s');
					$booking = get_wc_booking($data->booking_id);
					$invoice_data = WPFFWCB_generate::invoice($data->booking_id, $booking, $time, 'S', false);

					if ( $invoice_data ) {
						wp_redirect(admin_url('admin.php?page=wpffwcb_option&invoice=update'));
					} else {
						wp_die( 'Failed to update Invoice' );
					}
				} else {
					wp_die( 'Invalid request.' );
				}
			} else if ( $query == 'view' ) {
				$nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
				if ( ! wp_verify_nonce( $nonce, '__view_inv' ) ) {
					wp_die( 'Invalid request.' );
				}

				$data = $wpdb->get_row("SELECT * FROM $table_name WHERE `ID` = '$id'");
				if ( isset($data->source, $data->ID) && !empty($data->source) ) {
					$source = maybe_unserialize($data->source);
					if ( isset($source['path'], $source['url'], $source['basename']) ) {
						if ( ! file_exists($source['path']) ) {
							wp_die( 'File doesn\'t exist. Try to Update or Create pdf.' );
						}

						header( 'Content-type: application/pdf' );
						header( 'Content-Disposition: inline; filename="' . basename( $source['path'] ) . '"' );
						header( 'Content-Transfer-Encoding: binary' );
						header( 'Content-Length: ' . filesize( $source['path'] ) );
						header( 'Accept-Ranges: bytes' );
						readfile( $source['path'] );
						exit;
					}
				} else {
                	wp_die( 'Invoice data not found' );
                }
			} else if ( $query == 'backup' ) {
				$nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
				if ( ! wp_verify_nonce( $nonce, '__backup_inv' ) ) {
					wp_die( 'Invalid request.' );
				}

				$data = $wpdb->get_row("SELECT * FROM $table_name WHERE `ID` = '$id'");
				if ( isset($data->source, $data->ID) && !empty($data->source) ) {
					$invoice_data = maybe_unserialize($data->source);
					if ( isset($invoice_data['path'], $invoice_data['url'], $invoice_data['basename'], $invoice_data['sub_dir']) ) {
						if ( ! file_exists($invoice_data['path']) ) {
							wp_die( 'File doesn\'t exist. Try to Update or Create pdf.' );
						}
						$upload_data = Ffwcbook_dropbox::upload_file($invoice_data);

						if ( $upload_data && isset($upload_data['http_code'], $upload_data['response']) ) {
	                        if ( $upload_data['http_code'] == 200 ) {
	                            $up_data = [
	                                'is_backup' => 'success',
	                                'backup_date' => $upload_data['datetime'],
	                                'response' => $upload_data['response']
	                            ];
	                            $wpdb->update($table_name, $up_data, ['ID' => $data->ID]);

	                            wp_redirect(admin_url('admin.php?page=wpffwcb_option&invoice=backup'));
	                       	} else {
	                        	wp_die( 'Failed to Backup. ' . $upload_data['response'] );
	                        }
	                    } else {
	                    	wp_die( 'Failed to Backup. Server misconfiguration' );
	                    }
					} else {
                    	wp_die( 'Invoice data not found' );
                    }
				} else {
                	wp_die( 'Invoice data not found' );
                }
			}
		} 
		else if ( isset($_GET['wpffwcb_log'], $_GET['log_id'], $_GET['type']) && in_array($_GET['type'], ['invoice', 'journal', 'sale']) ) {
			// Actions log

			if ( ! is_user_logged_in() ) {
				wp_die( 'Access denied' );
			}

			$query = $_GET['wpffwcb_log'];
			$id = $_GET['log_id'];
			$type = $_GET['type'];
			global $wpdb;
			$table_name = $wpdb->prefix . WPFFWCB_TABLE;

			if ( $query == 'create' ) {
				$nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
				if ( ! wp_verify_nonce( $nonce, '__create_inv' ) ) {
					wp_die( 'Invalid request.' );
				}

				$data = WPFFWCB_generate::get_data($id);
				if ( isset($data->source, $data->ID) && !empty($data->source) ) {
					$source = maybe_unserialize($data->source);
					if ( is_array($source) ) {
						switch ($type) {
							case 'journal':
								if ( isset($source['start_date'], $source['end_date']) ) {
									$is_success = WPFFWCB_generate::invoice_journal($source['end_date'], $source['start_date'], 'S', false);
									if ( $is_success ) {
										wp_redirect(admin_url('admin.php?page=wpffwcb_log_report&journal=created'));
									} else {
										wp_die('Failed to generate Invoice Journal report');
									}
								} else {
									wp_die('Failed to generate Invoice Journal report');
								}
							break;

							case 'sale':
								if ( isset($source['start_date'], $source['end_date']) ) {
									$is_success = WPFFWCB_generate::sale_report($source['end_date'], $source['start_date'], 'S', false);
									if ( $is_success ) {
										wp_redirect(admin_url('admin.php?page=wpffwcb_log_report&sale=created'));
									} else {
										wp_die('Failed to generate Sale report');
									}
								} else {
									wp_die('Failed to generate Sale report');
								}
							break;
							
							case 'invoice':
							default:
								if ( isset($data->booking_id) && !empty($data->booking_id) ) {
									$time = date_i18n('Y-m-d H:i:s');
									$booking = get_wc_booking($data->booking_id);
									$is_success = WPFFWCB_generate::invoice($data->booking_id, $booking, $time, 'S', false);
									if ( $is_success ) {
										wp_redirect(admin_url('admin.php?page=wpffwcb_log_report&invoice=created'));
									} else {
										wp_die('Failed to generate Invoice report');
									}
								} else {
									wp_die('Failed to generate Invoice report');
								}
								break;
						}
					} else {
						wp_die('Invalid request');
					}
				}
			} else if ( $query == 'update' ) {
				$nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
				if ( ! wp_verify_nonce( $nonce, '__update_inv' ) ) {
					wp_die( 'Invalid request.' );
				}

				$data = WPFFWCB_generate::get_data($id);
				if ( isset($data->source, $data->ID) && !empty($data->source) ) {
					$source = maybe_unserialize($data->source);
					if ( is_array($source) ) {
						switch ($type) {
							case 'journal':
								if ( isset($source['start_date'], $source['end_date']) ) {
									$is_success = WPFFWCB_generate::invoice_journal($source['end_date'], $source['start_date'], 'S', false);
									if ( $is_success ) {
										wp_redirect(admin_url('admin.php?page=wpffwcb_log_report&journal=updated'));
									} else {
										wp_die('Failed to update Invoice Journal report');
									}
								} else {
									wp_die('Failed to update Invoice Journal report');
								}
							break;

							case 'sale':
								if ( isset($source['start_date'], $source['end_date']) ) {
									$is_success = WPFFWCB_generate::sale_report($source['end_date'], $source['start_date'], 'S', false);
									if ( $is_success ) {
										wp_redirect(admin_url('admin.php?page=wpffwcb_log_report&sale=updated'));
									} else {
										wp_die('Failed to update Sale report');
									}
								} else {
									wp_die('Failed to update Sale report');
								}
							break;
							
							case 'invoice':
							default:
								if ( isset($data->booking_id) && !empty($data->booking_id) ) {
									$booking = get_wc_booking($data->booking_id);
									$is_success = WPFFWCB_generate::invoice($data->booking_id, $booking, '', 'S', false);
									if ( $is_success ) {
										wp_redirect(admin_url('admin.php?page=wpffwcb_log_report&invoice=updated'));
									} else {
										wp_die('Failed to update Invoice report');
									}
								} else {
									wp_die('Failed to update Invoice report');
								}
								break;
						}
					} else {
						wp_die('Invalid request');
					}
				}
			} else if ( $query == 'view' ) {
				$nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
				if ( ! wp_verify_nonce( $nonce, '__view_inv' ) ) {
					wp_die( 'Invalid request.' );
				}

				$data = $wpdb->get_row("SELECT * FROM $table_name WHERE `ID` = '$id'");
				if ( isset($data->source, $data->ID) && !empty($data->source) ) {
					$source = maybe_unserialize($data->source);
					if ( isset($source['path'], $source['url'], $source['basename']) ) {
						if ( ! file_exists($source['path']) ) {
							wp_die( 'File doesn\'t exist. Try to Update or Create pdf.' );
						}

						header( 'Content-type: application/pdf' );
						header( 'Content-Disposition: inline; filename="' . basename( $source['path'] ) . '"' );
						header( 'Content-Transfer-Encoding: binary' );
						header( 'Content-Length: ' . filesize( $source['path'] ) );
						header( 'Accept-Ranges: bytes' );
						readfile( $source['path'] );
						exit;
					}
				} else {
                	wp_die( 'Invoice data not found' );
                }
			} else if ( $query == 'backup' ) {
				$nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
				if ( ! wp_verify_nonce( $nonce, '__backup_inv' ) ) {
					wp_die( 'Invalid request.' );
				}

				$data = $wpdb->get_row("SELECT * FROM $table_name WHERE `ID` = '$id'");
				if ( isset($data->source, $data->ID) && !empty($data->source) ) {
					$report_data = maybe_unserialize($data->source);
					if ( isset($report_data['path'], $report_data['url'], $report_data['basename'], $report_data['sub_dir']) ) {
						if ( ! file_exists($report_data['path']) ) {
							wp_die( 'File doesn\'t exist. Try to Update or Create report.' );
						}

						$upload_data = [];
						switch ($type) {
							case 'journal':
								$upload_data = Ffwcbook_dropbox::upload_file($report_data, 'Invoice-Journal');
							break;

							case 'sale':
								$upload_data = Ffwcbook_dropbox::upload_file($report_data, 'Sale-Reports');
							break;
							
							case 'invoice':
							default:
								$upload_data = Ffwcbook_dropbox::upload_file($report_data, 'Invoice');
							break;
						}

						if ( $upload_data && isset($upload_data['http_code'], $upload_data['response']) ) {
	                        if ( $upload_data['http_code'] == 200 ) {
	                            $up_data = [
	                                'is_backup' => 'success',
	                                'backup_date' => $upload_data['datetime'],
	                                'response' => $upload_data['response']
	                            ];
	                            $wpdb->update($table_name, $up_data, ['ID' => $data->ID]);

	                            wp_redirect(admin_url('admin.php?page=wpffwcb_log_report&report=backup'));
	                       	} else {
	                        	wp_die( 'Failed to Backup. ' . $upload_data['response'] );
	                        }
	                    } else {
	                    	wp_die( 'Failed to Backup. Server misconfiguration' );
	                    }
					} else {
                    	wp_die( 'Invoice data not found' );
                    }
				} else {
                	wp_die( 'Invoice data not found' );
                }
			}
		}
	}

	/**
	* Woocommerce get Sale report
	* Default last week data
	* @since 1.1
	**/
	public static function sale_report_data($end_date, $start_date, $status = '', $limit = -1) {
		$current_unix = date_i18n('U');
		if ( empty($start_date) || ! self::validateDate($start_date, 'Y-m-d') ) {
			$start_date = date("Y-m-d", strtotime("-1 week", $current_unix));
		}
		if ( empty($end_date) || ! self::validateDate($end_date, 'Y-m-d') ) {
			$end_date = date("Y-m-d", $current_unix);
		}

		if ( $start_date == $end_date ) {
			$date_created = $end_date;
		} else {
			$date_created = $start_date . '...' . $end_date;
		}

		$sale_setting = self::get_settings('sale_order_status');
		if ( empty($status) ) {
			$status = ( is_array($sale_setting) && !empty($sale_setting) ) ? $sale_setting : array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed');
		}

		$orders = wc_get_orders( array(
       		'type' => 'shop_order',
		    'limit' => $limit,
		    'orderby' => 'date',
		    'order' => 'DESC',
		    'return' => 'objects',
		    'status' => $status,
		    'date_created' => $date_created
		) );
		$sale_data = [];
		if ( $orders ) {
			foreach ($orders as $order) {
				if ( ! $order && is_object($order) ) {
					continue;
				}

				$bookable_products = [];
				$bookable_total = 0;
				$bookable_subtotal = 0;
				$taxable = 0;
				$non_taxable = 0;
				$total_tax = 0;
				$total_inc_tax = 0;
					
				$bookings = WC_Booking_Data_Store::get_booking_ids_from_order_id( $order->get_id() );
				if ( $bookings ) {
					foreach ( $bookings as $booking_id ) {
						$booking = get_wc_booking( $booking_id );
						$product_id = $booking->get_product_id();
						$bookable_products[$product_id] = '';
					}
				}

				// Total tax
				if ( wc_tax_enabled() ) {
					foreach ( $order->get_tax_totals() as $code => $tax_total ) {
						$total_tax += $tax_total->amount;
					}
				}

				if ( wc_tax_enabled() ) {
					$order_taxes = $order->get_taxes();
				}

				foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
					$product_id = $item->get_product_id();
					$total = $item->get_total();
					$subtotal = $item->get_subtotal();
					// Total include Vat
					$total_inc_tax += $total;

					// Vat
					$tax_data = wc_tax_enabled() ? $item->get_taxes() : false;
					$is_tax = false;
					if ( $tax_data ) {
						foreach ( $order_taxes as $tax_item ) {
							$tax_item_id       = $tax_item->get_rate_id();
							$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
							$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';

							if ( '' !== $tax_item_subtotal ) {
								$round_at_subtotal = 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
								$tax_item_total    = wc_round_tax_total( $tax_item_total, $round_at_subtotal ? wc_get_rounding_precision() : null );
								$is_tax = true;
							}
						}
					}

					if ( array_key_exists($product_id, $bookable_products) ) {
						//Bookable
						 $bookable_total += $total;
						 $bookable_subtotal += $subtotal;
						 $bookable_products[$product_id] = $item->get_name();
					} else {
						// Total non-taxable (Without bookable)
						if ( $is_tax ) {
							$taxable += $total;
						} else {
							$non_taxable += $total;
						}

					}
				}

				$sale_data[] = array(
					'order_id' => $order->get_id(),
					'currency' => $order->get_currency(),
					'bookable_products' => $bookable_products,
					'bookable_total' => $bookable_total,
					'total_with_tax' => $total_inc_tax,
					'bookable_subtotal' => $bookable_subtotal,
					'discount' => $order->get_total_discount(),
					'non_taxable' => $non_taxable,
					'taxable' => $taxable,
					'subtotal' => $order->get_subtotal(),
					'total_tax' => $total_tax,
					'stripe_fee' => (float) $order->get_meta('_stripe_fee', true),
					'stripe_net' => (float) $order->get_meta('_stripe_net', true),
				);
			}
		}
		return $sale_data;
	}

	/**
	* Order status change action
	* @since v1.5
	*/
	public function order_status_changed( $order_id, $order_status_from, $order_status_to, $order ) {
		if ( class_exists('WC_Booking_Data_Store') ) {
			$booking_ids = WC_Booking_Data_Store::get_booking_ids_from_order_id($order_id);
			if ( is_array($booking_ids) && !empty($booking_ids) ) {
				foreach ( $booking_ids as $booking_id ) {
					$booking = get_wc_booking($booking_id);
					if ( $booking ) {
						do_action( 'wpffwcb_woocommerce_booking', $booking_id );
					}
				}
			}
		}
	}

	/**
	* Woocommerce booking on new/update
	* @since 1.1
	**/
	public function save_wc_bookings( $booking_id ) {
		$booking = get_wc_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$datetime = current_datetime();
		if ( ! $datetime->getOffset() ) {
			$time_offset = 0;
		} else {
			$time_offset = $datetime->getOffset();
		}

		if ( $booking->get_all_day() ) {
			$current_date = strtotime(date_i18n('Y-m-d H:i:s'));
			$booking_date = strtotime($booking->get_start_date('Y-m-d 00:00:00'));
			if ( $booking_date <= $current_date ) {
				$start_time = strtotime("+5 minutes", $current_date);
			} else {
				$start_time = $booking_date;
			}
		} else {
			$start_time = strtotime($booking->get_start_date('Y-m-d H:i:s', '', false));
		}
		
		if ( ! $start_time ) {
			return;
		}
		$start_time -= $time_offset; // Universal booking start

		wp_clear_scheduled_hook( 'wcb_start_event', array( $booking_id ) );
		if ( $start_time > date_i18n('U', false, true) ) {
			wp_schedule_single_event( $start_time, 'wcb_start_event', array( $booking_id ) );
		}
		return;
	}

	/**
	* Booking start date create trigger
	* @since 1.0
	**/
	public function wc_booking_start( $booking_id ) {
		$booking = get_wc_booking( $booking_id );

		if ( ! $booking ) {
			return;
		}

		$datetime = current_datetime();
		if ( ! $datetime->getOffset() ) {
			$time_offset = 0;
		} else {
			$time_offset = $datetime->getOffset();
		}

		$is_today = false;
		if ( $booking->get_all_day() ) {
			$current_date = strtotime(date_i18n('Y-m-d H:i:s'));
			$booking_date = strtotime($booking->get_start_date('Y-m-d 00:00:00'));
			if ( $booking_date <= $current_date ) {
				$start_time = strtotime("+5 minutes", $current_date);
				$is_today = true;
			} else {
				$start_time = $booking_date;
			}
		} else {
			$start_time = strtotime($booking->get_start_date('Y-m-d H:i:s', '', false));
		}

		if ( ! $start_time ) {
			return;
		}
		$start_time -= $time_offset; // Universal booking start

		if ( ! $is_today && ($start_time > date_i18n('U', false, true)) && (! wp_next_scheduled('wcb_start_event', array( $booking_id ))) ) {
			wp_schedule_single_event( $start_time, 'wcb_start_event', array( $booking_id ) );
			return;
		}

		do_action( 'wpfinance_wc_booking_start', $booking_id, $booking, date('Y-m-d H:i:s', $start_time) ); // trigger actual action
	}

	/**
	* Event change on change timezone
	* @since 1.1
	**/
	public function update_timezone($old_value, $value) {
		wp_unschedule_hook('wpfinance_daily_event');
		$datetime = current_datetime();
		if ( ! $datetime->getOffset() ) {
			$time_offset = 0;
		} else {
			$time_offset = $datetime->getOffset();
		}
		$next_datetime = new DateTime( 'tomorrow', wp_timezone() );
		$next_date = (int) $next_datetime->format('U');
		$universal = (int) ($next_date + $time_offset);
		if ( $universal > date_i18n('U') ) {
			wp_schedule_single_event( $next_date, 'wpfinance_daily_event', array( $universal, 'future' ) );
		}
	}

	/**
	* Daily event
	* @since 1.8
	**/
	public function action_daily_event($gmt_time, $type) {
		if ( ! is_int($gmt_time) ) {
			$gmt_time = (int) $gmt_time;
		}

		$datetime = current_datetime();
		if ( ! $datetime->getOffset() ) {
			$time_offset = 0;
		} else {
			$time_offset = $datetime->getOffset();
		}

		if ( $gmt_time > date_i18n('U') ) {
			$current_unix = ($gmt_time - $time_offset);
			wp_clear_scheduled_hook( 'wpfinance_daily_event', array( $current_unix, $type ) );
			wp_schedule_single_event( $current_unix, 'wpfinance_daily_event', array( $gmt_time, $type ) );
			return;
		}

		// Future event
		if ( $type == 'future' ) {
			$startDate = new DateTime( date('Y-m-d 00:00:00', $gmt_time), wp_timezone() );
			$endDate = new DateTime( date_i18n('Y-m-d 23:59:59'), wp_timezone() );

			while ($startDate <= $endDate) {
				// All day between two dates
				$every_day = (int) $startDate->format('U');
				if ( $every_day != $gmt_time ) {
			    	$universal = (int) ($every_day + $time_offset);

			    	if ( (! wp_next_scheduled('wpfinance_daily_event', array($universal, 'past')) ) || (! wp_next_scheduled('wpfinance_daily_event', array($universal, 'future')) ) ) {
			    		wp_schedule_single_event( $every_day, 'wpfinance_daily_event', array( $universal, 'past' ) );
			    	}
				}
			    $startDate->modify('+1 day');
			}
		}

		// Next Schedule Event
		$next_datetime = new DateTime( 'tomorrow', wp_timezone() );
		$next_date = (int) $next_datetime->format('U');
		$next_universal = (int) ($next_date + $time_offset);

		if ( ($next_universal > date_i18n('U')) && ! wp_next_scheduled( 'wpfinance_daily_event', array( $next_universal, 'future' ) ) ) {
			wp_schedule_single_event( $next_date, 'wpfinance_daily_event', array( $next_universal, 'future' ) );
		}

		// Action
		do_action( 'wpfinance_daily_event_start', $gmt_time ); // GMT strtotime
	}

	/**
	* Daily event start action
	* @since 1.8
	**/
	public function start_daily_event_action($gmt_time) {
		$end_date = date('Y-m-d 23:59:59', strtotime("-1 days", $gmt_time));
		$start_date = date('Y-m-d 00:00:00', strtotime("-1 days", $gmt_time));

		WPFFWCB_generate::sale_report($end_date, $start_date); // Generate Sale report Everyday
		if ( date('w', $gmt_time) == 1 ) {
			$w_start_date = date('Y-m-d 00:00:00', strtotime("-7 days", $gmt_time));
			WPFFWCB_generate::invoice_journal($end_date, $w_start_date); // Generate Invoice Journal on Monday
		}
	}

	/**
	* Generate Invoices
	* @since 1.1
	**/
	public function generate_invoices( $booking_id, $booking, $datetime ) {
		if ( ! $booking ) {
			return;
		}

		$order = $booking->get_order();
		if ( ! $order ) {
			return;
		}

		$settings = $this->get_settings();
		$booking_status = ( isset($settings['booking_status']) && is_array($settings['booking_status']) ) ? $settings['booking_status'] : [] ;
		$order_status = ( isset($settings['order_status']) && is_array($settings['order_status']) ) ? $settings['order_status'] : [] ;

		$is_booking = true;
		$is_order = true;
		if ( !empty($booking_status) ) {
			$is_booking = in_array($booking->get_status(), $booking_status) ? true : false;
		}
		if ( !empty($order_status) ) {
			$is_order = in_array($order->get_status(), $order_status) ? true : false;
		}

		if ( $is_booking && $is_order ) {
			WPFFWCB_generate::invoice( $booking_id, $booking, $datetime );
		}
	}

	/**
	 * Activate scheduled events. Runs on activation
	 * @since 1.1
	 */
	public function create_previous_booking_events() {
		global $wp_filter;
		$args = array(
			'post_type'		=> 'wc_booking',
			'fields'		=> 'ids',
			'post_status'	=> array('unpaid', 'pending-confirmation', 'confirmed', 'paid', 'complete'),
			'orderby'		=> 'meta_value',
			'order'			=> 'DESC',
			'meta_key'		=> '_booking_start',
			'meta_query'	=> array(
				array(
					'key'     => '_booking_start',
					'value'   => esc_sql( date('YmdHis', strtotime("+5 minutes", date_i18n('U'))) ),
					'compare' => '>'
				)
			),
			'posts_per_page' => -1,
		);
		if ( isset($wp_filter["wcb_start_event"]) ) {
			$args['post__not_in'] = $this->get_booking_start_events();
		}
		$book_query = new WP_Query($args);
		foreach ( $book_query->posts as $booking_id ) {
			do_action( 'wpffwcb_woocommerce_booking', $booking_id );
		}
	}

	/**
	* Valid datetime checker
	* @source https://stackoverflow.com/questions/19271381/
	* @since 1.1
	**/
	public static function validateDate($date, $format = 'Y-m-d') {
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) === $date;
	}

	/**
	 * @since 1.0
	 **/
	public function load_plugin() {
		if ( version_compare( get_option( 'wpffwcb_version' ), WPFFWCB_VER, '>=' ) ) {
			return;
		}

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . WPFFWCB_TABLE;

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`ID` int(16) unsigned NOT NULL AUTO_INCREMENT,
			`order_id` int(16) DEFAULT NULL,
			`booking_id` int(16) DEFAULT NULL,
			`date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			`view` VARCHAR(25) NOT NULL DEFAULT 'invoice',
			`source` longtext,
			`is_backup` VARCHAR(25) NOT NULL DEFAULT 'failed',
			`backup_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			`response` text NOT NULL DEFAULT '',
			PRIMARY KEY (`ID`)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		$wpdb->query("ALTER TABLE $table_name AUTO_INCREMENT = 125000");

		update_option( "wpffwcb_version", WPFFWCB_VER );
	}

	/**
	 * Show Menu link in activated plugin
	 *
	 * @since 1.0
	 **/
	public function link_in_plugin( $links ) {
		$settings_link = '<a href="'.admin_url('admin.php?page=wpffwcb_settings').'">' . __('Settings', 'wpfinance') . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Installation. Runs on activation
	 *
	 * @since 1.0
	 */
	public function install() {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', function() {
				echo '<div class="error"><p><strong>' . sprintf( esc_html__( '"WP Finance for Woocommerce Booking" requires WooCommerce to be installed and active. You can download %s here.', 'wpfinance' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
			});
			deactivate_plugins( plugin_basename( WPFFWCB_FILE ) );
			return;
		}
		if ( ! class_exists( 'WC_Bookings' ) ) {
			add_action( 'admin_notices', function() {
				echo '<div class="error"><p><strong>' . sprintf( esc_html__( '"WP Finance for Woocommerce Booking" requires WooCommerce Booking to be installed and active. You can purchase %s here.', 'wpfinance' ), '<a href="https://woocommerce.com/products/woocommerce-bookings/" target="_blank">WooCommerce Booking</a>' ) . '</strong></p></div>';
			});
			deactivate_plugins( plugin_basename( WPFFWCB_FILE ) );
			return;
		}

		$wp_upload_dir = wp_upload_dir();
		$upload_dir = trailingslashit($wp_upload_dir['basedir']) . WPFFWCB_DIRNAME;

		/**
		* Create Directories
		**/
		if ( ! is_dir($upload_dir) ) {
			wp_mkdir_p($upload_dir);
		}
		
		if ( is_dir($upload_dir) ) {
			$wpffwcb_invoice = $upload_dir . '/Invoice';
			$wpffwcb_invoice_journal = $upload_dir . '/Invoice-Journal';
			$wpffwcb_sale_reports = $upload_dir . '/Sale-Reports';
			if ( !is_dir($wpffwcb_invoice) ) {
				wp_mkdir_p( $wpffwcb_invoice );
			}
			if ( !is_dir($wpffwcb_invoice_journal) ) {
				wp_mkdir_p( $wpffwcb_invoice_journal );
			}
			if ( !is_dir($wpffwcb_sale_reports) ) {
				wp_mkdir_p( $wpffwcb_sale_reports );
			}
		}

		$this->create_previous_booking_events();


		/**
		* Daily schedule events
		**/
		$datetime = current_datetime();
		if ( ! $datetime->getOffset() ) {
			$time_offset = 0;
		} else {
			$time_offset = $datetime->getOffset();
		}
		$next_datetime = new DateTime( 'tomorrow', wp_timezone() );
		$next_date = (int) $next_datetime->format('U');
		$universal = (int) ($next_date + $time_offset);
		if ( $universal > date_i18n('U') ) {
			wp_schedule_single_event( $next_date, 'wpfinance_daily_event', array( $universal, 'future' ) );
		}

		flush_rewrite_rules();
	}

	/**
	* Deactivate plugin hook
	* @since 1.0
	**/
	public function deactivate() {
		wp_unschedule_hook('wpfinance_daily_event');
		wp_unschedule_hook('wpfinance_weekly_event'); // Delete old weekly event @since 1.8
	}
}
WPFFWCB_init::instance();