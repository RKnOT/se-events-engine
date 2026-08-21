<?php


# INIT
/*
require_once SE_EVENTS_PATH . 'debug/error_log.php';
$db_se = new debug_tests();
global $db_se;
*/

#AUFRUF
/*
global $db_se;     
$db_se->se_debug('START  constuct from SE_Event_Editor');
*/
class debug_tests {

    public function __construct() {

    }

    public function se_debug($data){
        $string =  current_time('Y-m-d H:i') .  PHP_EOL;
        if (is_array($data) && !empty($data)) {
            foreach ($data as $key => $data) {
                $string .= $key . ': ' . $data . PHP_EOL;
            }
        } elseif (is_object($data)) {
             $string .=   ' ist ein Object';
        } elseif (is_string($data)) {
             $string .= $data;
        } else {
            $string .= 'Anderer Typ';
        }
        $this->se_debug_print($string);
    }

    public function se_debug_print($data, $file_name = '/se-debug.log'){
            file_put_contents(
            WP_CONTENT_DIR. $file_name,
            $data . PHP_EOL ,
            FILE_APPEND
            );
    }

}