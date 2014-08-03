<?php
/* phpFlickr Class 3.1
 * Written by Dan Coulter (dan@dancoulter.com)
 * Project Home Page: http://phpflickr.com/
 * Released under GNU Lesser General Public License (http://www.gnu.org/copyleft/lgpl.html)
 * For more information about the class and upcoming tools and toys using it,
 * visit http://www.phpflickr.com/
 *
 *	 For installation instructions, open the README.txt file packaged with this
 *	 class. If you don't have a copy, you can see it at:
 *	 http://www.phpflickr.com/README.txt
 *
 *	 Please submit all problems or questions to the Help Forum on my Google Code project page:
 *	 http://code.google.com/p/phpflickr/issues/list
 * 
 *   Authentification Oauth added by DantSu
 *   http://www.asociaux.fr - http://www.dantsu.com
 *   
 *   Jelix adaptation by 3Liz
 *
 */
require('flickrUser.class.php');
class phpFlickr {
  var $api_key;
  var $secret;

  var $rest_endpoint = 'https://api.flickr.com/services/rest/';
  var $upload_endpoint = 'https://api.flickr.com/services/upload/';
  var $replace_endpoint = 'https://api.flickr.com/services/replace/';
  var $oauthrequest_endpoint = 'https://www.flickr.com/services/oauth/request_token/';
  var $oauthauthorize_endpoint = 'https://www.flickr.com/services/oauth/authorize/';
  var $oauthaccesstoken_endpoint = 'https://www.flickr.com/services/oauth/access_token/';
  var $req;
  var $response;
  var $parsed_response;
  var $last_request = null;
  var $die_on_error;
  var $error_code;
  Var $error_msg;
  var $oauth_token;
  var $oauth_secret;
  var $php_version;
  var $custom_post = null;


  function phpFlickr () {
    $monfichier = jApp::configPath('flickr.ini.php');
    $ini = new jIniFileModifier ($monfichier);
    $api_key = $ini->getValue('api_key');
    $api_key_secret = $ini->getValue('api_key_secret');
    $die_on_error = false;
    //The API Key must be set before any calls can be made.  You can
    //get your own at https://www.flickr.com/services/api/misc.api_keys.html
    $this->api_key = $api_key;
    $this->secret = $api_key_secret;
    $this->die_on_error = $die_on_error;
    $this->service = "flickr";

    //Find the PHP version and store it for future reference
    $this->php_version = explode("-", phpversion());
    $this->php_version = explode(".", $this->php_version[0]);

    if (isset($_SESSION['FLICKR_USER']) && $_SESSION['FLICKR_USER']->oauth_token != ''){
      $u = $_SESSION['FLICKR_USER'];
      $this->oauth_token = $u->oauth_token;
      $this->oauth_secret = $u->oauth_secret;
    }
  }

  function setCustomPost ( $function ) {
    $this->custom_post = $function;
  }

  function post ($data, $url='') {

    if($url == '')
      $url = $this->rest_endpoint;

    if ( !preg_match("|http://(.*?)(/.*)|", $url, $matches) ) {
      die('There was some problem figuring out your endpoint');
    }

    if ( function_exists('curl_init') ) {
      // Has curl. Use it!
      $curl = curl_init($url);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($curl);
      curl_close($curl);
    } else {
      // Use sockets.
      foreach ( $data as $key => $value ) {
        $data[$key] = $key . '=' . urlencode($value);
      }

      $data = implode('&', $data);

      $fp = @pfsockopen($matches[1], 80);
      if (!$fp) {
        die('Could not connect to the web service');
      }
      fputs ($fp,'POST ' . $matches[2] . " HTTP/1.1\n");
      fputs ($fp,'Host: ' . $matches[1] . "\n");
      fputs ($fp,"Content-type: application/x-www-form-urlencoded\n");
      fputs ($fp,"Content-length: ".strlen($data)."\n");
      fputs ($fp,"Connection: close\r\n\r\n");
      fputs ($fp,$data . "\n\n");
      $response = "";
      while(!feof($fp)) {
        $response .= fgets($fp, 1024);
      }
      fclose ($fp);

      $chunked = false;
      $http_status = trim(substr($response, 0, strpos($response, "\n")));
      if ( $http_status != 'HTTP/1.1 200 OK' ) {
        die('The web service endpoint returned a "' . $http_status . '" response');
      }
      if ( strpos($response, 'Transfer-Encoding: chunked') !== false ) {
        $temp = trim(strstr($response, "\r\n\r\n"));
        $response = '';
        $length = trim(substr($temp, 0, strpos($temp, "\r")));
        while ( trim($temp) != "0" && ($length = trim(substr($temp, 0, strpos($temp, "\r")))) != "0" ) {
          $response .= trim(substr($temp, strlen($length)+2, hexdec($length)));
          $temp = trim(substr($temp, strlen($length) + 2 + hexdec($length)));
        }
      } elseif ( strpos($response, 'HTTP/1.1 200 OK') !== false ) {
        $response = trim(strstr($response, "\r\n\r\n"));
      }
    }
    return $response;
  }

  function request ($command, $args = array())
  {
    //Sends a request to Flickr's REST endpoint via POST.
    if (substr($command,0,7) != "flickr.") {
      $command = "flickr." . $command;
    }

    //Process arguments, including method and login data.
    $args = array_merge(array("method" => $command, "format" => "php_serial", "api_key" => $this->api_key), $args);
    ksort($args);
    $auth_sig = "";
    $this->last_request = $args;

    foreach ($args as $key => $data) {
      if ( is_null($data) ) {
        unset($args[$key]);
        continue;
      }
      $auth_sig .= $key . $data;
    }
    if (!empty($this->secret)) {
      $api_sig = md5($this->secret . $auth_sig);
      $args['api_sig'] = $api_sig;
    }

    if(!$args = $this->getArgOauth($this->rest_endpoint, $args))
      return false;

    $this->response = $this->post($args);

    /*
     * Uncomment this line (and comment out the next one) if you're doing large queries
     * and you're concerned about time.  This will, however, change the structure of
     * the result, so be sure that you look at the results.
     */
    $this->parsed_response = $this->clean_text_nodes(unserialize($this->response));
    if ($this->parsed_response['stat'] == 'fail') {
      if ($this->die_on_error) die("The Flickr API returned the following error: #{$this->parsed_response['code']} - {$this->parsed_response['message']}");
      else {
        $this->error_code = $this->parsed_response['code'];
        $this->error_msg = $this->parsed_response['message'];
        $this->parsed_response = false;
      }
    } else {
      $this->error_code = false;
      $this->error_msg = false;
    }
    return $this->response;
  }

  function clean_text_nodes ($arr) {
    if (!is_array($arr)) {
      return $arr;
    } elseif (count($arr) == 0) {
      return $arr;
    } elseif (count($arr) == 1 && array_key_exists('_content', $arr)) {
      return $arr['_content'];
    } else {
      foreach ($arr as $key => $element) {
        $arr[$key] = $this->clean_text_nodes($element);
      }
      return($arr);
    }
  }

  function getArgOauth($url, $data) {
    if(!empty($this->oauth_token) && !empty($this->oauth_secret))
    {
      $data['oauth_consumer_key'] = $this->api_key;
      $data['oauth_timestamp'] = time();
      $data['oauth_nonce'] = md5(uniqid(rand(), true));
      $data['oauth_signature_method'] = "HMAC-SHA1";
      $data['oauth_version'] = "1.0";
      $data['oauth_token'] = $this->oauth_token;

      if(!$data['oauth_signature'] = $this->getOauthSignature($url, $data))
        return false;
    }
    return $data;
  }

  function requestOauthToken() {
    if (session_id() == '')
      session_start();

    if(!isset($_SESSION['oauth_tokentmp']) || !isset($_SESSION['oauth_secrettmp']) || 
      $_SESSION['oauth_tokentmp'] == '' ||  $_SESSION['oauth_secrettmp'] == '')
    {
      $callback = 'http://'.$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI'];
      $this->getRequestToken($callback);
      return false;
    }
    else
      return $this->getAccessToken();
  }
        
  function getRequestToken($callback) {
    if (session_id() == '')
      session_start();

    $data = array(
      'oauth_consumer_key' => $this->api_key,
      'oauth_timestamp' => time(),
      'oauth_nonce' => md5(uniqid(rand(), true)),
      'oauth_signature_method' => "HMAC-SHA1",
      'oauth_version' => "1.0",
      'oauth_callback' => $callback
    );

    if(!$data['oauth_signature'] = $this->getOauthSignature($this->oauthrequest_endpoint, $data))
      return false;

    $reponse = $this->oauthResponse($this->post($data, $this->oauthrequest_endpoint));

    if(!isset($reponse['oauth_callback_confirmed']) || $reponse['oauth_callback_confirmed'] != 'true')
    {
      $this->error_code = 'Oauth';
      $this->error_msg = $reponse;
      return false;
    }


    $_SESSION['oauth_tokentmp'] = $reponse['oauth_token'];
    $_SESSION['oauth_secrettmp'] = $reponse['oauth_token_secret'];

    //header("location: ".$this->oauthauthorize_endpoint.'?oauth_token='.$reponse['oauth_token']);

    $this->error_code = '';
    $this->error_msg = '';
    //return true;
    return $this->oauthauthorize_endpoint.'?oauth_token='.$reponse['oauth_token'];
  }
  
  function getAccessToken() {
    if (session_id() == '')
      session_start();

    $this->oauth_token = $_SESSION['oauth_tokentmp'];
    $this->oauth_secret = $_SESSION['oauth_secrettmp'];
    unset($_SESSION['oauth_tokentmp']);
    unset($_SESSION['oauth_secrettmp']);

    if(!isset($_GET['oauth_verifier']) || $_GET['oauth_verifier'] == '')
    {
      $this->error_code = 'Oauth';
      $this->error_msg = 'oauth_verifier is undefined.';
      return false;
    }

    $data = array(
      'oauth_consumer_key' => $this->api_key,
      'oauth_timestamp' => time(),
      'oauth_nonce' => md5(uniqid(rand(), true)),
      'oauth_signature_method' => "HMAC-SHA1",
      'oauth_version' => "1.0",
      'oauth_token' => $this->oauth_token,
      'oauth_verifier' => $_GET['oauth_verifier']
    );

    if(!$data['oauth_signature'] = $this->getOauthSignature($this->oauthaccesstoken_endpoint, $data))
      return false;

    $reponse = $this->oauthResponse($this->post($data, $this->oauthaccesstoken_endpoint));

    if(isset($reponse['oauth_problem']) && $reponse['oauth_problem'] != '')
    {
      $this->error_code = 'Oauth';
      $this->error_msg = display_array($reponse);
      return false;
    }

    $u = new flickrUser();
    $u->oauth_token = $reponse['oauth_token'];
    $u->oauth_secret = $reponse['oauth_token_secret'];
    $u->nsid = urldecode($reponse['user_nsid']);
    $u->name = urldecode($reponse['username']);
    $u->fullname = urldecode($reponse['fullname']);
    $_SESSION['FLICKR_USER'] = $u;

    $this->oauth_token = $reponse['oauth_token'];
    $this->oauth_secret = $reponse['oauth_token_secret'];
    $this->error_code = '';
    $this->error_msg = '';
    return True;
  }
  
  function getOauthSignature($url, $data) {
    if($this->secret == '')
    {
      $this->error_code = 'Oauth';
      $this->error_msg = 'API Secret is undefined.';
      return false;
    }

    ksort($data);

    $adresse = 'POST&'.rawurlencode($url).'&';
    $param = '';
    foreach ( $data as $key => $value )
      $param .= $key.'='.rawurlencode($value).'&';
    $param = substr($param, 0, -1);
    $adresse .= rawurlencode($param);

    return base64_encode(hash_hmac('sha1', $adresse, $this->secret.'&'.$this->oauth_secret, true));
  }

  function oauthResponse($response) {
    $expResponse = explode('&', $response);
    $retour = array();
    foreach($expResponse as $v)
    {
      $expArg = explode('=', $v);
      $retour[$expArg[0]] = $expArg[1];
    }
    return $retour;   
  }

  function setOauthToken ($token, $secret) {
    $this->oauth_token = $token;
    $this->oauth_secret = $secret;
  }
  function getOauthToken () {
    return $this->oauth_token;
  }
  function getOauthSecretToken () {
    return $this->oauth_secret;
  }

  function isConnected () {
    return (isset($_SESSION['FLICKR_USER']) && $_SESSION['FLICKR_USER']->oauth_token != '');
  }
  function getUserSession () {
    if ( ! isset($_SESSION['FLICKR_USER']) )
      $_SESSION['FLICKR_USER'] = new flickrUser();
    return $_SESSION['FLICKR_USER'];
  }

  function setProxy ($server, $port) {
    // Sets the proxy for all phpFlickr calls.
    $this->req->setProxy($server, $port);
  }

  function getErrorCode () {
    // Returns the error code of the last call.  If the last call did not
    // return an error. This will return a false boolean.
    return $this->error_code;
  }

  function getErrorMsg () {
    // Returns the error message of the last call.  If the last call did not
    // return an error. This will return a false boolean.
    return $this->error_msg;
  }

  /* These functions are front ends for the flickr calls */

  function buildPhotoURL ($photo, $size = "Medium") {
    //receives an array (can use the individual photo data returned
    //from an API call) and returns a URL (doesn't mean that the
    //file size exists)
    $sizes = array(
      "square" => "_s",
      "thumbnail" => "_t",
      "small" => "_m",
      "medium" => "",
      "medium_640" => "_z",
      "large" => "_b",
      "original" => "_o"
    );

    $size = strtolower($size);
    if (!array_key_exists($size, $sizes)) {
      $size = "medium";
    }

    if ($size == "original") {
      $url = "https://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['originalsecret'] . "_o" . "." . $photo['originalformat'];
    } else {
      $url = "https://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . $sizes[$size] . ".jpg";
    }
    return $url;
  }

  function getFriendlyGeodata ($lat, $lon) {
    /* I've added this method to get the friendly geodata (i.e. 'in New York, NY') that the
     * website provides, but isn't available in the API. I'm providing this service as long
     * as it doesn't flood my server with requests and crash it all the time.
     */
    return unserialize(file_get_contents('http://phpflickr.com/geodata/?format=php&lat=' . $lat . '&lon=' . $lon));
  }

  function sync_upload ($photo, $title = null, $description = null, $tags = null, $is_public = null, $is_friend = null, $is_family = null) {
    if ( function_exists('curl_init') ) {
      // Has curl. Use it!

      //Process arguments, including method and login data.
      $args = array("api_key" => $this->api_key, "title" => $title, "description" => $description, "tags" => $tags, "is_public" => $is_public, "is_friend" => $is_friend, "is_family" => $is_family);


      ksort($args);
      $auth_sig = "";
      foreach ($args as $key => $data) {
        if ( is_null($data) ) {
          unset($args[$key]);
        } else {
          $auth_sig .= $key . $data;
        }
      }
      if (!empty($this->secret)) {
        $api_sig = md5($this->secret . $auth_sig);
        $args["api_sig"] = $api_sig;
      }

      $args = $this->getArgOauth($this->upload_endpoint, $args);

      $photo = realpath($photo);
      $args['photo'] = '@' . $photo;

      $curl = curl_init($this->upload_endpoint);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($curl);
      $this->response = $response;
      curl_close($curl);

      $rsp = explode("\n", $response);
      foreach ($rsp as $line) {
        if (preg_match('|<err code="([0-9]+)" msg="(.*)"|', $line, $match)) {
          if ($this->die_on_error)
            die("The Flickr API returned the following error: #{$match[1]} - {$match[2]}");
          else {
            $this->error_code = $match[1];
            $this->error_msg = $match[2];
            $this->parsed_response = false;
            return false;
          }
        } elseif (preg_match("|<photoid>(.*)</photoid>|", $line, $match)) {
          $this->error_code = false;
          $this->error_msg = false;
          return $match[1];
        }
      }

    } else {
      die("Sorry, your server must support CURL in order to upload files");
    }

  }

  function async_upload ($photo, $title = null, $description = null, $tags = null, $is_public = null, $is_friend = null, $is_family = null) {
    if ( function_exists('curl_init') ) {
      // Has curl. Use it!

      //Process arguments, including method and login data.
      $args = array("async" => 1, "api_key" => $this->api_key, "title" => $title, "description" => $description, "tags" => $tags, "is_public" => $is_public, "is_friend" => $is_friend, "is_family" => $is_family);


      ksort($args);
      $auth_sig = "";
      foreach ($args as $key => $data) {
        if ( is_null($data) ) {
          unset($args[$key]);
        } else {
          $auth_sig .= $key . $data;
        }
      }
      if (!empty($this->secret)) {
        $api_sig = md5($this->secret . $auth_sig);
        $args["api_sig"] = $api_sig;
      }

      $args = $this->getArgOauth($this->upload_endpoint, $args);

      $photo = realpath($photo);
      $args['photo'] = '@' . $photo;

      $curl = curl_init($this->upload_endpoint);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($curl);
      $this->response = $response;
      curl_close($curl);

      $rsp = explode("\n", $response);
      foreach ($rsp as $line) {
        if (preg_match('|<err code="([0-9]+)" msg="(.*)"|', $line, $match)) {
          if ($this->die_on_error)
            die("The Flickr API returned the following error: #{$match[1]} - {$match[2]}");
          else {
            $this->error_code = $match[1];
            $this->error_msg = $match[2];
            $this->parsed_response = false;
            return false;
          }
        } elseif (preg_match("|<ticketid>(.*)</|", $line, $match)) {
          $this->error_code = false;
          $this->error_msg = false;
          return $match[1];
        }
      }
    } else {
      die("Sorry, your server must support CURL in order to upload files");
    }
  }

  // Interface for new replace API method.
  function replace ($photo, $photo_id, $async = null) {
    if ( function_exists('curl_init') ) {
      // Has curl. Use it!

      //Process arguments, including method and login data.
      $args = array("api_key" => $this->api_key, "photo_id" => $photo_id, "async" => $async);

      ksort($args);
      $auth_sig = "";
      foreach ($args as $key => $data) {
        if ( is_null($data) ) {
          unset($args[$key]);
        } else {
          $auth_sig .= $key . $data;
        }
      }
      if (!empty($this->secret)) {
        $api_sig = md5($this->secret . $auth_sig);
        $args["api_sig"] = $api_sig;
      }

      $photo = realpath($photo);
      $args['photo'] = '@' . $photo;

      $args = $this->getArgOauth($this->replace_endpoint, $args);

      $curl = curl_init($this->replace_endpoint);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($curl);
      $this->response = $response;
      curl_close($curl);

      if ($async == 1)
        $find = 'ticketid';
      else
        $find = 'photoid';

      $rsp = explode("\n", $response);
      foreach ($rsp as $line) {
        if (preg_match('|<err code="([0-9]+)" msg="(.*)"|', $line, $match)) {
          if ($this->die_on_error)
            die("The Flickr API returned the following error: #{$match[1]} - {$match[2]}");
          else {
            $this->error_code = $match[1];
            $this->error_msg = $match[2];
            $this->parsed_response = false;
            return false;
          }
        } elseif (preg_match("|<" . $find . ">(.*)</|", $line, $match)) {
          $this->error_code = false;
          $this->error_msg = false;
          return $match[1];
        }
      }
    } else {
      die("Sorry, your server must support CURL in order to upload files");
    }
  }



        /*******************************

        To use the phpFlickr::call method, pass a string containing the API method you want
        to use and an associative array of arguments.  For example:
            $result = $f->call("flickr.photos.comments.getList", array("photo_id"=>'34952612'));
        This method will allow you to make calls to arbitrary methods that haven't been
        implemented in phpFlickr yet.

        *******************************/

  function call ($method, $arguments) {
    foreach ( $arguments as $key => $value ) {
      if ( is_null($value) ) unset($arguments[$key]);
    }
    $this->request($method, $arguments);
    return $this->parsed_response ? $this->parsed_response : false;
  }

  function oauth_checkToken () {
    $this->request("flickr.auth.oauth.checkToken", array("api_key"=>$this->api_key,"oauth_token"=>$this->oauth_token));
    return $this->parsed_response ? $this->parsed_response : false;
  }

  /* Photos Methods */
  function photos_addTags ($photo_id, $tags) {
    /* https://www.flickr.com/services/api/flickr.photos.addTags.html */
    $this->request("flickr.photos.addTags", array("photo_id"=>$photo_id, "tags"=>$tags), TRUE);
    return $this->parsed_response ? true : false;
  }

  function photos_removeTag ($tag_id) {
    /* https://www.flickr.com/services/api/flickr.photos.removeTag.html */
    $this->request("flickr.photos.removeTag", array("tag_id"=>$tag_id), TRUE);
    return $this->parsed_response ? true : false;
  }

  function photos_search ($args = array()) {
    /* This function strays from the method of arguments that I've
     * used in the other functions for the fact that there are just
     * so many arguments to this API method. What you'll need to do
     * is pass an associative array to the function containing the
     * arguments you want to pass to the API.  For example:
     *   $photos = $f->photos_search(array("tags"=>"brown,cow", "tag_mode"=>"any"));
     * This will return photos tagged with either "brown" or "cow"
     * or both. See the API documentation (link below) for a full
     * list of arguments.
     */

    /* https://www.flickr.com/services/api/flickr.photos.search.html */
    $this->request("flickr.photos.search", $args);
    return $this->parsed_response ? $this->parsed_response['photos'] : false;
  }

  function photos_getInfo ($photo_id, $secret = NULL, $humandates = NULL, $privacy_filter = NULL, $get_contexts = NULL) {
    /* https://www.flickr.com/services/api/flickr.photos.getInfo.html */
    return $this->call('flickr.photos.getInfo', array('photo_id' => $photo_id, 'secret' => $secret, 'humandates' => $humandates, 'privacy_filter' => $privacy_filter, 'get_contexts' => $get_contexts));
  }
}
