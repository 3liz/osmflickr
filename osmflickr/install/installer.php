<?php
/**
* @package   osmflickr
* @author    your name
* @copyright 2011 your name
* @link      http://www.yourwebsite.undefined
* @license    All rights reserved
*/

require_once (dirname(__FILE__).'/../application.init.php');

jApp::setEnv('install');

$installer = new jInstaller(new textInstallReporter());

$installer->installApplication();

try {
    jAppManager::clearTemp();    
}
catch(Exception $e) {
    echo "WARNING: temporary files cannot be deleted because of this error: ".$e->getMessage().".\nWARNING: Delete temp files by hand immediately!\n";
}
