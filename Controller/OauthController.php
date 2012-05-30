<?php
/** 
 * Single Sign In plugin for CakePHP 2.x
 *
 * @copyright Copyright 2012 News2u Corporation
 * @package SocialSignIn
 * 
 */

/**
 * OauthController
 *
 * callback receiver of Oauth 1.0a
 *
 * @package SocialSignIn
 * @subpackage Controller
 */
class OauthController extends AppController {
    /** Components **/
    public $components = array('Auth', 'Session');
    /** Models, no model is needed */
    public $uses = false;
    /** Session basename */
    public $session_basename;

    /** allow access to callback actions */
    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow('signin', 'callback');
    }

    /*
     * Sign in redirecter
     *
     * @param string $type Name of social servive to be signed in.
     * @return void, redirect to request_token url or just $this->referer();
     */
    function signin($type = 'twitter') {
        $this->autoRender = false;
        
        $type_camel = Inflector::camelize($type);
        $api = Configure::read('SocialSignIn.API');
        if (isset($api[$type_camel])) {
            $api = $api[$type_camel];
        } else {
            // $this->redirect($this->referer());
        }

        $config_base = implode('.', array( 'Auth', $type_camel, '' ));
        $consumer_key = Configure::read($config_base.'consumer_key');
        $consumer_secret = Configure::read($config_base.'consumer_secret');
        $this->session_basename = Configure::read($config_base.'session_name');

        // $oauth_callback = $this->redirect_uri;
        $oauth_callback = Router::url(array('plugin' => 'social_sign_in', 'controller' => 'oauth', 'action' => 'callback'), true);

        $oauth = new OAuth($consumer_key, $consumer_secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
        $request_token_info = $oauth->getRequestToken($api['request_token_url']);
        $this->Session->write($this->session_basename.'.oauth_token', $request_token_info['oauth_token']);
        $this->Session->write($this->session_basename.'.secret', $request_token_info['oauth_token_secret']);
        $url = $api['authorize_url'] . '?oauth_token='.$request_token_info['oauth_token'];
        $this->redirect($url);
    }

    /**
     * Callback receiver
     * @param string Type of Authenticate
     */
    public function callback($type = 'twitter') {
        $this->autoRender = false;
        $type_camel = Inflector::camelize($type);
        $api = Configure::read('SocialSignIn.API');
        if (isset($api[$type_camel])) {
            $api = $api[$type_camel];
        } else {
            // configuration error;
        }

        $config_base = implode('.', array( 'Auth', $type_camel, '' ));
        $this->session_basename = Configure::read($config_base.'session_name');
        $s = $this->Session->read($this->session_basename);

        if (isset($s['callback'])) {
            $callback = $s['callback'];
        } else {
            $callback = '/';
        }
        $callback = Router::url($callback);

        $authenticates = $this->_authenticate();
        $user = $authenticates[$type]->authenticate($this->request, $this->response);

        $this->redirect($callback);
    }

    /**
     * Retrieve AuthComponent
     *
     * @param boolean whether calling authenticate().
     * @return array AuthComponent classes.
     */
    protected function _authenticate($do_auth = false) {
        $login_method = null;
        $objs = $this->Auth->constructAuthenticate();
        foreach ($objs as $obj) {
            preg_match('/^(.+)Authenticate/', get_class($obj), $m);
            $name = strtolower($m[1]);
            $authenticates[$name] = $obj;
            if ($do_auth && $obj->authenticate($this->request, $this->response)) {
                $login_method = $name;
            }
        }
        return $authenticates;
    }
}
