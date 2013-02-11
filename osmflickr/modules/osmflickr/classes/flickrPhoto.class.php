<?php
class flickrPhoto {
    public $id = '';
    public $owner = '';
    public $secret = '';
    public $server = '';
    public $farm = '';
    public $title = '';
    public $ispublic = 0;
    public $isfriend = 0;
    public $isfamily = 0;
    public $info = null;

    function __construct( $id, $secret = '' ) {
      $this->id = $id;
      $this->secret = $secret;
    }

    public function getInfo() {
        $f = jClasses::getService('osmflickr~phpFlickr');
        $photo_info = $f->photos_getInfo($this->id, $this->secret);
        $p = $photo_info['photo'];

        $this->owner = $p['owner']['nsid'];
        $this->secret = $p['secret'];
        $this->server = $p['server'];
        $this->farm = $p['farm'];
        $this->title = $p['title'];
        $this->ispublic = $p['visibility']['ispublic'];
        $this->isfriend = $p['visibility']['isfriend'];
        $this->isfamily = $p['visibility']['isfamily'];
        $this->info = $p;
    }

    public function updateFromSearch( $p ) {
      if ($this->id != $p['id'])
        return false;

      $this->owner = $p['owner'];
      $this->secret = $p['secret'];
      $this->server = $p['server'];
      $this->farm = $p['farm'];
      $this->title = $p['title'];
      $this->ispublic = $p['ispublic'];
      $this->isfriend = $p['isfriend'];
      $this->isfamily = $p['isfamily'];
      return true;
    }

    public function buildURL ( $size = "medium" ) {
      //receives an array (can use the individual photo data returned
      //from an API call) and returns a URL (doesn't mean that the
      //file size exists)
      $sizes = array(
        "square" => "_s",
        "lsquare" => "_q",
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
        $url = "http://farm" . $this->farm . ".static.flickr.com/" . $this->server . "/" . $this->id . "_" . $this->originalsecret . "_o" . "." . $this->originalformat;
      } else {
        $url = "http://farm" . $this->farm . ".static.flickr.com/" . $this->server . "/" . $this->id . "_" . $this->secret . $sizes[$size] . ".jpg";
      }
      return $url;
    }

    public function getPhotopageURL() {
      $url = '#';

      if ( !$this->info )
        return $url;

      foreach ( $this->info['urls']['url'] as $u ) {
        if ( $u['type'] == 'photopage' )
          return urldecode( $u['_content'] );
      }
      return $url;
    }

    public function hasSimpleTags() {
      if ( !$this->info )
        return false;

      foreach ( $this->info['tags']['tag'] as $t ) {
        if ( $t['machine_tag'] == 0 )
          return true;
      }
      return false;
    }

    public function getSimpleTags() {
      $tags = array();

      if ( !$this->info )
        return $tags;

      foreach ( $this->info['tags']['tag'] as $t ) {
        if ( $t['machine_tag'] == 0 )
          $tags[] = (object) array(
            "id"=> $t['id'],
            "author"=> $t['author'],
            "raw"=> $t['raw'],
            "title"=> $t['_content'],
          );
      }
      return $tags;
    }

    public function hasOsmTags() {
      if ( !$this->info )
        return false;

      foreach ( $this->info['tags']['tag'] as $t ) {
        if ( $t['machine_tag'] == 1 && preg_match('/^osm:/', $t['raw'] ) )
          return true;
      }
      return false;
    }

    public function getOsmTags() {
      $tags = array();

      if ( !$this->info )
        return $tags;

      foreach ( $this->info['tags']['tag'] as $t ) {
        if ( $t['machine_tag'] == 1 && preg_match('#^osm:#', $t['raw'] ) ) {
          $matches = array();
          $raw = preg_match('/osm:(?P<osm_type>\w+)=(?P<osm_id>\d+)/', $t['raw'], $matches);;
          $tags[] = (object) array(
            "id"=> $t['id'],
            "author"=> $t['author'],
            "raw"=> $t['raw'],
            "title"=> $t['_content'],
            "osm_id"=> $matches['osm_id'],
            "osm_type"=> $matches['osm_type'],
          );
        }
      }
      return $tags;
    }

    public function hasLocation() {
      if ( !$this->info )
        return false;

      if ( isset($this->info['location']) )
        return true;
      return false;
    }

    public function getLocation() {
      $loc = null;

      if ( !$this->info || !isset($this->info['location']) )
        return $loc;

      $loc = $this->info['location'];
      $loc = (object) array(
        "lat"=>$loc['latitude'],
        "lon"=>$loc['longitude'],
        "acc"=>$loc['accuracy'],
        "country"=>urldecode($loc['country']['_content']),
        "region"=>urldecode($loc['region']['_content']),
        "county"=>urldecode($loc['county']['_content']),
        "locality"=>urldecode($loc['locality']['_content']),
      );
      return $loc;
    }
}
