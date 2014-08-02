<?php

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Messenger;

/**
 * A custom class loader.
 *
 * @param string $classname
 */
function classAutoloader($classname) {
    if ($classname != 'Lightning\Tools\Configuration') {
        static $loaded = false;
        static $classes;
        static $overrides = array();
        static $overridable = array();
        if (!$loaded) {
            $classes = Configuration::get('classes');
            $overridable = Configuration::get('overridable');
            $loaded = true;
        }
        if (!empty($classes[$classname])) {
            // Load an override class and override it.
            $overridden_name = 'Overridden\\' . $classname;
            $overrides[$overridden_name] = $overridden_name;
            loadClassFile($classname);
            loadClassFile($classes[$classname]);
            return;
        }
        if (isset($overrides[$classname])) {
            return;
        }
        if (in_array($classname, $overridable)) {
            $class_file = str_replace('Overridable\\', '', $classname);
            loadClassFile($class_file);
            class_alias($classname, $class_file);
            return;
        }
    }
    loadClassFile($classname);
}

function loadClassFile($classname) {
    $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $classname);
    require_once HOME_PATH . DIRECTORY_SEPARATOR . $class_path . '.php';
}

spl_autoload_register('classAutoloader');

// REMOVE ALL THIS JUNK

// Define the home path.
if (!defined('HOME_PATH')) {
    define('HOME_PATH', empty($home_path) ? '.' : $home_path);
}

if (!defined('CONFIG_DIR')) {
    define('CONFIG_DIR', './config');
}

//include HOME_PATH.'/include/site_detect.php';

require_once HOME_PATH.'/include/lib.inc.php';
require_once HOME_PATH.'/include/lib2.inc.php';
//require_once HOME_PATH.'/include/class.phpmailer.php';
//require_once HOME_PATH.'/include/class_aa.php';


// Detect which server was requested
$server = '';
global $site_id;
$site_id = 0;
if ($_SERVER['HTTP_HOST']) {
    // HTTP host was sent by the requestor
    $server = $_SERVER['HTTP_HOST'];
} elseif ($_SERVER['SERVER_NAME']) {
    // SERVER_NAME is known to the host
    $server = $_SERVER['SERVER_NAME'];
}

$user = ClientUser::getInstance();

if($user->details['type'] >= 5){
    Database::getInstance()->verbose();
    error_reporting(E_ALL ^ E_NOTICE);
    require_once HOME_PATH.'/include/debugging.php';
}

if($user->details['type'] >= 5){
    $cms->editable = true;

    // BLOG WARNING MESSAGE
    if(Database::getInstance()->check('blog_comment', array('approved' => 0))){
        Messenger::error('You have comments on your blog that need to be approved. <a href="/Lightning/Pages/blog_comments.php">click here</a>');
    }
}