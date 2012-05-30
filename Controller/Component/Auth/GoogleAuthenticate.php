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
// App::uses('Session', 'Controller/Component');
App::uses('CakeSession', 'Model/Datasource');

/**
 * Google authentication adapter for AuthComponent of CakePHP
 * Provides Sign-in with Facebook using OAuth 2.0
 * 
 * @package SocialSignIn
 * @subpackage Controller.Component.Auth
 * @since 2.0
 */
class GoogleAuthenticate extends BaseAuthenticate {
    public $settings = array(
        'fields' => array(
            'username' => 'google_user_id',
            'password' => 'password',
        ),
        'userModel' => 'User',
        'scope' => array(),
        'redirect_uri' => '',
        'client_id' => '', 
        'client_secret' => '',
        'session' => 'GoogleAuthenticate',
    );
    public $access_token;

    public function __construct(ComponentCollection $collection, $settings) {
        parent::__construct($collection, $settings);
    }

    public function authenticate(CakeRequest $request, CakeResponse $response) {
        // Server Side Flow
        $this->access_token = $this->_retrive_access_token($request);
        if ($this->access_token) {
            $user = $this->_retrive_userdata();
            $user->access_token = $this->access_token;
                       
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
                CakeSession::write($session_name, $user);
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
     * retrives access token
     *
     * @param CakeRequest $request
     * @return mixed Either false on failure, or string the access_token facebook.
     */
    private function _retrive_access_token(CakeRequest $request) {
        if ($this->access_token) {
            return $this->access_token;
        }

        $session_name = $this->settings['session'];
        if ($user = CakeSession::read($session_name)) {
            $this->access_token = $user->access_token;
            return $this->access_token;
        }
        if (!isset($request->query['code']))
            return false;
        $code = $request->query['code'];
        $access_token = false;
        $url = 'https://accounts.google.com/o/oauth2/token';
        $query = implode('&', array(
                    'code=' . $code,
                    'client_id=' . $this->settings['client_id'],
                    'client_secret=' . $this->settings['client_secret'],
                    'redirect_uri=' . urlencode($this->settings['redirect_uri']) , 
                    'grant_type=authorization_code'
            ));

        $headers = array(
            "Content-Type: application/x-www-form-urlencoded",
            "Content-Length: ".strlen($query)
        );
        $context = array(
            'http' => array(
                "method"  => "POST",
                "header"  => implode("\r\n", $headers),
                "content" => $query
            )
        );

        if ($res = @file_get_contents($url, false, stream_context_create($context))) {
            $params = json_decode($res);
            $access_token = $params->access_token;
        }
        return $access_token;
    }

    /* 
     * Retrives userdata
     *
     * @return Object $user
     */
    private function _retrive_userdata() {
        $url = 'https://www.googleapis.com/oauth2/v1/userinfo?'.
            'access_token='.$this->access_token;
        $res = @file_get_contents($url);
        $user = json_decode($res);
        return $user;
    }

    /* logout from Google.
     * 
     * @return true
     */
    public function logout() {
        // just remove access_token hold in Session.
        $session_name = $this->settings['session'];
        $res = CakeSession::delete($session_name);
        $this->access_token = false;
        return true;
    }

}
