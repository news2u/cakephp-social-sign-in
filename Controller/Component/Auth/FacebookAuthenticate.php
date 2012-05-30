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
 * Facebook authentication adapter for AuthComponent of CakePHP
 * Provides Sign-in with Facebook using OAuth 2.0
 *
 * @package SocialSignIn
 * @subpackage Controller.Component.Auth
 * @since 2.0
 */
class FacebookAuthenticate extends BaseAuthenticate {
    public $settings = array(
        'fields' => array(
            'username' => 'facebook_user_id',
            'password' => 'password',
        ),
        'userModel' => 'User',
        'scope' => array(),
        'redirect_uri' => '',
        'app_id' => '',
        'app_secret' => '',
        'type' => 'server', // 'server' or 'client'
        'session' => 'FacebookAuthenticate',
    );
    public $access_token;

    public function __construct(ComponentCollection $collection, $settings) {
        parent::__construct($collection, $settings);
    }

    public function authenticate(CakeRequest $request, CakeResponse $response) {
        if ($this->settings['type'] == 'server') {
            // Server Side Flow
            $this->access_token = $this->_og_retrive_access_token($request);
            if ($this->access_token) {
                $user = $this->_og_retrive_userdata();
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
        } else {
            // Client Side Flow
            // Not implemented yet.
        }
        return false;
    }

    /**
     * retrives access token
     *
     * @param CakeRequest $request
     * @return mixed Either false on failure, or string the access_token facebook.
     */
    private function _og_retrive_access_token(CakeRequest $request) {
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
        $url = 'https://graph.facebook.com/oauth/access_token?'.
            implode('&', array(
                    'client_id=' . $this->settings['app_id'],
                    'client_secret=' . $this->settings['app_secret'],
                    'redirect_uri=' . urlencode($this->settings['redirect_uri']) , 
                    'code=' . $code,
                ));
        //debug($url); 
        if ($res = @file_get_contents($url)) {
            $params = explode('&', $res);
            foreach ($params as $param) {
                list($key, $value) = explode('=', $param);
                if ($key == 'access_token') {
                    $access_token = $value;
                }
            }
        } 
        return $access_token;
    }

    /* 
     * Retrives userdata
     *
     * @return Object $user
     */
    private function _og_retrive_userdata() {
        $url = 'https://graph.facebook.com/me?' .
            'access_token=' . $this->access_token;
        $res = @file_get_contents($url);
        $user = json_decode($res);
        return $user;
    }

    public function login_url() {
        $url = 'http://www.facebook.com/dialog/oauth?' . 
            implode('&', array(
                'client_id='. $this->settings['app_id'], 
                'redirect_uri='. $this->settings['redirect_uri']
                ));
        return $url;
    }

    /* logout from Facebook.
     *   kick fb logout page with access_token
     * @return true
     */
    public function logout() {
        $session_name = $this->settings['session'];
        if ($user = CakeSession::read($session_name)) {
            $this->access_token = $user->access_token;
        }
        if ($this->access_token) {
            $url = "https://www.facebook.com/logout.php?next=" .
                urlencode(Router::url('/', true)) . 
                "&access_token=".$this->access_token;
            $res = @file_get_contents($url);
            $this->access_token = false;
        }
        $res = CakeSession::delete($session_name);
        return true;
    }

}
