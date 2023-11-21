<?php if ( ! defined( 'ABSPATH' ) ) exit;

class Ffwcbook_dropbox {
    protected static $instance = null;
    protected static $api_url = 'https://content.dropboxapi.com/2/files/upload';
    protected static $token = '';
    
    public static function instance() {
        return null == self::$instance ? new self : self::$instance;
    }

    public function __construct() {
        $wpffwcb_option = WPFFWCB_init::get_settings();
        if ( isset($wpffwcb_option['dropbox_token']) ) {
            self::$token = $wpffwcb_option['dropbox_token'];
        }
    }

    public static function upload_file($filearr, $folder = 'Invoice') {
        if ( !is_array($filearr) || empty($filearr) || !isset($filearr['basename']) || !isset($filearr['path']) || !isset($filearr['sub_dir']) ) {
            return false;
        }

        $folder = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $folder));
        if ( empty($folder) ) {
            $folder = 'Invoice';
        }

        $path = '/' . $folder . '/' . $filearr['sub_dir'] . '/' . $filearr['basename'];
        $status = self::save_file_dropbox($filearr['path'], $path, "overwrite", true);
        if ( $status ) {
            do_action( 'wpffwcb_dropbox_upload', $status, $filearr, $folder, $path);
        }
        return $status;
    }

    private static function save_file_dropbox($filepath, $uploadpath, $mode = "add", $plain_error = false) {
        $return = false;
        if ( $mode == "add" || $mode == "overwrite" ) {
            $date = date_i18n('Y-m-d H:i:s', false, true);
            $headers = array('Authorization: Bearer '. self::$token,
                'Content-Type: application/octet-stream',
                'Dropbox-API-Arg: '.
                json_encode(
                    array(
                        "path"=> $uploadpath,
                        "mode" => $mode, // "add" / "overwrite"
                        "autorename" => true,
                        "mute" => false
                    )
                )
            );

            $ch = curl_init(self::$api_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            $fp = fopen($filepath, 'rb');
            $filesize = filesize($filepath);

            curl_setopt($ch, CURLOPT_POSTFIELDS, fread($fp, $filesize));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ( $plain_error ) {
                $return = ['http_code' => $http_code, 'response' => self::plain_error_message($http_code), 'datetime' => $date];
            } else {
                $return = ['http_code' => $http_code, 'response' => $response, 'datetime' => $date];
            }
            curl_close($ch);
        }
        return $return;
    }

    private static function plain_error_message($http_code) {
        $return = '';
        switch ($http_code) {
            case 200:
                $return = 'Success';
                break;

            case 400:
                $return = 'Bad input parameter';
                break;

            case 401:
                $return = 'Bad or expired token';
                break;

            case 403:
                $return = 'The user or team account doesn\'t have access to the endpoint or feature';
                break;

            case 409:
                $return = 'Endpoint-specific error';
                break;

            case 429:
                $return = 'Your app is making too many requests for the given user or team and is being rate limited. Your app should wait for the number of seconds specified in the "Retry-After" response header before trying again';
                break;
            
            default:
                $return = 'An error occurred on the Dropbox servers';
                break;
        }
        return $return;
    }

}
Ffwcbook_dropbox::instance();