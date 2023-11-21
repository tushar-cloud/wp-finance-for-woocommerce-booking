<?php if ( ! defined( 'ABSPATH' ) ) exit;

class WPFFWCB_generate {
    protected static $instance = null;
    protected static $upload_dir = null;
    protected static $upload_url = null;
    protected static $stylesheet = null;

    protected static $invoice_dir = null;
    protected static $invoice_url = null;
    protected static $journal_dir = null;
    protected static $journal_url = null;
    protected static $sale_dir = null;
    protected static $sale_url = null;
    protected static $theme_dir = null;
    protected static $plugin_dir = null;
    protected static $invoice_file = null;
    protected static $journal_file = null;
    protected static $sale_file = null;
    
    public static function instance() {
        return null == self::$instance ? new self : self::$instance;
    }

    public function __construct() {
        $wp_upload_dir = wp_upload_dir();
        self::$upload_dir = trailingslashit($wp_upload_dir['basedir']) . WPFFWCB_DIRNAME;
        self::$upload_url = trailingslashit($wp_upload_dir['baseurl']) . WPFFWCB_DIRNAME;

        self::$invoice_dir = self::$upload_dir . '/Invoice/';
        self::$invoice_url = self::$upload_url . '/Invoice/';
        self::$journal_dir = self::$upload_dir . '/Invoice-Journal/';
        self::$journal_url = self::$upload_url . '/Invoice-Journal/';
        self::$sale_dir = self::$upload_dir . '/Sale-Reports/';
        self::$sale_url = self::$upload_url . '/Sale-Reports/';
        self::$theme_dir = get_stylesheet_directory() . '/' . WPFFWCB_DIRNAME . '/';

        self::$stylesheet = file_exists( self::$theme_dir . 'css/pdf-styles.css' ) ? self::$theme_dir . 'css/pdf-styles.css' : WPFFWCB_ASSETS_URL . 'css/pdf-styles.css';

        self::$plugin_dir = WPFFWCB_DIR . '/templates/';
        self::$invoice_file = file_exists( self::$theme_dir . 'templates/invoices.php' ) ? self::$theme_dir . 'templates/invoices.php' : self::$plugin_dir . 'invoices.php';
        self::$journal_file = file_exists( self::$theme_dir . 'templates/journal.php' ) ? self::$theme_dir . 'templates/journal.php' : self::$plugin_dir . 'journal.php';
        self::$sale_file = file_exists( self::$theme_dir . 'templates/sale.php' ) ? self::$theme_dir . 'templates/sale.php' : self::$plugin_dir . 'sale.php';

        add_filter( 'wc_price', function( $return, $price, $args, $unformatted_price, $original_price ) {
            $negative = $price < 0;
            $formatted_price = ( $negative ? '-' : '' ) . sprintf( $args['price_format'], '<span class="woocommerce-Price-currencySymbol">' . get_woocommerce_currency_symbol( $args['currency'] ) . '</span>', $price );
            $return = '<span class="woocommerce-Price-amount amount">' . $formatted_price . '</span>';

            if ( $args['ex_tax_label'] && wc_tax_enabled() ) {
                $return .= ' <small class="woocommerce-Price-taxLabel tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
            }
            return $return;
        }, 99, 5);
    }

    /**
    * Insert into table
    * @since 1.0
    **/
    private static function insert_data($data) {
        $data = (array) $data;
        if ( !empty($data) ) {
            global $wpdb;
            $table_name = $wpdb->prefix . WPFFWCB_TABLE;

            $arr = array(
                'order_id' => (isset($data['order_id']) && !empty($data['order_id'])) ? $data['order_id'] : 0,
                'booking_id' => (isset($data['booking_id']) && !empty($data['booking_id'])) ? $data['booking_id'] : 0,
                'date' => (isset($data['date']) && !empty($data['date'])) ? $data['date'] : date_i18n('Y-m-d H:i:s', false, true),
                'source' => (isset($data['source']) && !empty($data['date'])) ? str_replace("'","\'", maybe_serialize($data['source'])) : '',
                'is_backup' => (isset($data['is_backup']) && !empty($data['is_backup'])) ? $data['is_backup'] : 'failed',
                'view' => (isset($data['view']) && !empty($data['view'])) ? strtolower($data['view']) : 'invoice',
                'response' => (isset($data['response']) && !empty($data['response'])) ? $data['response'] : '',
                'backup_date' => (isset($data['backup_date']) && !empty($data['backup_date'])) ? $data['backup_date'] : date_i18n('Y-m-d H:i:s', false, true)
            );

            $arr = apply_filters( 'wpffwcb_data_before_insert', $arr);

            $is_inserted = $wpdb->insert( $table_name, $arr );
            if ( $is_inserted ) {
                $id = $wpdb->insert_id;
                do_action( 'wpffwcb_inserted_data', $id, $arr );
                return $id;
            }
        }
        return false;
    }

    /**
    * Get Data from table by ID
    * @since 1.7
    **/
    public static function get_data($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . WPFFWCB_TABLE;
        $return = false;

        $data = $wpdb->get_row("SELECT * FROM $table_name WHERE `ID` = '$id'");
        if ( isset($data->ID) ) {
            $return = $data;
        }
        return $return;
    }

    /**
    * Get Directory Info
    * @since 1.7
    **/
    public static function get_dir_info($type, $year, $month) {
        $return = false;
        $is_valid_date = WPFFWCB_init::validateDate($year.'-'.$month, 'Y-M');
        if ( $is_valid_date ) {
            $dirname = $dirurl = "";
            switch($type) {
                case 'Invoice':
                case 'invoice':
                    $dirname = self::$upload_dir . '/Invoice/'.$year.'/'.$month;
                    $dirurl = self::$upload_url . '/Invoice/'.$year.'/'.$month;
                break;

                case 'Invoice-Journal':
                case 'invoice-Journal':
                case 'Invoice-journal':
                case 'invoice-journal':
                    $dirname = self::$upload_dir . '/Invoice-Journal/'.$year.'/'.$month;
                    $dirurl = self::$upload_url . '/Invoice-Journal/'.$year.'/'.$month;
                break;
                
                case 'Sale-Report':
                case 'Sale-report':
                case 'sale-Report':
                case 'sale-report':
                    $dirname = self::$upload_dir . '/Sale-Reports/'.$year.'/'.$month;
                    $dirurl = self::$upload_url . '/Sale-Reports/'.$year.'/'.$month;
                break;
            }

            if ( !empty($dirname) && !empty($dirurl) ) {
                $has_dir = true;
                if ( ! is_dir($dirname) ) {
                    $has_dir = wp_mkdir_p($dirname);
                }

                if ( $has_dir ) {
                    $return = [
                        'path' => $dirname . '/',
                        'url' => $dirurl . '/'
                    ];
                }
            }
        }

        return $return;
    }

    /**
    * Generate Invoice data
    * @access public
    * @since 1.0
    **/
    public static function invoice($booking_id, $booking, $datetime = '', $type = 'S', $backup = true) {
        global $wpdb;
        $table_name = $wpdb->prefix . WPFFWCB_TABLE;

        if ( ! $booking instanceof WC_Booking ) {
            return;
        }

        $order = $booking->get_order();
        if ( ! $order ) {
            return;
        }

        if ( ! WPFFWCB_init::validateDate($datetime, 'Y-m-d H:i:s') ) {
            $datetime = date_i18n('Y-m-d H:i:s', false, true);
        }
        
        $row = $wpdb->get_row("SELECT * FROM $table_name WHERE `booking_id` = ".$booking_id." AND `view` = 'invoice'");
        if ( isset($row->ID) ) {
            $invoice_id = $row->ID;
        } else {
            $invoice_id = self::insert_data(array(
                'order_id' => $order->get_id(),
                'booking_id' => $booking_id,
                'date' => $datetime,
                'view' => 'invoice',
                'source' => '',
                'is_backup' => 'failed'
            ));
        }

        if ( $invoice_id && $invoice_id > 0 ) {
            if ( $type == 'd' || $type == 'D' ) {
                return self::generate_invoice($booking_id, $booking, $order, $invoice_id, time(), 'D');
            } else {
                $invoice_data = self::generate_invoice($booking_id, $booking, $order, $invoice_id);

                if ( !empty($invoice_data) && is_array($invoice_data) ) {
                    $up_data = [
                        'source' => str_replace("'","\'", maybe_serialize(apply_filters('wpffwcb_save_source_on_db', $invoice_data))),
                        'is_backup' => 'failed',
                        'response' => __('Updated report not backed up, Click on the Backup button to backup ', 'wpfinance')
                    ];
                    if ( $backup ) {
                        $upload_data = Ffwcbook_dropbox::upload_file($invoice_data);
                        if ( $upload_data && isset($upload_data['http_code'], $upload_data['response']) ) {
                            if ( $upload_data['http_code'] == 200 ) {
                                $up_data = [
                                    'source' => str_replace("'","\'", maybe_serialize(apply_filters('wpffwcb_save_source_on_db', $invoice_data))),
                                    'is_backup' => 'success',
                                    'backup_date' => $upload_data['datetime'],
                                    'response' => $upload_data['response']
                                ];
                            } else {
                                $up_data = [
                                    'source' => str_replace("'","\'", maybe_serialize(apply_filters('wpffwcb_save_source_on_db', $invoice_data))),
                                    'is_backup' => 'failed',
                                    'response' => $upload_data['response']
                                ];
                            }
                        }
                    }
                    $wpdb->update($table_name, $up_data, ['ID' => $invoice_id]);
                    return true;
                }
            }
        }
        return false;
    }

    /**
    * Generate Invoice data
    * @access public
    * @since 1.0
    **/
    public static function sale_report($inp_end_date, $inp_start_date, $type = 'S', $backup = true) {
        global $wpdb;
        $table_name = $wpdb->prefix . WPFFWCB_TABLE;

        $datetime = current_datetime();
        if ( ! $datetime->getOffset() ) {
            $time_offset = 0;
        } else {
            $time_offset = $datetime->getOffset();
        }

        $uni_unix = date_i18n('U', false, true);
        $gmt_unix = $uni_unix + $time_offset;
        $gmt_date = date('Y-m-d H:i:s', $gmt_unix);

        if ( WPFFWCB_init::validateDate($inp_start_date, 'Y-m-d H:i:s') ) {
            $inp_start_date = date("Y-m-d 00:00:00", strtotime($inp_start_date));
            $start_date = date("Y-m-d", strtotime($inp_start_date));
            $sql_start_time = date("Y-m-d H:i:s", (strtotime($inp_start_date) - $time_offset));
        } else {
            return false;
        }

        if ( WPFFWCB_init::validateDate($inp_end_date, 'Y-m-d H:i:s') ) {
            $inp_end_date = date("Y-m-d 23:59:59", strtotime($inp_end_date));
            $end_date = date("Y-m-d", strtotime($inp_end_date));
            $sql_end_time = date("Y-m-d H:i:s", (strtotime("+1 days", strtotime($inp_end_date)) - $time_offset));
        } else {
            return false;
        }

        // Data
        $report_data = WPFFWCB_init::sale_report_data($end_date, $start_date);
        if ( ! empty($report_data) && is_array($report_data) ) {
            if ( $type == 'd' || $type == 'D' ) {
                return self::generate_sale_report($report_data, $inp_end_date, $inp_start_date, time(), $gmt_date, 'D');
            } else {
                $d_date_sta = date("Y-m-d 00:00:00", strtotime($inp_end_date));
                $d_sql_end = date("Y-m-d H:i:s", (strtotime("+1 days", strtotime($inp_end_date)) - $time_offset));
                $d_sql_sta = date("Y-m-d H:i:s", (strtotime("+1 days", strtotime($d_date_sta)) - $time_offset));

                $row = $wpdb->get_row("SELECT * FROM $table_name WHERE `date` BETWEEN '$d_sql_sta' AND '$d_sql_end' AND `view` = 'sale'");
                if ( isset($row->ID) ) {
                    $sale_id = $row->ID;
                } else {
                    $sale_id = self::insert_data(array(
                        'date' => date('Y-m-d H:i:s', $uni_unix),
                        'view' => 'sale',
                        'is_backup' => 'failed'
                    ));
                }

                if ( $sale_id && $sale_id > 0 ) {
                    $sale_data = self::generate_sale_report($report_data, $inp_end_date, $inp_start_date, $sale_id, $gmt_date);

                    if ( $sale_data && is_array($sale_data) && !empty($sale_data) ) {
                        $up_data = [
                            'source' => str_replace("'","\'", maybe_serialize(apply_filters('wpffwcb_save_source_on_db', $sale_data))),
                            'is_backup' => 'failed',
                            'response' => __('Updated report not backed up, Click on the Backup button to backup ', 'wpfinance')
                        ];
                        if ( $backup ) {
                            $upload_data = Ffwcbook_dropbox::upload_file($sale_data, 'Sale-Reports');
                            if ( $upload_data && isset($upload_data['http_code'], $upload_data['response']) ) {
                                if ( $upload_data['http_code'] == 200 ) {
                                    $up_data = [
                                        'source' => str_replace("'","\'", maybe_serialize(apply_filters('wpffwcb_save_source_on_db', $sale_data))),
                                        'is_backup' => 'success',
                                        'backup_date' => $upload_data['datetime'],
                                        'response' => $upload_data['response']
                                    ];
                                } else {
                                    $up_data = [
                                        'source' => str_replace("'","\'", maybe_serialize(apply_filters('wpffwcb_save_source_on_db', $sale_data))),
                                        'is_backup' => 'failed',
                                        'response' => $upload_data['response']
                                    ];
                                }
                            }
                        }
                        $wpdb->update($table_name, $up_data, ['ID' => $sale_id]);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
    * Generate Invoice data
    * @access public
    * @since 1.0
    **/
    public static function invoice_journal($inp_end_date, $inp_start_date, $type = 'S', $backup = true){
        global $wpdb;
        $table_name = $wpdb->prefix . WPFFWCB_TABLE;

        $datetime = current_datetime();
        if ( ! $datetime->getOffset() ) {
            $time_offset = 0;
        } else {
            $time_offset = $datetime->getOffset();
        }

        $uni_unix = date_i18n('U', false, true);
        $gmt_unix = $uni_unix + $time_offset;
        $gmt_date = date('Y-m-d H:i:s', $gmt_unix);

        if ( WPFFWCB_init::validateDate($inp_start_date, 'Y-m-d H:i:s') ) {
            $inp_start_date = date("Y-m-d 00:00:00", strtotime($inp_start_date));
            $start_date = date("Y-m-d", strtotime($inp_start_date));
            $sql_start_time = date("Y-m-d H:i:s", (strtotime($inp_start_date) - $time_offset));
        } else {
            return false;
        }

        if ( WPFFWCB_init::validateDate($inp_end_date, 'Y-m-d H:i:s') ) {
            $inp_end_date = date("Y-m-d 23:59:59", strtotime($inp_end_date));
            $end_date = date("Y-m-d", strtotime($inp_end_date));
            $sql_end_time = date("Y-m-d H:i:s", (strtotime("+1 days", strtotime($inp_end_date)) - $time_offset));
        } else {
            return false;
        }

        $inv_journals = $wpdb->get_results("SELECT * FROM $table_name WHERE `date` BETWEEN '$sql_start_time' AND '$sql_end_time' AND `view` = 'invoice' GROUP BY `order_id` ORDER BY `date` DESC", ARRAY_A);
        if ( !empty($inv_journals) && is_array($inv_journals) ) {
            if ( $type == 'd' || $type == 'D' ) {
                return self::generate_invoice_journal($inv_journals, $inp_end_date, $inp_start_date, time(), $gmt_date, 'D');
            } else {
                $d_date_sta = date("Y-m-d 00:00:00", strtotime($inp_end_date));
                $d_sql_end = date("Y-m-d H:i:s", (strtotime("+1 days", strtotime($inp_end_date)) - $time_offset));
                $d_sql_sta = date("Y-m-d H:i:s", (strtotime("+1 days", strtotime($d_date_sta)) - $time_offset));

                $row = $wpdb->get_row("SELECT * FROM $table_name WHERE `date` BETWEEN '$d_sql_sta' AND '$d_sql_end' AND `view` = 'journal'");
                if ( isset($row->ID) ) {
                    $journal_id = $row->ID;
                } else {
                    $journal_id = self::insert_data(array(
                        'date' => date('Y-m-d H:i:s', $uni_unix),
                        'view' => 'journal',
                        'is_backup' => 'failed'
                    ));
                }

                if ( $journal_id && $journal_id > 0 ) {
                    $journal_data = self::generate_invoice_journal($inv_journals, $inp_end_date, $inp_start_date, $journal_id, $gmt_date);

                    if ( $journal_data && is_array($journal_data) && !empty($journal_data) ) {
                        $up_data = [
                            'source' => str_replace("'","\'", maybe_serialize(apply_filters('wpffwcb_save_source_on_db', $journal_data))),
                            'is_backup' => 'failed',
                            'response' => __('Updated report not backed up, Click on the Backup button to backup ', 'wpfinance')
                        ];
                        if ( $backup ) {
                            $upload_data = Ffwcbook_dropbox::upload_file($journal_data, 'Invoice-Journal');
                            if ( $upload_data && isset($upload_data['http_code'], $upload_data['response']) ) {
                                if ( $upload_data['http_code'] == 200 ) {
                                    $up_data = [
                                        'source' => str_replace("'","\'", maybe_serialize(apply_filters('wpffwcb_save_source_on_db', $journal_data))),
                                        'is_backup' => 'success',
                                        'backup_date' => $upload_data['datetime'],
                                        'response' => $upload_data['response']
                                    ];
                                } else {
                                    $up_data = [
                                        'source' => str_replace("'","\'", maybe_serialize(apply_filters('wpffwcb_save_source_on_db', $journal_data))),
                                        'is_backup' => 'failed',
                                        'response' => $upload_data['response']
                                    ];
                                }
                            }
                        }
                        $wpdb->update($table_name, $up_data, ['ID' => $journal_id]);
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
    * Generate Invoice
    * @since 1.0
    **/
    private static function generate_invoice($booking_id, $booking, $order, $invoice_id, $time = '', $type = 'S') {
        if ( ! $booking instanceof WC_Booking ){
            return false;
        }

        if ( ! $order ) {
            return false;
        }

        ob_start();

        $wpffwcb_option = WPFFWCB_init::get_settings();
        $logo_url = ( isset($wpffwcb_option['logo']) && $wpffwcb_option['logo'] !== false ) ? get_attached_file($wpffwcb_option['logo']) : WPFFWCB_ASSETS_DIR . 'img/logo-default.jpg';
        $show_sku = ( isset($wpffwcb_option['show_sku']) && $wpffwcb_option['show_sku'] !== false ) ? $wpffwcb_option['show_sku'] : false;
        $show_variation = ( isset($wpffwcb_option['show_variation']) && $wpffwcb_option['show_variation'] !== false ) ? $wpffwcb_option['show_variation'] : false;
        $show_booking = ( isset($wpffwcb_option['show_booking']) && $wpffwcb_option['show_booking'] !== false ) ? $wpffwcb_option['show_booking'] : false;
        $time = !empty($time) ? $time : date_i18n('Y-m-d H:i:s');
        
        try {
            include self::$invoice_file;
        } catch (Exception $e) {
            ob_clean();
            return false;
        }

        $html = ob_get_clean();
        $html = str_replace(['Moms', 'moms'], __('Vat', 'wpfinance'), $html); // Force replace

        $css = file_get_contents(self::$stylesheet, false, stream_context_create([
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false
            ]
        ]));

        $mpdf = new \Mpdf\Mpdf([
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_top' => 15,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_bottom' => 15,
            'margin_header' => 6,
            'margin_footer' => 5,
            'defaultheaderline ' => 0,
            'defaultfooterline' => 0,
        ]);

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->SetHTMLHeader('<table width="100%" class="header"><tr><td width="50%">'.__('Invoice', 'wpfinance').'</td><td width="50%" style="text-align: right;">' . esc_html( sprintf( '%1$s (%2$s)', $time, WPFFWCB_init::get_timezone_name() ) ) . '</td></tr></table>');
        $mpdf->SetFooter('<p class="footer">'.__('Page {PAGENO} of {nbpg}', 'wpfinance').'</p>');
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        
        try{
            if ( $type == 'D' || $type == 'd' ) {
                header('Content-Type: application/pdf');
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                $mpdf->Output("Invoice-".time().".pdf", "D");
                return true;
            } else {
                $invoice_data = self::get_data($invoice_id);
                if ( ! $invoice_data || ! isset($invoice_data->date) || ! WPFFWCB_init::validateDate($invoice_data->date, 'Y-m-d H:i:s') ) {
                    return false;
                }

                $str_inv_date = strtotime($invoice_data->date);
                $dirinfo = self::get_dir_info('Invoice', date('Y', $str_inv_date), date('M', $str_inv_date));

                if ( $dirinfo && isset($dirinfo['path'], $dirinfo['url']) ) {
                    $invoice_name = date('Ymd', $str_inv_date) . '-invoice-'.$invoice_id.'-report';
                    $invoice_name = apply_filters('wpffwcb_invoice_name', $invoice_name, $invoice_id, $booking_id);
                    $invoice_basename = $invoice_name . '.pdf';
                    $invoice_path = $dirinfo['path'] . $invoice_basename;
                    $invoice_url = $dirinfo['url'] . $invoice_basename;
                    $mpdf->Output($invoice_path, "F");

                    if ( file_exists($invoice_path) ) {
                        do_action( 'wpffwcb_generate_invoice', $booking_id, $invoice_id, $invoice_path, $invoice_url, $invoice_basename );

                        return [
                            'basename' => $invoice_basename,
                            'path' => $invoice_path,
                            'url' => $invoice_url,
                            'invoice_id' => $invoice_id,
                            'date' => date('Y-m-d H:00:00', strtotime($time)),
                            'sub_dir' => date('Y', $str_inv_date) . '/' . date('M', $str_inv_date)
                        ];
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        } catch(Exception $ex){
            return false;
        }
    }

    /**
    * Generate Sale Report
    * @since 1.0
    **/
    private static function generate_sale_report($report_data, $end_date, $start_date, $sale_id, $time = '', $type = 'S') {
        if ( empty($report_data) || ! is_array($report_data) ) {
            return false;
        }

        ob_start();

        $wpffwcb_option = WPFFWCB_init::get_settings();
        $logo_url = ( isset($wpffwcb_option['logo']) && $wpffwcb_option['logo'] !== false ) ? get_attached_file($wpffwcb_option['logo']) : WPFFWCB_ASSETS_DIR . 'img/logo-default.jpg';
        $time = !empty($time) ? $time : date_i18n('Y-m-d H:i:s');
        
        try {
            include self::$sale_file;
        } catch (Exception $e) {
            ob_clean();
            return false;
        }

        $html = ob_get_clean();
        $html = str_replace(['Moms', 'moms'], __('Vat', 'wpfinance'), $html); // Force replace
        
        $css = file_get_contents(self::$stylesheet, false, stream_context_create([
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false
            ]
        ]));

        $mpdf = new \Mpdf\Mpdf([
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_top' => 15,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_bottom' => 15,
            'margin_header' => 6,
            'margin_footer' => 5,
            'defaultheaderline ' => 0,
            'defaultfooterline' => 0,
        ]);

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->SetHTMLHeader('<table width="100%" class="header"><tr><td width="50%">'.__('Sale Report', 'wpfinance').'</td><td width="50%" style="text-align: right;">' . esc_html( sprintf( '%1$s (%2$s)', $time, WPFFWCB_init::get_timezone_name() ) ) . '</td></tr></table>');
        $mpdf->SetFooter('<p class="footer">'.__('Page {PAGENO} of {nbpg}', 'wpfinance').'</p>');
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

        try{
            if ( $type == 'D' || $type == 'd' ) {
                header('Content-Type: application/pdf');
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                $mpdf->Output("Sale-".time().".pdf", "D");
                return true;
            } else {
                $sale_data = self::get_data($sale_id);
                if ( ! $sale_data || ! isset($sale_data->date) || ! WPFFWCB_init::validateDate($sale_data->date, 'Y-m-d H:i:s') || ! WPFFWCB_init::validateDate($start_date, 'Y-m-d H:i:s') || ! WPFFWCB_init::validateDate($end_date, 'Y-m-d H:i:s') ) {
                    return false;
                }

                $str_sale_date = strtotime($sale_data->date);
                $dirinfo = self::get_dir_info('Sale-Report', date('Y', $str_sale_date), date('M', $str_sale_date));

                if ( $dirinfo && isset($dirinfo['path'], $dirinfo['url']) ) {
                    $sale_name = date('Ymd', $str_sale_date) . '-sale-'.$sale_id.'-report';
                    $sale_name = apply_filters('wpffwcb_sale_name', $sale_name, $sale_id);
                    $sale_basename = $sale_name . '.pdf';
                    $sale_path = $dirinfo['path'] . $sale_basename;
                    $sale_url = $dirinfo['url'] . $sale_basename;
                    $mpdf->Output($sale_path, "F");

                    if ( file_exists($sale_path) ) {
                        do_action( 'wpffwcb_generate_sale', $report_data, $sale_id, $sale_path, $sale_url, $sale_basename );

                        return [
                            'basename' => $sale_basename,
                            'path' => $sale_path,
                            'url' => $sale_url,
                            'sale_id' => $sale_id,
                            'date' => date('Y-m-d H:00:00', strtotime($time)),
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'sub_dir' => date('Y', $str_sale_date) . '/' . date('M', $str_sale_date)
                        ];
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        } catch(Exception $ex){
            return false;
        }
    }

    /**
    * Generate Invoice Journal
    * @since 1.0
    **/
    private static function generate_invoice_journal($invoices, $end_date, $start_date, $journal_id, $time = '', $type = 'S') {
        ob_start();

        $wpffwcb_option = WPFFWCB_init::get_settings();
        $logo_url = ( isset($wpffwcb_option['logo']) && $wpffwcb_option['logo'] !== false ) ? get_attached_file($wpffwcb_option['logo']) : WPFFWCB_ASSETS_DIR . 'img/logo-default.jpg';
        $time = !empty($time) ? $time : date_i18n('Y-m-d H:i:s');
        
        try {
            include self::$journal_file;
        } catch (Exception $e) {
            ob_clean();
            return false;
        }

        $html = ob_get_clean();
        $html = str_replace(['Moms', 'moms'], __('Vat', 'wpfinance'), $html); // Force replace
        
        $css = file_get_contents(self::$stylesheet, false, stream_context_create([
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false
            ]
        ]));

        $mpdf = new \Mpdf\Mpdf([
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_top' => 15,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_bottom' => 15,
            'margin_header' => 6,
            'margin_footer' => 5,
            'defaultheaderline ' => 0,
            'defaultfooterline' => 0,
        ]);

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->SetHTMLHeader('<table width="100%" class="header"><tr><td width="50%">'.__('Invoice Journal', 'wpfinance').'</td><td width="50%" style="text-align: right;">' . esc_html( sprintf( '%1$s (%2$s)', $time, WPFFWCB_init::get_timezone_name() ) ) . '</td></tr></table>');
        $mpdf->SetFooter('<p class="footer">'.__('Page {PAGENO} of {nbpg}', 'wpfinance').'</p>');
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

        try{
            if ( $type == 'D' || $type == 'd' ) {
                header('Content-Type: application/pdf');
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                $mpdf->Output("Journal-".time().".pdf", "D");
                return true;
            } else {
                $journal_data = self::get_data($journal_id);
                if ( ! $journal_data || ! isset($journal_data->date) || ! WPFFWCB_init::validateDate($journal_data->date, 'Y-m-d H:i:s') || ! WPFFWCB_init::validateDate($start_date, 'Y-m-d H:i:s') || ! WPFFWCB_init::validateDate($end_date, 'Y-m-d H:i:s') ) {
                    return false;
                }

                $str_journal_date = strtotime($journal_data->date);
                $dirinfo = self::get_dir_info('Invoice-Journal', date('Y', $str_journal_date), date('M', $str_journal_date));

                if ( $dirinfo && isset($dirinfo['path'], $dirinfo['url']) ) {
                    $journal_name = date('Ymd', $str_journal_date) . '-journal-'.$journal_id.'-report';
                    $journal_name = apply_filters('wpffwcb_journal_name', $journal_name, $journal_id);
                    $journal_basename = $journal_name . '.pdf';
                    $journal_path = $dirinfo['path'] . $journal_basename;
                    $journal_url = $dirinfo['url'] . $journal_basename;
                    $mpdf->Output($journal_path, "F");

                    if ( file_exists($journal_path) ) {
                        do_action( 'wpffwcb_generate_journal', $invoices, $journal_id, $journal_path, $journal_url, $journal_basename );

                        return [
                            'basename' => $journal_basename,
                            'path' => $journal_path,
                            'url' => $journal_url,
                            'journal_id' => $journal_id,
                            'date' => date('Y-m-d H:00:00', strtotime($time)),
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'sub_dir' => date('Y', $str_journal_date) . '/' . date('M', $str_journal_date)
                        ];
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        } catch(Exception $ex){
            return false;
        }
    }

}
WPFFWCB_generate::instance();