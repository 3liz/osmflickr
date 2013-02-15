<?php
/**
* @package   osmflickr
* @subpackage osmflickr
* @author    DHONT René-Luc
* @copyright 2011 DHONT René-Luc
* @link      http://www.3liz.com
* @license    All rights reserved
*/

class defaultCtrl extends jController {
    /**
    *
    */
    function index() {
      // Until we create the map of geolocated Flickr Images
      // with OSM tag
      /*
          $rep = $this->getResponse('redirect');
          $rep->action = 'osmflickr~auth:index';
          return $rep;
*/
        jLog::log('default','debug');
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

        // this is a call for the 'welcome' zone after creating a new application
        // remove this line !
        //$rep->body->assignZone('MAIN', 'jelix~check_install');

        if ( $isConnected ) {
          // Get the user images
          $photos_search = $f->photos_search(array(
            "user_id"=>$user->nsid,
            "per_page"=>30,
            "content_type"=>1,  // for photos only
            //"has_geo"=>1, // for geotagged photos
            //"has_geo"=>0, // for not geotagged photos
          ));
          jLog::log(json_encode($photos_search),'debug');
          jClasses::inc('osmflickr~flickrPhoto');
          $photos = array();
          foreach ($photos_search['photo'] as $p) {
            $photo = new flickrPhoto( $p['id'] );
            $photo->updateFromSearch( $p );
            $photos[] = $photo;
          }
          $tpl = new jTpl();
          $tpl->assign('photos', $photos);
          $rep->body->assign('MAIN', $tpl->fetch('photos'));
        }

        return $rep;
    }
}
