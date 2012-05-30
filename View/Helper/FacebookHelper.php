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
 * Provides Sign-in with Facebook OAuth 2.0
 *
 * In Controller, typically set up:
 *     function beforeFilter() {
 *         $this->helpers['SocialSignIn.Linkedin'] = 
 *             array('api_key' => '__FACEBOOK_APP_ID__');
 *
 * In View template:
 * return Sign in link
 *     $this->Facebook->signin('http://HOST/login', 'login');
 *
 * And Sign out is just JS code,
 *     $this->Facebook->signout();
 *
 * @package SocialSignIn
 * @subpackage View.Helper
 * @since 2.0
 *
 */
class FacebookHelper extends AppHelper {
    public $app_id;
    public $redirect_uri;

    function __construct(View $view, $settings = array()) {
        parent::__construct($view, $settings);
        foreach ($settings as $key => $value) {
            $this->$key = $value;
        }
    }

    /*
     * Signin with Facebook
     *
     * @param string $redirect_uri redirect_uri passed to linkedin API and redirected after sigined-in.
     * @param string $text Anchor text of link
     * 
     * @return string HTML code to sign in with Facebook
     */
    function signin($text = 'Login', $redirect_uri = null, $scope = null) {
        if (is_null($redirect_uri)) {
            $redirect_uri = $this->redirect_uri;
        }
        $output = '<a href="http://www.facebook.com/dialog/oauth?client_id='.
            $this->app_id .
            '&redirect_uri=' . urlencode($redirect_uri) .
            (is_array($scope) ? '&scope=' . implode(',', $scope) : '').
            '">' . 
            $text .
            "</a>\n";
        return $output;
    }
}
