<?php
/**
* @package   sendui
* @subpackage 
* @author    Yves Tannier [grafactory.net]
* @copyright 2009 Yves Tannier
* @link      http://www.grafactory.net/sendui
* @license   http://www.grafactory.net/sendui/licence GPL Gnu Public Licence
*/

define ('JELIX_APP_PATH',   dirname (__FILE__).DIRECTORY_SEPARATOR); // don't change

require (JELIX_APP_PATH.'/../lib/jelix/init.php');

define ('JELIX_APP_TEMP_PATH',      realpath(JELIX_APP_PATH.'../temp/sendui/').'/');
define ('JELIX_APP_VAR_PATH',       JELIX_APP_PATH.'var/');
define ('JELIX_APP_LOG_PATH',       JELIX_APP_PATH.'var/log/');
define ('JELIX_APP_CONFIG_PATH',    JELIX_APP_PATH.'var/config/');

// production or test
if($_SERVER['SERVER_NAME']=='sendui') {
    if (!empty($_SERVER['SPECIAL_ID'])) {
        define ('JELIX_APP_WWW_PATH', $_SERVER['PATH_APP_WWW']);
    } else {
        define ('JELIX_APP_WWW_PATH', '/Users/yves/Sites/sendui/web/www/sendui/');
    }
} else {
    define ('JELIX_APP_WWW_PATH', '/home/grafactory/www/grafactory.net/www/sendui/');
}

define ('JELIX_APP_CMD_PATH',       JELIX_APP_PATH.'scripts/');
define ('JELIX_APP_WWW',            'sendui/');

