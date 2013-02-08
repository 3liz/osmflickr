<?php
/**
* @package   osmflickr
* @subpackage 
* @author    your name
* @copyright 2011 your name
* @link      http://www.yourwebsite.undefined
* @license    All rights reserved
*/


require_once (JELIX_LIB_CORE_PATH.'response/jResponseHtml.class.php');

class myHtmlResponse extends jResponseHtml {

    public $bodyTpl = 'osmflickr~main';

    function __construct() {
        parent::__construct();

        $bp = jApp::config()->urlengine['basePath'];

        $this->title = 'OsmFlickr';

        // CSS
        $this->addCSSLink($bp.'css/jquery-ui-1.8.23.custom.css');
        $this->addCSSLink($bp.'css/bootstrap.css');
        $this->addCSSLink($bp.'css/bootstrap-responsive.css');

        // META
        $this->addMetaDescription('');
        $this->addMetaKeywords('');
        $this->addHeadContent('<meta name="Revisit-After" content="10 days" />');
        $this->addHeadContent('<meta name="Robots" content="all" />');
        $this->addHeadContent('<meta name="Rating" content="general" />');
        $this->addHeadContent('<meta name="Distribution" content="global" />');
        $this->addHeadContent('<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />');

        $this->addJSLink($bp.'js/jquery-1.8.0.min.js');
        $this->addJSLink($bp.'js/bootstrap.js');
        $this->addJSLink($bp.'js/jquery-ui-1.8.23.custom.min.js');

        // Include your common CSS and JS files here
    }

    protected function doAfterActions() {
        // Include all process in common for all actions, like the settings of the
        // main template, the settings of the response etc..

        $this->body->assignIfNone('MAIN','<p>no content</p>');
    }
}
