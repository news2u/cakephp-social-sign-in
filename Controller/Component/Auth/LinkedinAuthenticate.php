<?php
/**
 * Single Sign In Plugin for CakePHP 2.x
 *
 * @copyright Copyright 2012, News2u Corporation
 * @link http://www.news2u.com
 * @license  All Rights Reserved
 * @package SingleSignIn
 */

App::uses('BaseAuthenticate', 'Controller/Component/Auth');
App::uses('Session', 'Controller/Component');

/**
 * Linkedin authentication adapter for AuthComponent of CakePHP
 * Provides Sign-in with Linkedin using OAuth 2.0 / JSAPI.
 *
 * @package SocialSignIn
 * @subpackage Controller.Component.Auth
 * @since 2.0
 */
class LinkedinAuthenticate extends BaseAuthenticate {
    /**
     * $settings['fields']['username'] :  Column name of Linkedin User ID.
     * $settings['api_key'] :  API Key of Linkedin Application
     * $settings['secret_key'] :  Secret Key of Linkedin Application
     */
    public $settings = array(
        'fields' => array(
            'username' => 'linkedin_member_id',
            'password' => 'password', // necessary for compatibility.
        ),
        'userModel' => 'User',
        'scope' => array(),
        'api_key' => '',
        'secret_key' => '',
        'session' => 'Auth.LinkedinAuthenticate.User',
    );
    public $access_token;

    public function __construct(ComponentCollection $collection, $settings) {
        parent::__construct($collection, $settings);
    }

    /** 
     * Authenticate a user using Linkedin Auth Cookie.
     *
     * @param CakeRequest $request The request to authenticate with.
     * @param CakeResponse $response The response to add headers to.
     * @return mixed Either false on failure, or an array of user data on success.
     */
    public function authenticate(CakeRequest $request, CakeResponse $response) {
        if ($user = $this->getUser($request)) {
            $this->access_token = $user->access_token;

            $userModel = $this->settings['userModel'];
            list($plugin, $model) = pluginSplit($userModel);
            $fields = $this->settings['fields'];
            $conditions = array($model . '.' . $fields['username'] => $user->member_id);
            
            if (!empty($this->settings['scope'])) {
                $conditions = array_merge($conditions, $this->settings['scope']);
            }
            $result = ClassRegistry::init($userModel)->find('first', array(
                'conditions' => $conditions,
                'recursive' => 0
            ));
            
            if (empty($result) || empty($result[$model])) {
                $session_name = $this->settings['session'];
                SessionComponent::write($session_name, $user);
                return false;
            }
            unset($result[$model][$fields['password']]);
            if (isset($result[$model]['linkedin'])) {
                unset($result[$model]['linkedin']);
            }
            $user->id = $result[$model]['_id'];
            $session_name = $this->settings['session'];
            SessionComponent::write($session_name, $user);
            return $result[$model];
        }
        return false;
    }

    /** 
     * Retrive Linkedin auth data in Cookie set by Linkedin JSSDK.
     * 
     * @param CakeRequest $request Request object.
     * @return mixed Either false or an object of user information of Linkedin
     */
    public function getUser(CakeRequest $request) {
        $cookie_name = 'linkedin_oauth_' . $this->settings['api_key'];
        if (isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name]) {
            $linkedin = json_decode($_COOKIE[$cookie_name]);
            if (isset($linkedin) && isset($linkedin->access_token) && $this->__verifyToken($linkedin)) {
                $oauth = new OAuth($this->settings['api_key'], $this->settings['secret_key']);
                $oauth->fetch('https://api.linkedin.com/uas/oauth/accessToken', 
                    array('xoauth_oauth2_access_token' => $linkedin->access_token), 
                    OAUTH_HTTP_METHOD_POST);
                parse_str($oauth->getLastResponse(), $response);
                $oauth->setToken($response['oauth_token'],$response['oauth_token_secret']);
                $url = 'http://api.linkedin.com/v1/people/~:(id,first-name,last-name,headline,public-profile-url,summary,positions)';
                // $url = 'http://api.linkedin.com/v1/people/~';
                $oauth->fetch($url, array(), OAUTH_HTTP_METHOD_GET, array('x-li-format' => 'json'));
                $profile = json_decode($oauth->getLastResponse());
                $linkedin->profile = $profile;
                return $linkedin;
            }
        }
        return false;
    }
    
    /**
     * Verify Auth data from cookie.
     *
     * @param Object $credentials User data from Linkedin via Cookie
     * @return boolean
     */
    private function __verifyToken($credentials) {
        // reference: https://developer.linkedin.com/documents/exchange-jsapi-tokens-rest-api-oauth-tokens
        if ($credentials->signature_version == 1) {
            if ($credentials->signature_order && is_array($credentials->signature_order)) {
                $base_string = '';
                // build base string from values ordered by signature_order
                foreach ($credentials->signature_order as $key) {
                    if (isset($credentials->$key)) {
                        $base_string .= $credentials->$key;
                    } else {
                        // "missing signature parameter: $key";
                        return false;
                    }
                }
                // hex encode an HMAC-SHA1 string
                $signature =  base64_encode(hash_hmac('sha1', $base_string, $this->settings['secret_key'], true));
                // check if our signature matches the cookie's
                if ($signature == $credentials->signature) {
                    // "signature validation succeeded";
                    return true;
                } else {
                    // "signature validation failed";   
                    return false;
                }
            } else {
                // "signature order missing";
                return false;
            }
        } else {
            // "unknown cookie version";
            return false;
        }
    }
    
    public function logout() {
        $cookie_name = 'linkedin_oauth_' . $this->settings['api_key'];
        ob_start();
        setcookie($cookie_name, '', time()-86400, '/', $_SERVER['HTTP_HOST'], true);
        ob_end_flush();
        $session_name = $this->settings['session'];
        SessionComponent::delete($session_name);
        return true;
    }
}
