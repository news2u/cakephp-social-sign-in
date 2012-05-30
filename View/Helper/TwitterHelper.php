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
 *         $this->helpers['SocialSignIn.Twitter'] = 
 *             array('api_key' => '__TWITTER_API_KEY__');
 *
 * In View template:
 * return Sign in link
 *     $this->Twitter->signin('login');
 *
 * @package SocialSignIn
 * @subpackage View.Helper
 * @since 2.0
 */
class TwitterHelper extends AppHelper {
    public $consumer_key;
    public $consumer_secret;
    public $redirect_uri;
    public $session;


    function __construct(View $view, $settings = array()) {
        parent::__construct($view, $settings);
        foreach ($settings as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Signin URL
     *
     * @param string Anchor text
     * @param string redirect url
     * @return string HTML tag
     */
    public function signin($text = 'Login', $redirect = null, $scope = null) {
        $api = Configure::read('SocialSignIn.API.Twitter');
        $url = Router::url(array(
                'plugin' => 'social_sign_in', 
                'controller' => 'oauth',
                'action' => 'signin'
            ));
        CakeSession::write($this->session.'.callback', $redirect, true);
        $output = '<a href="'.$url.'">' . $text . "</a>\n";
        return $output;
    }
}
