<?php
/** 
 * This file contains the information of API.
 *
 * @copyright Copyright 2012 News2u Corporation
 * @package SocialSignIn
 * @subpackage Config
 */

/** Twitter API endpoints */
Configure::write('SocialSignIn.API.Twitter', array(
        'request_token_url' => 'http://api.twitter.com/oauth/request_token',
        'access_token_url' => "http://twitter.com/oauth/access_token",
        'authorize_url' => "http://twitter.com/oauth/authorize",
        'fetch_url' => "http://twitter.com/account/verify_credentials.json",
    ));

