<?php
/**
 * Single Sign In Plugin for CakePHP 2.x
 *
 * @copyright Copyright 2012, News2u Corporation
 * @link http://www.news2u.com
 * @license  All Rights Reserved
 * @package SocialSignIn
 */

App::uses('BaseAuthenticate', 'Controller/Component/Auth');
App::uses('Session', 'Controller/Component');

/**
 * Twitter authentication adapter for AuthComponent of CakePHP
 * Provides Sign-in with Twitter using OAuth 1.0
 *
 * @package SocialSignIn
 * @subpackage Controller.Component.Auth
 * @since 2.0
 */
class TwitterAuthenticate extends BaseAuthenticate {
    /**
     * $setting['fields']['username'] :  Column name of Twitter User ID.
     * $setting['api_key'] :  API Key of Twitter Application
     * $setting['secret_key'] :  Secret Key of Twitter Application
     */
    public $settings = array(
        'fields' => array(
            'username' => 'twitter_user_id',
            'password' => 'password', // necessary for compatibility.
        ),
        'userModel' => 'User',
        'scope' => array(),
        'consumer_key' => '',
        'consumer_secret' => '',
        'session' => '',
    );
    public $access_token;

    public function __construct(ComponentCollection $collection, $settings) {
        parent::__construct($collection, $settings);
    }

    /** 
     * Authenticate a user using Twitter Auth Cookie.
     *
     * @param CakeRequest $request The request to authenticate with.
     * @param CakeResponse $response The response to add headers to.
     * @return mixed Either false on failure, or an array of user data on success.
     */
    public function authenticate(CakeRequest $request, CakeResponse $response) {
        if ($user = $this->getUser($request)) {
            $userModel = $this->settings['userModel'];
            list($plugin, $model) = pluginSplit($userModel);
            $fields = $this->settings['fields'];
            $conditions = array($model . '.' . $fields['username'] => $user->id);
            
            if (!empty($this->settings['scope'])) {
                $conditions = array_merge($conditions, $this->settings['scope']);
            }
            $result = ClassRegistry::init($userModel)->find('first', array(
                'conditions' => $conditions,
                'recursive' => 0
            ));
            if (empty($result) || empty($result[$model])) {
                $session_name = $this->settings['session'];
                SessionComponent::write($session_name . '.User', $user);
                return false;
            }
            unset($result[$model][$fields['password']]);

            $user->id = $result[$model]['_id'];
            $session_name = $this->settings['session'];
            CakeSession::write($session_name, $user);

            return $result[$model];
        }
        return false;
    }

    /** 
     * Retrive Twitter auth data in Cookie set by Twitter JSSDK.
     * 
     * @param CakeRequest $request Request object.
     * @return mixed Either false or an object of user information of Twitter
     */
    public function getUser(CakeRequest $request) {
        $api = Configure::read('SocialSignIn.API.Twitter');

        // $request_token_url = 'http://api.twitter.com/oauth/request_token';
        // $access_token_url = "http://twitter.com/oauth/access_token";
        // $authorize_url="http://twitter.com/oauth/authorize";

        $session_name = $this->settings['session'];
        $s = SessionComponent::read($session_name);

        // if already authenticated, user object is stored in the session
        if (isset($s['User']) && is_object($s['User'])) {
            return $s['User'];
        }
        
        if (isset($request->query['oauth_token']) && isset($s['secret'])) {
            $oauth = new OAuth($this->settings['consumer_key'], $this->settings['consumer_secret'], OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);

            $oauth->setToken($request->query['oauth_token'],$s['secret']);
            $access_token_info = $oauth->getAccessToken($api['access_token_url']);
            if ($access_token_info['oauth_token']) {
                $oauth->setToken($access_token_info['oauth_token'],$access_token_info['oauth_token_secret']);
                $data = $oauth->fetch($api['fetch_url']);
                $user = json_decode($oauth->getLastResponse());
                return $user;
            }
        }
        return false;
    }
    
    public function logout() {
        // just erase data in Session
        $session_name = $this->settings['session'];
        SessionComponent::delete($session_name);
        return true;
    }
}
