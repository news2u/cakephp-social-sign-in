# SocialSignin Plugin

## About

CakePHP 2.0 以降の AuthenticateComponent として動作する Facebook, Twitter, Google, Linkedin のアカウントを使ってログイン認証するためのプラグインです。
A CakePHP plugin to sign in with Facebook, Twitter, Google and Linkedin account.  

## Target APIs

* Facebook
* Linkedin
* Twitter
* Google Account (OAuth 2.0 only)

## Requirements

* CakePHP 2.0/2.1
* PECL oauth extension
* service API keys to work with

## Installation

To install to plugin directoroy of your CakePHP application

    cd my/app/Plugin
    git clone git@github.com:news2u/cakephp-social-sign-in.git SocialSignIn

## How to use

Works as an Authenticate Component.  The below is sample setting of Facebook sign in.

    class AppController extends Controller {
        ...
        public function beforeFilter() {
            ....
            $this->Auth->authenticate = array(
                'SocialSignIn.Facebook' => array(
                    'userModel' => 'User',
                    'fields' => array('username' => 'facebook_user_id'),
                    'app_id' => '__YOUR_APP_ID__',
                    'app_secret' => '__YOUR_APP_SECRET__',
                    'redirect_uri' => '__YOUR_APP_LOGIN_URI__',
                    'session' => 'FaecbookAuthenticate',
                )
            );
        }
        $this->helpers['SocialSignIn.Facebook'] = array(
            'app_id' => '__YOUR_APP_ID__',
            'redirect_uri' => '__YOUR_APP_LOGIN_URI__',
        );
        ....
    }

And put "Sign in with Facebook" link in your Login page

    $this->Facebook->signin(__('Sigin in with Facebook account'));

## How to connect with user

Authenticate component stores each Social account information to Session, both whether user is found or not.

    $facebook = $this->Session->read('FacebookAuthenticate');
    $this->User->saveField('facebook_user_id', $facebook->id);

## How to know user using which account to sign in.

CakePHP does not pass the infomation which authentication component is used to login directly.  If it needed the below code may help.

    private function _login_method() {
        $login_method = null;
        $objs = $this->Auth->constructAuthenticate();
        foreach ($objs as $obj) {
             preg_match(/^(.+)Authenticate/', get_class($obj), $m);
             $name = $m[1];
             if ($obj->authenticate($this->request, $this->response)) {
                 $login_method = $name;
             ]
        }
        return $login_method;
    }

## License

Copyright 2012 News2u Corporation
MIT License (http://www.opensource.org/licenses/mit-license.php)
