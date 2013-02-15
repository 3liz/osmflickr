<?php
/**
* The public controller
* @package   osmflickr
* @subpackage osmflickr
* @author    3Liz
* @copyright 2013 3liz
* @link      http://www.3liz.com
* @license    Mozilla Public License : http://www.mozilla.org/MPL/
*/

class defaultCtrl extends jController {
    /**
    * The OsmFlickr Map
    */
    function index() {
        $rep = $this->getResponse('htmlmap');
        $rep->title = 'OsmFlickr - Map';

        $f = jClasses::getService('osmflickr~phpFlickr');
        
        $rep->body->assign('isConnected', $f->isConnected());
        $rep->body->assign('user', $f->getUserSession());
        
        $rep->body->assign('photo', null);
        
        // Get the search form
        $form = jForms::get("osmflickr~osmflickrmap");
        if ( !$form )
          $form = jForms::create("osmflickr~osmflickrmap");
        
        $tpl = new jTpl();
        $tpl->assign('form', $form);
        $info = $tpl->fetch('default_info');
        $rep->body->assign('INFO', $info);
        
        $bp = jApp::config()->urlengine['basePath'];
        $rep->addJSLink($bp.'js/map.default.js');
        
        $rep->addJSCode("var cfgUrl = '".jUrl::get('osmflickr~photo:getProjectConfig')."';");
        $rep->addJSCode("var wmsServerURL = '".jUrl::get('osmflickr~photo:getCapabilities')."';");
        $rep->addJSCode("var nominatimUrl = '".jUrl::get('osmflickr~service:nominatim')."';");
        $rep->addJSCode("var osmflickrmapUrl = '".jUrl::get('osmflickr~service:osmflickrmap')."';");

        return $rep;
    }
}
