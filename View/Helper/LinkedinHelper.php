<?php
/** 
 * Single Sign In plugin for CakePHP 2.x
 *
 * @copyright Copyright 2012 News2u Corporation
 * @package SocialSignIn
 * 
 */

App::uses('AppHelper', 'View/Helper');

/**
 * Helper for Linkedin authentication adapter
 * Provides Sign-in with Linkedin using OAuth 2.0 / JSAPI.
 *
 * In Controller, typically set up:
 *     function beforeFilter() {
 *         $this->helpers['SocialSignIn.Linkedin'] = 
 *             array('api_key' => '__LINKEDIN_API_KEY__');
 *
 * In View template:
 * return Sign in link
 *     $this->Linkedin->signin('http://HOST/login', 'login');
 *
 * And Sign out is just JS code,
 *     $this->Linkedin->signout();
 *
 * @package SocialSignIn
 * @subpackage View.Helper
 * @since 2.0
 *
 */
class LinkedinHelper extends AppHelper {
    public $api_key;
    public $redirect_uri;

    function __construct(View $view, $settings = array()) {
        parent::__construct($view, $settings);
        foreach ($settings as $key => $value) {
            $this->$key = $value;
        }
    }

    /*
     * Signin with Linkedin
     *
     * @param string $redirect_uri redirect_uri passed to linkedin API and redirected after sigined-in.
     * @param string $text Anchor text of link, or button JSAPI codes if not provided.
     * 
     * @return string HTML/JS code to sign in with Linkedin
     */
    function signin($text = null, $redirect_uri = null) {
        if (is_null($redirect_uri)) {
            $redirect_uri = $this->redirect_uri;
        }
        if (is_null($text)) {
            $output = '<script type="in/Login"></script>';
        } else {
            $output = '<a href="javascript:IN.UI.Authorize().place();void(0);">'.$text.'</a>';
        }
        return $this->__jsLoader(false) . $output . <<< EOT
<script type="text/javascript">
IN.Event.on(IN, 'auth', onLinkedInAuth);
function onLinkedInAuth() {
    location.href = '$redirect_uri';
}
</script>
EOT;
    }

    /*
     * Signout from Linkedin
     *
     * @return string HTML/JS code to sign out with Linkedin
     */
    function signout() {
        return $this->__jsLoader(true, 'onLinkedInLogout') . <<< EOT
<script type="text/javascript">
function onLinkedInLogout() {
     if (IN.User.isAuthorized()) {IN.User.logout();}
}
</script>
EOT;
    }

    /*
     * JSAPI loading code
     *
     * @param boolean $authorize 
     * @param string $onLoad function name of javascipt called back at onLoad.
     * @return string HTML/JS code to sign out with Linkedin
     */
    private function __jsLoader($authorize = true, $onLoad = null) {
        $authorize = $authorize ? 'true' : 'false';
        $onLoad = is_null($onLoad) ? '' : 'onLoad: '.$onLoad;
        return <<< EOT
<script type="text/javascript" src="https://platform.linkedin.com/in.js">
   api_key: $this->api_key
   authorize: $authorize
   credentials_cookie: true
   $onLoad
</script>
EOT;
    }
}