<?php

use Lightning\Tools\Configuration;

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
        if (isset($overridable[$classname]) || isset($overridable['Overridable\\' . $classname])) {
            $class_file = str_replace('Overridable\\', '', $classname);
            loadClassFile($class_file);
            class_alias($overridable[$class_file], $class_file);
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
