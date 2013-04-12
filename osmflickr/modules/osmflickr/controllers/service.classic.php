<?php
/**
* The service controller
* @package   osmflickr
* @subpackage osmflickr
* @author    3Liz
* @copyright 2013 3liz
* @link      http://www.3liz.com
* @license    Mozilla Public License : http://www.mozilla.org/MPL/
*/

class serviceCtrl extends jController {
  /**
  * Get OpenStreetmap Object.
  * @param integer $osm_id The OpenStreetMap Object id
  * @param text $osm_type The OpenStreetMap Object type
  * @return XML.
  */
  function OpenStreetMap() {
    $rep = $this->getResponse('binary');
    $rep->outputFileName = 'file.osm';
    $rep->mimeType = 'text/xml';

    $osm_id = $this->param('osm_id');
    $osm_type = $this->param('osm_type');
    $osm_type_array = array("node", "way") ;

    if( !(ctype_digit($osm_id) and in_array($osm_type, $osm_type_array)) ){
      $rep->content = '<osm></osm>';
      return $rep;
    }

    $url = 'http://api.openstreetmap.org/api/0.6/';
    if ( $osm_type == 'node' )
      $url .= 'node/'.$osm_id;
    else if ( $osm_type == 'way' )
      $url .= 'way/'.$osm_id.'/full';

    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
    $content = curl_exec($curl_handle);
    curl_close($curl_handle);
    
    $rep->content = $content;
    return $rep;
  }

  /**
  * Query the OpenStreetMap XAPI
  * @param text $bbox A bounding box in EPSG:4326
  * @return XML.
  */
  function xapi() {
    $rep = $this->getResponse('binary');
    $rep->outputFileName = 'file.osm';
    $rep->mimeType = 'text/xml';

    $bbox = $this->param('bbox');
    if( !preg_match('/(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?/',$bbox) ) {
      $rep->content = '<osm></osm>';
      return $rep;
    }

    $url = 'www.overpass-api.de/api/xapi?*[bbox='.$bbox.']';
    $url = 'overpass.osm.rambler.ru/cgi/xapi?*[bbox='.$bbox.']';
    $url = 'api.openstreetmap.org/api/0.6/map?bbox='.$bbox;

    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
    $content = curl_exec($curl_handle);
    curl_close($curl_handle);

    $rep->content = $content;
    return $rep;
  }

  /**
  * Query the OpenStreetMap map API
  * @param text $bbox A bounding box in EPSG:4326
  * @return XML.
  */
  function mapapi() {
    $rep = $this->getResponse('binary');
    $rep->outputFileName = 'file.osm';
    $rep->mimeType = 'text/xml';

    $bbox = $this->param('bbox');
    if( !preg_match('/(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?/',$bbox) ) {
      $rep->content = '<osm></osm>';
      return $rep;
    }

    $url = 'api.openstreetmap.org/api/0.6/map?bbox='.$bbox;

    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
    $content = curl_exec($curl_handle);
    curl_close($curl_handle);

    $rep->content = $content;
    return $rep;
  }

  /**
  * Query the OpenStreetMap Nominatim API
  * @param text $query A query on OpenStreetMap object
  * @param text $bbox A bounding box in EPSG:4326
  * @return XML.
  */
  function nominatim() {
    $rep = $this->getResponse('binary');
    $rep->outputFileName = 'nominatim.json';
    $rep->mimeType = 'application/json';

    $query = $this->param('query');
    if ( !$query ) {
      $rep->content = '[]';
      return $rep;
    }

    $url = 'http://nominatim.openstreetmap.org/search.php?';
    $params = array(
      'q'=>$query,
      'format'=>'json',
    );
    $bbox = $this->param('bbox');
    if( !preg_match('/(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?/',$bbox) )
      $params['viewbox'] = $bbox;

    $url .= http_build_query($params);
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
    $content = curl_exec($curl_handle);
    curl_close($curl_handle);

    $rep->content = $content;

    return $rep;
  }

  /**
  * Query the Flickr API to retrieve geotagged photos with osm tag machine
  * @param text $q A query on Flickr photos
  * @param text $bbox A bounding box in EPSG:4326
  * @return XML.
  */
  function osmflickrmap() {
    $f = jClasses::getService('osmflickr~phpFlickr');
    
    // Get the search form
    $form = jForms::get("osmflickr~osmflickrmap");
    if ( !$form )
      $form = jForms::create("osmflickr~osmflickrmap");
    $form->setData('q', $this->param('q') );

    // Get the osmflickr images
    $search_params = array(
      "per_page"=>30,
      "content_type"=>1,  // for photos only
      "has_geo"=>1, // for geotagged photos
      "machine_tags"=>"osm:",
      "extras"=>"geo,url_s,url_sq,machine_tags"
    );
    $bbox = $this->param('bbox');
    if( !preg_match('/(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?,(-)?\d+(\.\d+)?/',$bbox) )
      $search_params['bbox'] = $bbox;
    if ( $form->getData('q') != '' )
      $search_params['text'] = $form->getData('q');
    
    $photos_search = $f->photos_search($search_params);

    $rep = $this->getResponse('binary');
    $rep->outputFileName = 'flickr.json';
    $rep->mimeType = 'application/json';
    $rep->content = '{"photos":'.json_encode($photos_search).',"stat":"ok"}';

    return $rep;
  }
}
