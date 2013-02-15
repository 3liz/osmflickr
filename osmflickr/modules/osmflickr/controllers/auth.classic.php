<?php
/**
* @package   osmflickr
* @subpackage osmflickr
* @author    DHONT René-Luc
* @copyright 2011 DHONT René-Luc
* @link      http://www.3liz.com
* @license    All rights reserved
*/


class authCtrl extends jController {
    /**
    *
    */
    function index() {
        $rep = $this->getResponse('html');
        $f = jClasses::getService('osmflickr~phpFlickr');

        $isConnected = $f->isConnected();
        $user = $f->getUserSession();

        $rep->body->assign('isConnected', $isConnected);
        $rep->body->assign('user', $user);

        // this is a call for the 'welcome' zone after creating a new application
        // remove this line !
        //$rep->body->assignZone('MAIN', 'jelix~check_install');

        if ( $isConnected ) {
          // Get the search form
          $form = jForms::get("osmflickr~search");
          if ( !$form ) {
            $form = jForms::create("osmflickr~search");
            $form->setData('hasgeo', -1);
            $form->setData('hasosm', -1);
          }
          if ( $this->intParam('page') )
            $form->setData('page', $this->param('page') );

          // Get the user images
          $search_params = array(
            "user_id"=>$user->nsid,
            //"user_id"=>"me",
            //"safe_search"=>"2",
            "per_page"=>30,
            "content_type"=>1,  // for photos only
            //"has_geo"=>1, // for geotagged photos
            //"has_geo"=>0, // for not geotagged photos
            //"machine_tags"=>"osm:",
            "extras"=>"geo,machine_tags"
          );

          if ( $form->getData('hasosm') == '1' )
            $search_params['text'] = 'osm:%=%';
          else if ( $form->getData('hasosm') == '0' )
            $search_params['text'] = '-osm:%=%';
          if ( $form->getData('q') != '' ) {
            if ( isset($search_params['text']) )
              $search_params['text'] .= ' '.$form->getData('q');
            else
              $search_params['text'] = $form->getData('q');
          }
          if ( $form->getData('hasgeo') != '-1' )
            $search_params['has_geo'] = $form->getData('hasgeo');
          if ( $form->getData('page') != '1' )
            $search_params['page'] = $form->getData('page');

          $photos_search = $f->photos_search($search_params);

          jClasses::inc('osmflickr~flickrPhoto');
          $photos = array();
          if ($photos_search) {
            foreach ($photos_search['photo'] as $p) {
              $photo = new flickrPhoto( $p['id'] );
              $photo->updateFromSearch( $p );
              $photos[] = $photo;
            }
          } else {
            jLog::log('Code: '.$f->error_code,'debug');
            jLog::log('Msg: '.json_encode($f->error_msg),'debug');
          }
          $tpl = new jTpl();
          $tpl->assign('form', $form);
          $tpl->assign('photos', $photos);
          $rep->body->assign('MAIN', $tpl->fetch('photos'));
          $rep->body->assign('pages', (int) $photos_search['pages']);
          $rep->body->assign('page', (int) $photos_search['page']);
        } else {
          $rep->body->assign('MAIN', jLocale::get("osmflickr~default.message.auth.identify"));
        }

        return $rep;
    }

    /**
    *
    */
    function search() {
      if ( $this->param('clear') != '' )
        $form = jForms::destroy("osmflickr~search");
      // Get the search form
      $form = jForms::get("osmflickr~search");
      if ( !$form ) {
        $form = jForms::create("osmflickr~search");
        $form->setData('hasgeo', -1);
        $form->setData('hasosm', '-1');
      }
      // Init the search Form with the request
      if ( $this->boolParam('search') && $this->param('clear') == '')
        $form->initFromRequest();
      if ( $this->intParam('page') )
        $form->setData('page', $this->param('page') );

      $rep = $this->getResponse('redirect');
      $rep->action = 'osmflickr~auth:index';
      return $rep;
    }

    /**
    *
    */
    function in() {
      $f = jClasses::getService('osmflickr~phpFlickr');
      if (! $f->isConnected() ) {
        $callback = jUrl::getFull('osmflickr~auth:in_callback');
        $url = $f->getRequestToken($callback);
        if ($url) {
          $rep = $this->getResponse('redirectUrl');
          $rep->url = $url;
          return $rep;
        } else {
          jMessage::add($f->getErrorCode().': '.implode(',', $f->getErrorMsg()), 'error');
          $rep = $this->getResponse('redirect');
          $rep->action = 'osmflickr~default:index';
          return $rep;
        }
      }
      $rep = $this->getResponse('redirect');
      $rep->action = 'osmflickr~auth:index';
      return $rep;
    }

    /**
    *
    */
    function in_callback() {
      $rep = $this->getResponse('redirect');
      $f = jClasses::getService('osmflickr~phpFlickr');
      if ( ! $f->getAccessToken() ) {
        jMessage::add($f->getErrorCode().': '.implode(',', $f->getErrorMsg()), 'error');
        $rep->action = 'osmflickr~default:index';
      }
      $rep->action = 'osmflickr~auth:index';
      return $rep;
    }

    /**
    *
    */
    function out() {
      $f = jClasses::getService('osmflickr~phpFlickr');
      $f->setOauthToken('','');
      unset($_SESSION['FLICKR_USER']);
      $rep = $this->getResponse('redirect');
      $rep->action = 'osmflickr~default:index';
      return $rep;
    }
}
