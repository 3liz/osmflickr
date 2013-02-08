<?php
/**
* @package   osmflickr
* @subpackage osmflickr
* @author    your name
* @copyright 2011 your name
* @link      http://www.yourwebsite.undefined
* @license    All rights reserved
*/


class osmflickrModuleInstaller extends jInstallerModule {

    function install() {
        //if ($this->firstDbExec())
        //    $this->execSQLScript('sql/install');

        /*if ($this->firstExec('acl2')) {
            jAcl2DbManager::addSubject('my.subject', 'osmflickr~acl.my.subject', 'subject.group.id');
            jAcl2DbManager::addRight('admins', 'my.subject'); // for admin group
        }
        */
    }
}