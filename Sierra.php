<?php
/**
 * @file Sierra.php
 *
 * Sierra API class to make calls to the Sierra REST API
 *
 *
 * Example Usage:
 * 
 * The example gets information on bib ID 3996024 and limits the results to 20 records only
 * including the fields id, location, and status.
 *
 * include('Sierra.php');
 *
 * $s = new Sierra(array(
 *    'endpoint' => 'Sierra REST API Endpoint (ie https://lib.example.edu/iii/sierra-api/v1/)',
 *    'key' => 'Sierra Client Key',
 *    'secret' => 'Sierra Client Secret'
 *    'tokenFile' => 'Location to the temp file to keep token infomation, default: /tmp/SierraToken'
 * ));
 *
 * $bibInformation = $s->query('items', array(
 *    'bibIds' => '3996024',
 *    'limit' => '20',
 *    'fields' => 'id,location,status'
 * ));
 *
 *
 *
 * @author Sean Watkins <slwatkins@uh.edu>
 * @copyright 2014 Sean Watkins
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Sierra {

/**
 * The Authorization Token array returned when accessing a token request.
 *
 * @var array
 */
    public $token = array();

/**
 * The Sierra configuration
 *
 * @var array
 */
    public $config = array(
        'tokenFile' => '/tmp/SierraToken'
    );
   
/**
 * Constructor
 *
 * @param array $config Array of configuration information for Sierra
 */
    public function __construct($config) {
        $this->config = array_merge($this->config, $config);
    }

/**
 * Makes the resource request
 *
 * @param string $resource The resource being requested
 * @param array $params Array of paramaters
 * @param boolean $marc True to have the response include MARC data
 * @return array Array of data
 */  
    public function query($resource, $params = array(), $marc = false) {
        if (!$this->_checkToken()) return null;
        
        $headers = array('Authorization: ' . $this->token['token_type'] . ' ' . $this->token['access_token']);
        if ($marc) {
            $headers[] = 'Accept: application/marc-in-json';
        }

        $response = $this->_request($this->config['endpoint'] . $resource, $params, $headers);
        if ($response['status'] != 200) return null;
        
        return json_decode($response['body'], true);
    }

/**
 * Checks if Authentication Token exists or has expired. A new Authentication Token will 
 * be created if one does not exist.
 *
 * @return boolean True if token is valid
 */    
    private function _checkToken() {
        if (file_exists($this->config['tokenFile'])) {
            $this->token = json_decode(file_get_contents($this->config['tokenFile']), true);
        }
        
        if (!$this->token || (time() >= $this->token['expires_at'])) {
            return $this->_accessToken();
        }
        return true;
    }

/**
 * Requests a Authentication Token from Sierra
 *
 * @return boolean True if a token is created
 */
    private function _accessToken() {
        $auth = base64_encode($this->config['key'] . ':' . $this->config['secret']);
    
        $response = $this->_request($this->config['endpoint'] . 'token', array('grant_type', 'client_credentials'), array('Authorization: Basic ' . $auth), 'post');
        $token = json_decode($response['body'], true);
        if (!$token) return false;

        if (!isset($token['error'])) {
            $token['expires_at'] = time() + $token['expires_in'];
        
            $this->token = $token;
            file_put_contents($this->config['tokenFile'], json_encode($token));
            return true;
        }
        
        return false;
    }

/**
 * Requests data from Sierra
 *
 * @param string $url The full URL to the REST API call
 * @param array $params The query paramaters to pass to the call
 * @param array $header Additional header information to include
 * @param string $type The request type 'GET' or 'POST'
 * @return array Result array
 * 
 * ### Result keys returned
 * - 'status': The return status from the server
 * - 'header': The header information fo the server
 * - 'body': The body of the message
 */
    private function _request($url, $params = array(), $header = array(), $type = 'get') {
        $type = strtolower($type);

        $s = curl_init();
        
        if ($type == 'post') {
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($s, CURLOPT_POST, true);
            curl_setopt($s, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        else {
            $url .= ($params ? '?' . http_build_query($params) : '');
        }
        
        curl_setopt($s, CURLOPT_URL, $url);
        curl_setopt($s, CURLOPT_TIMEOUT, 60);
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($s, CURLOPT_USERAGENT, 'Sierra PHP Test/0.1');
        curl_setopt($s, CURLOPT_HEADER, true);
    
        if ($header) {
            curl_setopt($s, CURLOPT_HTTPHEADER, $header);
        }

        $result = curl_exec($s);
        $status = curl_getinfo($s, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($s, CURLINFO_HEADER_SIZE);
        $header = $this->_parseResponseHeaders(substr($result, 0, $headerSize));
        $body = substr($result, $headerSize);
    
        $response = array(
            'status' => $status,
            'header' => $header,
            'body' => $body
        );	
        curl_close($s);
    
        return $response;
    }

/**
 * Parse response headers into a array
 *
 * @param string $header The header information as a string
 * @return array
 */
    private function _parseResponseHeaders($header) {
        $headers = array();
        $h = explode("\r\n", $header);
        foreach ($h as $header) {
            if (strpos($header, ':') !== false) {
                list($type, $value) = explode(":", $header, 2);
                if (isset($headers[$type])) {
                    if (is_array($headers[$type])) {
                        $headers[$type][] = trim($value);
                    }
                    else {
                        $headers[$type] = array($headers[$type], trim($value));
                    }
                }
                else {
                    $headers[$type] = trim($value);
                }
            }
        }
    
        return $headers;
    }
}