<?php

/**
 * @package    Arc90_Service
 * @subpackage Twitter
 * @author     Matt Williams <matt@mattwilliamsnyc.com>
 * @copyright  Copyright (c) 2008 {@link http://arc90.com Arc90 Inc.}
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * Software License Agreement (BSD License)
 * 
 * Copyright (c) 2008, Arc90 Inc.
 * All rights reserved.
 * 
 * Redistribution and use of this software in source and binary forms, with or
 * without modification, are permitted provided that the following conditions
 * are met:
 * 
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * 
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * 
 * - Neither the name of Arc90 Inc. nor the names of its contributors may be
 *   used to endorse or promote products derived from this software without
 *   specific prior written permission of Arc90 Inc.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @see Arc90_Service_Twitter_Exception
 */
require_once('Twitter/Exception.php');

/**
 * @see Arc90_Service_Twitter_Response
 */
require_once('Twitter/Response.php');

/**
 * Arc90_Service_Twitter provides methods for interacting with the {@link http://twitter.com Twitter} API.
 * Based on Twitter {@link http://groups.google.com/group/twitter-development-talk/web/api-documentation documentation}
 * 
 * NOTE: Support for the $id parameter has been
 * {@link http://groups.google.com/group/twitter-development-talk/browse_thread/thread/89a7292e5a9eee6d disabled} by
 * Twitter for several documented API calls.
 *
 * @package    Arc90_Service
 * @subpackage Twitter
 * @author     Matt Williams <matt@mattwilliamsnyc.com>
 * @copyright  Copyright (c) 2008 {@link http://arc90.com Arc90 Inc.}
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Arc90_Service_Twitter
{
    /**
     * Base URL for the Twitter API.
     */
    const API_URL             = 'http://twitter.com';

    // API URLs
    const PATH_STATUS_PUBLIC  = '/statuses/public_timeline';
    const PATH_STATUS_FRIENDS = '/statuses/friends_timeline';
    const PATH_STATUS_USER    = '/statuses/user_timeline';
    const PATH_STATUS_SHOW    = '/statuses/show';
    const PATH_STATUS_UPDATE  = '/statuses/update';
    const PATH_STATUS_REPLIES = '/statuses/replies';
    const PATH_STATUS_DESTROY = '/statuses/destroy';

    const PATH_USER_FRIENDS   = '/statuses/friends';
    const PATH_USER_FOLLOWERS = '/statuses/followers';
    const PATH_USER_FEATURED  = '/statuses/featured';
    const PATH_USER_SHOW      = '/users/show';

    const PATH_DM_MESSAGES    = '/direct_messages';
    const PATH_DM_SENT        = '/direct_messages/sent';
    const PATH_DM_NEW         = '/direct_messages/new';
    const PATH_DM_DESTROY     = '/direct_messages/destroy';

    const PATH_FRIEND_CREATE  = '/friendships/create';
    const PATH_FRIEND_DESTROY = '/friendships/destroy';
    const PATH_FRIEND_EXISTS  = '/friendships/exists';

    const PATH_ACCT_VERIFY    = '/account/verify_credentials';
    const PATH_ACCT_END_SESS  = '/account/end_session';
    const PATH_ACCT_ARCHIVE   = '/account/archive';
    const PATH_ACCT_LOCATION  = '/account/update_location';
    const PATH_ACCT_DEVICE    = '/account/update_delivery_device';

    const PATH_FAV_FAVORITES  = '/favorites';
    const PATH_FAV_CREATE     = '/favorites/create';
    const PATH_FAV_DESTROY    = '/favorites/destroy';

    const PATH_NOTIF_FOLLOW   = '/notifications/follow';
    const PATH_NOTIF_LEAVE    = '/notifications/leave';

    const PATH_BLOCK_CREATE   = '/blocks/create';
    const PATH_BLOCK_DESTROY  = '/blocks/destroy';

    const PATH_HELP_TEST      = '/help/test';
    const PATH_HELP_DOWNTIME  = '/help/downtime_schedule';
    // END API URLs

    /**
     * Maximum 'count' parameter for timeline requests
     */
    const MAX_COUNT           = 20;

    /**
     * Maximum length (in number of characters) of status updates
     */
    const STATUS_MAXLENGTH    = 140;

    /**
     * Twitter account username.
     * @var string
     */
    protected $_authUsername ='';

    /**
     * Twitter account password.
     * @var string
     */
    protected $_authPassword ='';

    /**
     * Unix time of the most recent API call.
     * @var integer
     */
    protected $_lastRequestTime =0;

    /**
     * Response to the most recent API call.
     * @var string
     */
    protected $_lastResponse =null;

    /**
     * Constructs a new Twitter Web Service Client.
     *
     * @param  string $username Twitter account username
     * @param  string $password Twitter account password
     */
    public function __construct($username =null, $password =null)
    {
        $this->setAuth($username, $password);
    }

    /**
     * Set client username and password.
     *
     * @param  string $username Twitter account username
     * @param  string $password Twitter account password
     * @return Arc90_Service_Twitter Provides a fluent interface.
     */
    public function setAuth($username, $password)
    {
        $this->_authUsername = $username;
        $this->_authPassword = $password;

        return $this;
    }

    /**
     * Returns the 20 most recent statuses from non-protected users who have set a custom user icon.
     * Does not require authentication.
     *
     * @param  string  $format   Response format (JSON,XML,RSS,ATOM); Defaults to JSON
     * @param  integer $since_id Returns only statuses more recent than the specified ID
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function getPublicTimeline($format ='json', $since_id =0)
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml', 'rss', 'atom');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_STATUS_PUBLIC . ".{$requestedFormat}";

        if(0 !== $since_id)
        {
            $this->_validateNonNegativeInteger('since_id', $since_id);
            $url .= "?since_id={$since_id}";
        }

        return $this->_makeRequest($url, $requestedFormat);
    }

    /**
     * Returns the 20 most recent statuses posted from the authenticating user and that user's friends.
     *
     * @param  string  $format Response format (JSON,XML,RSS,ATOM); Defaults to JSON
     * @param  string  $since  Narrows returned results to those statuses created after the specified date
     * @param  integer $page   Gets the specified page of results (20 results per page)
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function getFriendsTimeline($format ='json', $since ='', $page =0)
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml', 'rss', 'atom');
        $this->_validateOption($requestedFormat, $validFormats);

        $url  = self::PATH_STATUS_FRIENDS . ".{$requestedFormat}";
        $args = array();

        if('' !== $since)
        {
            $this->_validateDateString('since', $since);
            $args []= 'since=' . urlencode(date('D j M Y G:i:s T', strtotime($since)));
        }

        if(0 !== $page)
        {
            $this->_validateNonNegativeInteger('page', $page);
            $args []= "page={$page}";
        }

        if(count($args))
        {
            $url .= '?' . implode('&', $args);
        }

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Returns the 20 most recent statuses posted from the authenticating user.
     *
     * @param  string  $format Response format (JSON,XML,RSS,ATOM); Defaults to JSON
     * @param  string  $since  Narrows returned results to those statuses created after the specified date
     * @param  integer $count  Specifies the number of statuses to retrieve (Must be <= 20)
     * @param  integer $page   Gets the specified page of results (20 results per page)
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function getUserTimeline($format ='json', $since ='', $count =0, $page =0)
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml', 'rss', 'atom');
        $this->_validateOption($requestedFormat, $validFormats);

        $url  = self::PATH_STATUS_USER . ".{$requestedFormat}";
        $args = array();

        if('' !== $since)
        {
            $this->_validateDateString('since', $since);
            $args []= 'since=' . urlencode(date('D j M Y G:i:s T', strtotime($since)));
        }

        if(0 !== $count)
        {
            $this->_validateNonNegativeInteger('count', $count);

            $max = self::MAX_COUNT;
            if($max < $count)
            {
                throw new Arc90_Service_Twitter_Exception(
                    "Invalid parameter (count): '{$count}'. Must be <= {$max}."
                );
            }

            $args []= "count={$count}";
        }

        if(0 !== $page)
        {
            $this->_validateNonNegativeInteger('page', $page);
            $args []= "page={$page}";
        }

        if(count($args))
        {
            $url .= '?' . implode('&', $args);
        }

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Returns a single status, specified by the id parameter below.
     * The status's author will be returned inline.
     *
     * @param  integer $id     Numerical ID of the status to be retrieved
     * @param  string  $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function showStatus($id, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $this->_validateNonNegativeInteger('id', $id);

        $url = self::PATH_STATUS_SHOW . "/{$id}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Updates the authenticating user's status. Requires the status parameter specified below.
     *
     * @param  string $status The text of your status update. Must not exceed 140 characters
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function updateStatus($status, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $max = self::STATUS_MAXLENGTH;
        if($max < strlen($status))
        {
            throw new Arc90_Service_Twitter_Exception(
                "Status updates may not exceed {$max} characters!"
            );
        }

        $url =  self::PATH_STATUS_UPDATE . ".{$requestedFormat}";
        $url .= '?status=' . urlencode(stripslashes(urldecode($status)));

        return $this->_makeRequest($url, $requestedFormat, true, true);
    }

    /**
     * Returns the 20 most recent replies (updates prefixed with @username posted by friends of authenticating user).
     *
     * @param  string  $format Response format (JSON,XML,RSS,ATOM); Defaults to JSON
     * @param  integer $page   Gets the specified page of results (20 results per page)
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function getReplies($format ='json', $page =0)
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml', 'rss', 'atom');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_STATUS_REPLIES . ".{$requestedFormat}";

        if(0 !== $page)
        {
            $this->_validateNonNegativeInteger('page', $page);
            $url .= "?page={$page}";
        }

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Destroys the status specified by the required ID parameter.
     * The authenticating user must be the author of the specified status.
     *
     * @param  integer $id     The ID of the status to destroy
     * @param  string  $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function destroyStatus($id, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $this->_validateNonNegativeInteger('id', $id);

        $url = self::PATH_STATUS_DESTROY . "/{$id}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Returns up to 100 of the authenticating user's friends who have recently updated, with current status inline.
     *
     * @param  string  $format Response format (JSON,XML); Defaults to JSON
     * @param  integer $page   Gets the specified page of results (100 results per page)
     * @param  boolean $lite   Prevents the inline inclusion of current status
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function getFriends($format ='json', $page =0, $lite =false)
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url  = self::PATH_USER_FRIENDS . ".{$requestedFormat}";
        $args = array();

        if(0 !== $page)
        {
            $this->_validateNonNegativeInteger('page', $page);
            $args []= "page={$page}";
        }

        if($lite)
        {
            $args []= 'lite=true';
        }

        if(count($args))
        {
            $url .= '?' . implode('&', $args);
        }

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Returns up to 100 of the authenticating user's followers, each with current status inline.
     *
     * @param  string  $format Response format (JSON,XML); Defaults to JSON
     * @param  integer $page   Gets the specified page of results (100 results per page)
     * @param  integer $lite   Prevents the inline inclusion of current status.
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function getFollowers($format ='json', $page =0, $lite =false)
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url  = self::PATH_USER_FOLLOWERS . ".{$requestedFormat}";

        $args = array();

        if(0 !== $page)
        {
            $this->_validateNonNegativeInteger('page', $page);
            $args []= "page={$page}";
        }

        if($lite)
        {
            $args []= 'lite=true';
        }

        if(count($args))
        {
            $url .= '?' . implode('&', $args);
        }

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Returns a list of the users currently featured on Twitter with their current statuses inline.
     * Does not require authentication.
     *
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function getFeatured($format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_USER_FEATURED . ".{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat);
    }

    /**
     * Returns extended information of a given user, specified by ID or screen name via the required user parameter.
     * This includes design settings, so third party developers can theme widgets according to a user's preferences.
     *
     * @param  mixed  $user   The ID or screen name of a user.
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function showUser($user, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $user = urlencode($user);
        $url  = self::PATH_USER_SHOW . "/{$user}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Returns a list of the 20 most recent direct messages sent to the authenticating user.
     * The XML and JSON versions include detailed information about the sending and recipient users.
     *
     * @param  string  $format   Response format (JSON,XML,RSS,ATOM); Defaults to JSON
     * @param  string  $since    Narrows returned results to those statuses created after the specified date
     * @param  integer $since_id Returns only messages more recent than the specified ID
     * @param  integer $page     Gets the specified page of results (20 results per page)
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function getMessages($format ='json', $since ='', $since_id =0, $page =0)
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml', 'rss', 'atom');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_DM_MESSAGES . ".{$requestedFormat}";

        if('' !== $since)
        {
            $this->_validateDateString('since', $since);
            $args []= 'since=' . urlencode(date('D j M Y G:i:s T', strtotime($since)));
        }

        if(0 !== $since_id)
        {
            $this->_validateNonNegativeInteger('since_id', $since_id);
            $args []= "since_id={$since_id}";
        }

        if(0 !== $page)
        {
            $this->_validateNonNegativeInteger('page', $page);
            $args []= "page={$page}";
        }

        if(count($args))
        {
            $url .= '?' . implode('&', $args);
        }

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Returns a list of the 20 most recent direct messages sent by the authenticating user.
     * The XML and JSON versions include detailed information about the sending and recipient users.
     *
     * @param  string  $format   Response format (JSON,XML); Defaults to JSON
     * @param  string  $since    Narrows returned results to those statuses created after the specified date
     * @param  integer $since_id Returns only messages more recent than the specified ID
     * @param  integer $page     Gets the specified page of results (20 results per page)
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function getSentMessages($format ='json', $since ='', $since_id =0, $page =0)
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url  = self::PATH_DM_SENT . ".{$requestedFormat}";
        $args = array();

        if('' !== $since)
        {
            $this->_validateDateString('since', $since);
            $args []= 'since=' . urlencode(date('D j M Y G:i:s T', strtotime($since)));
        }

        if(0 !== $since_id)
        {
            $this->_validateNonNegativeInteger('since_id', $since_id);
            $args []= "since_id={$since_id}";
        }

        if(0 !== $page)
        {
            $this->_validateNonNegativeInteger('page', $page);
            $args []= "page={$page}";
        }

        if(count($args))
        {
            $url .= '?' . implode('&', $args);
        }

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Sends a new direct message to the specified user from the authenticating user.
     * Returns the sent message in the requested format when successful.
     *
     * @param  mixed  $user   The ID or screen name of the recipient user.
     * @param  string $text   The text of your direct message. Must not exceed 140 characters
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function sendMessage($user, $text, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_DM_NEW . ".{$requestedFormat}";

        $max = self::STATUS_MAXLENGTH;
        if($max < strlen($text))
        {
            throw new Arc90_Service_Twitter_Exception(
                "Message length may not exceed {$max} characters!"
            );
        }

        $user = urlencode($user);
        $text = urlencode(stripslashes(urldecode($text)));
        $data = "user={$user}&text={$text}";

        return $this->_makeRequest($url, $requestedFormat, true, true, $data);
    }

    /**
     * Destroys the direct message specified by the required ID parameter.
     * The authenticating user must be the recipient of the specified direct message.
     *
     * @param  integer $id     The ID of the direct message to destroy
     * @param  string  $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function destroyMessage($id, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $this->_validateNonNegativeInteger('id', $id);

        $url = self::PATH_DM_DESTROY . "/{$id}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Befriends the user specified in the ID parameter as the authenticating user.
     * Returns the befriended user in the requested format when successful.
     * Returns a string describing the failure condition when unsuccessful.
     *
     * @param  mixed  $user   The ID or screen name of the user to befriend
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function createFriendship($user, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $user = urlencode($user);
        $url  = self::PATH_FRIEND_CREATE . "/{$user}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Discontinues friendship with the user specified in the ID parameter as the authenticating user.
     * Returns the un-friended user in the requested format when successful.
     * Returns a string describing the failure condition when unsuccessful.
     *
     * @param  mixed  $user   The ID or screen name of the user with whom to discontinue friendship
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function destroyFriendship($user, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $user = urlencode($user);
        $url  = self::PATH_FRIEND_DESTROY . "/{$user}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Tests if friendship exists between the two users specified in the parameters below.
     *
     * @param  mixed $userA The ID or screen name of the first user to test friendship for.
     * @param  mixed $userB The ID or screen name of the second user to test friendship for.
     * @param  string $format Response format (JSON,XML,none); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function friendshipExists($userA, $userB, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml', 'none');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_FRIEND_EXISTS;
        if('none' !== $requestedFormat)
        {
            $url .= ".{$requestedFormat}";
        }

        $userA = urlencode($userA);
        $userB = urlencode($userB);

        $url .= "?user_a={$userA}&user_b={$userB}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Returns an HTTP 200 OK response code and a format-specific response if authentication was successful.
     * Use this method to test if supplied user credentials are valid with minimal overhead.
     *
     * @param  string $format Response format (JSON,XML,none); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function verifyCredentials($format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml','none');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_ACCT_VERIFY;
        if('none' !== $requestedFormat)
        {
            $url .= ".{$requestedFormat}";
        }

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Ends the session of the authenticating user, returning a null cookie.
     * Use this method to sign users out of client-facing applications like widgets.
     *
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function endSession()
    {
        $url = self::PATH_ACCT_END_SESS;

        return $this->_makeRequest($url, 'none', true);
    }

    /**
     * Returns 80 statuses per page for the authenticating user, ordered by descending date of posting.
     * Use this method to rapidly export your archive of statuses.
     *
     * @param  string  $format   Response format (JSON,XML); Defaults to JSON
     * @param  string  $since    Narrows returned results to those statuses created after the specified date
     * @param  integer $since_id Returns only messages more recent than the specified ID
     * @param  integer $page     Gets the specified page of results (80 results per page)
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function getArchive($format ='json', $since =0, $since_id =0, $page =0)
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url  = self::PATH_ACCT_ARCHIVE . ".{$requestedFormat}";

        $args = array();

        if('' !== $since)
        {
            $this->_validateDateString('since', $since);
            $args []= 'since=' . urlencode(date('D j M Y G:i:s T', strtotime($since)));
        }

        if(0 !== $since_id)
        {
            $this->_validateNonNegativeInteger('since_id', $since_id);
            $args []= "since_id={$since_id}";
        }

        if(0 !== $page)
        {
            $this->_validateNonNegativeInteger('page', $page);
            $args []= "page={$page}";
        }

        if(count($args))
        {
            $url .= '?' . implode('&', $args);
        }

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Updates the location of the authenticating user, as displayed in their profile and returned in API calls.
     *
     * @param string $location The location of the user. Please note that this is not normalized or translated.
     * @param string $format   Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function updateLocation($location, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $location = urlencode($location);
        $data     = "location={$location}";

        return $this->_makeRequest($url, $requestedFormat, true, true, $data);
    }

    /**
     * Sets which device Twitter delivers updates to for the authenticating user.
     * Sending none as the device parameter will disable IM or SMS updates.
     *
     * @param string $device Must be one of: sms, im, none.
     * @param string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function updateDeliveryDevice($device, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $requestedDevice = strtolower($device);
        $validDevices    = array('sms', 'im', 'none');
        $this->_validateOption($requestedDevice, $validDevices);

        $device = urlencode($device);
        $data   = "device={$device}";

        return $this->_makeRequest($url, $requestedFormat, true, true, $data);
    }

    /**
     * Returns 20 most recent favorite statuses for the authenticating user.
     *
     * @param  string  $format Response format (JSON,XML,RSS,ATOM); Defaults to JSON
     * @param  integer $page   Gets the specified page of results (20 results per page)
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function getFavorites($format ='json', $page =0)
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml', 'rss', 'atom');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_FAV_FAVORITES . ".{$requestedFormat}";

        if(0 !== $page)
        {
            $this->_validateNonNegativeInteger('page', $page);
            $url .= "?page={$page}";
        }

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Favorites the status specified in the ID parameter as the authenticating user.
     * Returns the favorite status when successful.
     *
     * @param  integer $id     The ID of the status to favorite.
     * @param  string  $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function createFavorite($id, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $this->_validateNonNegativeInteger('id', $id);

        $url = self::PATH_FAV_CREATE . "/{$id}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Un-favorites the status specified in the ID parameter as the authenticating user.
     * Returns the un-favorited status in the requested format when successful.
     *
     * @param  integer $id     The ID of the status to un-favorite.
     * @param  string  $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function destroyFavorite($id, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $this->_validateNonNegativeInteger('id', $id);

        $url = self::PATH_FAV_DESTROY . "/{$id}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Enables notifications for updates from the specified user to the authenticating user.
     * Returns the specified user when successful.
     *
     * @param  mixed  $user   The ID or screenname of the user to follow.
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function follow($user, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_NOTIF_FOLLOW . "/{$user}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Disables notifications for updates from the specified user to the authenticating user.
     * Returns the specified user when successful.
     *
     * @param  mixed  $user   The ID or screenname of the user to stop following.
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function leave($user, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_NOTIF_LEAVE . "/{$user}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Blocks the user specified in the ID parameter as the authenticating user.
     * Returns the blocked user in the requested format when successful.
     *
     * @param  mixed  $user   The ID or screen_name of the user to block
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     * @link   http://help.twitter.com/index.php?pg=kb.page&id=69 
     */
    public function block($user, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_BLOCK_CREATE . "/{$user}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Un-blocks the user specified in the ID parameter as the authenticating user.
     * Returns the un-blocked user in the requested format when successful.
     *
     * @param  mixed  $user   The ID or screen_name of the user to unblock
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     * @link   http://help.twitter.com/index.php?pg=kb.page&id=69 
     */
    public function unblock($user, $format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_BLOCK_DESTROY . "/{$user}.{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Returns the string "ok" in the requested format with a 200 OK HTTP status code.
     *
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function test($format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_HELP_TEST . ".{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Returns the same text displayed on {@link http://twitter.com/home} when a maintenance window is scheduled.
     *
     * @param  string $format Response format (JSON,XML); Defaults to JSON
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    public function downtimeSchedule($format ='json')
    {
        $requestedFormat = strtolower($format);
        $validFormats    = array('json', 'xml');
        $this->_validateOption($requestedFormat, $validFormats);

        $url = self::PATH_HELP_DOWNTIME . ".{$requestedFormat}";

        return $this->_makeRequest($url, $requestedFormat, true);
    }

    /**
     * Returns the UNIX time of the most recent request made by this client.
     *
     * @return integer
     */
    public function getLastRequestTime()
    {
        return $this->_lastRequestTime;
    }

    /**
     * Returns the response to the most recent successful API call.
     *
     * @return Arc90_Service_Twitter_Response
     */
    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    /**
     * Validates that a parameter is a valid date string (recognized by strtotime()).
     *
     * @param  string  $name  Name of the parameter to be validated (for use in error messages)
     * @param  mixed   $value Value of the parameter to be validated
     * @return boolean Validation of option
     * @throws Arc90_Service_Twitter_Exception
     */
    protected function _validateDateString($name, $value)
    {
        if(0 >= strtotime($value)) // PHP 5.1+ returns false on failure. Prior to PHP 5.1, -1 was returned.
        {
            throw new Arc90_Service_Twitter_Exception(
                "Invalid parameter ({$name}): '{$value}'; must be a valid date string."
            );
        }

        return true;
    }

    /**
     * Validates that a parameter is an integer greater than or equal to zero.
     *
     * @param string $name  Name of the parameter to be validated (for use in error messages).
     * @param mixed  $value Value of the parameter to be validated.
     * @return bool  Validation of option
     * @throws Arc90_Service_Twitter_Exception
     */
    protected function _validateNonNegativeInteger($name, $value)
    {
        if(!is_numeric($value) || 0 > $value)
        {
            throw new Arc90_Service_Twitter_Exception(
                "Invalid parameter ({$name}): '{$value}'; must be a positive integer (or zero)."
            );
        }

        return true;
    }

    /**
     * Validates an option against a set of allowed options.
     *
     * @param  mixed $option  Option to validate
     * @param  array $options Array of allowed option values
     * @return bool  Validation of option
     * @throws Arc90_Service_Twitter_Exception
     */
    protected function _validateOption($option, array $options)
    {
        if(!in_array($option, $options))
        {
            throw new Arc90_Service_Twitter_Exception(
                "Invalid option: '{$option}'. Valid options include: " . implode(', ', $options)
            );
        }

        return true;
    }

    /**
     * Sends an HTTP GET or POST request to the Twitter API with optional Basic authentication.
     *
     * @param  string $url    Target URL for this request (relative to the API root URL)
     * @param  string $format Response format (JSON,XML, RSS, ATOM, none); Defaults to JSON
     * @param  bool   $auth   Specifies whether authentication is required
     * @param  bool   $post   Specifies whether HTTP POST should be used (versus GET)
     * @param  array  $data   x-www-form-urlencoded data to be sent in a POST request body
     * @return Arc90_Service_Twitter_Response
     * @throws Arc90_Service_Twitter_Exception
     */
    protected function _makeRequest($url, $format ='json', $auth =false, $post =false, $data ='')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::API_URL . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($auth)
        {
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->_authUsername}:{$this->_authPassword}");
        }

        if($post)
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $this->_lastRequestTime = time();

        $data     = curl_exec($ch);
        $metadata = curl_getinfo($ch);

        curl_close($ch);

        // Create, store and return a response object
        return $this->_lastResponse = new Arc90_Service_Twitter_Response($data, $metadata, $format);
    }
}
