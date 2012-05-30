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
 * Helper for Google authentication adapter
 * Provides Sign-in with Google OAuth 2.0
 *
 * In Controller, typically set up:
 *     function beforeFilter() {
 *         $this->helpers['SocialSignIn.Google'] = 
 *             array('api_key' => '__GOOGLE_APP_ID__');
 *
 * In View template:
 * return Sign in link
 *     $this->Google->signin('http://HOST/login', 'login');
 *
 * @package SocialSignIn
 * @subpackage View.Helper
 * @since 2.0
 *
 */
class GoogleHelper extends AppHelper {
    public $client_id;
    public $redirect_uri;

    function __construct(View $view, $settings = array()) {
        parent::__construct($view, $settings);
        foreach ($settings as $key => $value) {
            $this->$key = $value;
        }
    }

    /*
     * Signin with Google
     *
     * @param string $redirect_uri redirect_uri passed to Google API and redirected after sigined-in.
     * @param string $text Anchor text of link
     * @param array $scope Scope to pass to Google API
     * 
     * @return string HTML code to sign in with Facebook
     */
    function signin($text = 'Login', $redirect_uri = null, $scope = array()) {
        if (is_null($redirect_uri)) {
            $redirect_uri = $this->redirect_uri;
        }
        $scope = array_merge($scope, array(
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile'
            ));
        $scope = array_map('urlencode', $scope);
        $output = '<a href="https://accounts.google.com/o/oauth2/auth?'.
            (is_array($scope) ? 'scope='. implode('+', $scope) : '').
            '&status=%2Fprofile' . 
            '&redirect_uri=' . urlencode($redirect_uri) .
            '&response_type=code' .
            '&client_id=' . $this->client_id .
            '">' . 
            $text .
            "</a>\n";
        return $output;
    }
}
