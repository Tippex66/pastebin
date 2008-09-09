<?php
/**
 * Script for creating and loading test database
 */

if (!defined('APPLICATION_PATH')) {
    define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../'));
}

if (!class_exists('Zend_Registry', false) || !Zend_Registry::isRegistered('config')) {
    $base  = realpath(dirname(__FILE__) . '/../');
    $paths = array(
        '.', 
        $base . '/library',
    );

    if (!class_exists('Zend_Registry')) {
        ini_set('include_path', implode(PATH_SEPARATOR, $paths));
        require_once 'Zend/Loader.php';
        Zend_Loader::registerAutoload();
    }

    $config = new Zend_Config_Ini($base . '/application/config/paste.ini', 'testing');
    Zend_Registry::set('config', $config);
    unset($base, $path, $config);
}

$config = Zend_Registry::get('config');

if (file_exists($config->db->cxn->params->dbname)) {
    unlink($config->db->cxn->params->dbname);
}

$db = Zend_Db::factory($config->db->cxn);
Zend_Db_Table_Abstract::setDefaultAdapter($db);

$statements = include $config->appPath . '/data/pasteSchema.php';

foreach ($statements as $statement) {
    $db->query($statement);
}

return true;
