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
        if ( $this->param('bbox') )
          $form->setData('q', '' );
        if ( $this->param('query') )
          $form->setData('q', $this->param('query') );
        
        $tpl = new jTpl();
        $tpl->assign('form', $form);
        $info = $tpl->fetch('default_info');
        $rep->body->assign('INFO', $info);
        
        $bp = jApp::config()->urlengine['basePath'];
        $rep->addJSLink($bp.'js/map.default.js');
        
        if ( $this->param('bbox') && preg_match('/\d+(\.\d+)?,\d+(\.\d+)?,\d+(\.\d+)?,\d+(\.\d+)?/',$this->param('bbox')) )
          $rep->addJSCode("var cfgUrl = '".jUrl::get('osmflickr~default:getProjectConfig', array('bbox'=>$this->param('bbox')))."';");
        else
          $rep->addJSCode("var cfgUrl = '".jUrl::get('osmflickr~default:getProjectConfig')."';");
        $rep->addJSCode("var wmsServerURL = '".jUrl::get('osmflickr~photo:getCapabilities')."';");
        $rep->addJSCode("var nominatimUrl = '".jUrl::get('osmflickr~service:nominatim')."';");
        $rep->addJSCode("var osmflickrmapUrl = '".jUrl::get('osmflickr~service:osmflickrmap')."';");

        return $rep;
    }

  /**
  * Get the project configuration : map options and layers.
  * @param integer $tree_id Id of the tree
  * @return Json string containing the project options.
  */
  function getProjectConfig() {
    $rep = $this->getResponse('binary');

    # default values
    $bbox = '-85.0,-85.0,85.0,85.0';
    # use the cookie
    if ( isset($_COOKIE['bbox']) && preg_match('/(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?/',$_COOKIE['bbox']) )
      $bbox = $_COOKIE['bbox'];
    if ( $this->param('bbox') && preg_match('/(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?/',$this->param('bbox')) )
      $bbox = $this->param('bbox');

    $rep->content = '{
  "options" : {
    "googleStreets":"False",
    "googleHybrid":"False",
    "googleSatellite":"False",
    "googleTerrain":"False",
    "osmMapnik":"True",
    "osmMapquest":"True",
    "projection" : {"proj4":"+proj=longlat +ellps=WGS84 +towgs84=0,0,0,0,0,0,0 +no_defs", "ref":"EPSG:4326"},
    "bbox":['.$bbox.'],
    "imageFormat" : "image/png",
    "minScale" : 10000,
    "maxScale" : 10000000,
    "zoomLevelNumber" : 10,
    "mapScales" : [100000,50000,25000,10000]
  },
  "layers" : {}
}';
    $rep->addHttpHeader ("mime/type", "text/json");
    return $rep;
  }
}
