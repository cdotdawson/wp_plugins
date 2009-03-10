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
require_once('Exception.php');

/**
 * Arc90_Service_Twitter_Response represents a response to a {@link http://twitter.com Twitter} API call.
 *
 * @package    Arc90_Service
 * @subpackage Twitter
 * @author     Matt Williams <matt@mattwilliamsnyc.com>
 * @copyright  Copyright (c) 2008 {@link http://arc90.com Arc90 Inc.}
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Arc90_Service_Twitter_Response
{
    /**
     * Metadata related to the HTTP response collected by cURL
     * @var array
     */
    protected $_metadata = array();

    /**
     * Response body (if any) returned by Twitter
     * @var string
     */
    protected $_data     = '';

    /**
     * Data type of the response body
     * @var string
     */
    protected $_format   = '';

    /**
     * Creates a new Arc90_Service_Twitter_Response instance.
     *
     * @param array  $metadata HTTP response {@link http://us3.php.net/curl_getinfo curl_getinfo() metadata}
     * @param string $data     Response body (if any) returned by Twitter
     * @param string $format   Data type of the response body (JSON, XML, RSS, ATOM, none)
     */
    public function __construct($data, array $metadata, $format)
    {
        $this->_data     = $data;
        $this->_metadata = $metadata;
        $this->_format   = $format;
    }

    /**
     * Overloads retrieval of object properties to allow read-only access.
     *
     * @param  string $name Name of the property to be accessed
     * @return mixed
     */
    public function __get($name)
    {
        if('data' == $name)
        {
            return $this->_data;
        }

        if(isset($this->_metadata[$name]))
        {
            return $this->_metadata[$name];
        }

        throw new Arc90_Service_Twitter_Exception(
            "Response property '{$name}' does not exist!"
        );
    }

    /**
     * Overloads checking for existence of object properties to allow read-only access.
     *
     * @param  string  $name Name of the property to be accessed
     * @return boolean
     */
    public function __isset($name)
    {
        if('data' == $name)
        {
            return isset($this->_data);
        }

        return isset($this->_metadata[$name]);
    }

    /**
     * Returns the content body (if any) returned by Twitter.
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Checks the HTTP status code of the response for 4xx or 5xx class errors.
     *
     * @return boolean
     */
    public function isError()
    {
        $type = floor($this->_metadata['http_code'] / 100);

        return 4 == $type || 5 == $type;
    }

    /**
     * Does this response contain JSON data?
     *
     * @return boolean
     */
    public function isJson()
    {
        return 'json' == $this->_format;
    }

    /**
     * Does this response contain XML data?
     *
     * @return bool
     */
    public function isXml()
    {
        return 'xml' == $this->_format;
    }

    /**
     * Returns response data (and metadata) as an associative array.
     *
     * @return array
     */
    public function toArray()
    {
        $array         = $this->_metadata;
        $array['data'] = $this->_data;

        return $array;
    }
}
