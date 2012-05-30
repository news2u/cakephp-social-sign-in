# SocialSignin Plugin

## 概要

CakePHP 2.0 以降の AuthenticateComponent として動作する Facebook, Twitter, Google, Linkedin のアカウントを使ってログイン認証するためのプラグインです。

## 対応 API

* Facebook
* Linkedin
* Twitter
* Google Account (OAuth 2.0 only)

## 動作環境

* CakePHP 2.0/2.1
* PECL oauth extension
* 各ソーシャルアカウントの API アカウント

## インストール方法

SocialSignIn プラグインを利用したい CakePHP のアプリケーションフォルダにインストールします。

## 使い方 (サインイン)

通常の Authenticate Component と同様に Controller class で設定します。例えば、Facebook の場合は次の通りです。

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

ログインページには、ヘルパーを使ってログインページへのリンクを設置します。

    $this->Facebook->signin(__('Sigin in with Facebook account'));

## 使い方 (接続)

SocialSignIn Authenticate Component は各サービスのサインインのときに得られるユーザー情報を、ログインの成否に関わらず Session に保存します。このユーザー情報を使用して、ユーザー情報を特定のユーザーとひもづけることができます。

    $facebook = $this->Session->read('FacebookAuthenticate');
    $this->User->saveField('facebook_user_id', $facebook->id);

## 制限 

複数の Authenticate Component を利用する場合、どの Component でログインしたのかを直接知る方法は CakePHP では用意されていないと思います。どうしても必要である場合には、コントローラーで以下のようなコードを実行することで知ることができます。

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

## 著作権・ライセンス

Copyright 2012 News2u Corporation
MIT License (http://www.opensource.org/licenses/mit-license.php)
