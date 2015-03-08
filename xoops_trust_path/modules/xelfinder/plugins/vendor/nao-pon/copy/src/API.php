<?php

namespace Barracuda\Copy;

/**
 * Copy API class
 *
 * @package Copy
 * @license https://raw.github.com/copy-app/php-client-library/master/LICENSE MIT
 */
class API
{
    /**
     * API URl
     * @var string $api_url
     */
    protected $api_url = 'https://api.copy.com';

    /**
     * Instance of curl
     * @var resource $curl
     */
    private $curl;

    /**
     * @var array
     * User data
     */
    private $signature;

    /**
     * Constructor
     *
     * @param string $consumerKey    OAuth consumer key
     * @param string $consumerSecret OAuth consumer secret
     * @param string $accessToken    OAuth access token
     * @param string $tokenSecret    OAuth token secret
     */
    public function __construct($consumerKey, $consumerSecret, $accessToken, $tokenSecret)
    {
        // oauth setup
        $this->signature = array(
            'consumer_key' => $consumerKey,
            'shared_secret' => $consumerSecret,
            'oauth_token' => $accessToken,
            'oauth_secret' => $tokenSecret
        );

        $this->__wakeup();
    }

    /**
     * Wakeup function on unserialize
     * 
     */
    public function __wakeup()
    {
        // curl setup
        $this->curl = curl_init();
        if (!$this->curl) {
            throw new \Exception("Failed to initialize curl");
        }

        // ca bundle
        $cacrt = __DIR__ . '/ca.crt';
        if (!is_file($cacrt)) {
            throw new \Exception("Failed to load ca certificate");
        }
        // check for phar execution
        // in case we have to move the .crt file to a temp folder so curl is able to load it
        if (substr(__FILE__, 0, 7) == 'phar://') {
            $cacrt = self::extractPharCacert($cacrt);
        }
        curl_setopt($this->curl, CURLOPT_CAINFO, $cacrt);
    }

    /**
     * Upload a file from a string
     *
     * @param string $path full path containing leading slash and file name
     * @param string $data binary data
     *
     * @return object described in createFile()
     */
    public function uploadFromString($path, $data)
    {
        // create the temporary stream
        $stream = fopen('php://temp', 'w+');

        // write the data
        fwrite($stream, $data);

        // rewind the pointer
        rewind($stream);

        // upload as a stream
        return $this->uploadFromStream($path, $stream);
    }    

    /**
     * Upload a file from a stream resource
     *
     * @param string $path full path containing leading slash and file name
     * @param resource $stream resource to read data from
     *
     * @return object described in createFile()
     */
    public function uploadFromStream($path, $stream)
    {
        // send data 1MB at a time
        $parts = array();
        $limit = 1048576;
        $buffer = '';
        while ($buffer .= fread($stream, $limit)) {
            // check $buffer size for remote stream
            // ref. http://php.net/manual/function.fread.php
            // see Example #3 Remote fread() examples
            if (!feof($stream) && strlen($buffer) < $limit) {
                continue;
            }
            $next = '';
            if (strlen($buffer) > $limit) {
                $next = substr($buffer, $limit);
                $buffer = substr($buffer, 0, $limit);
            }
            $parts[] = $this->sendData($buffer);
            $buffer = $next;
        }

        // close the stream
        fclose($stream);

        // update the file in the cloud
        return $this->createFile('/' . $path, $parts);
    }

    /**
     * Read a file to a string
     *
     * @param string $path full path containing leading slash and file name
     *
     * @return array contains key of contents which contains binary data of the file
     */
    public function readToString($path)
    {
        $object = $this->readToStream($path);
        $object['contents'] = stream_get_contents($object['stream']);
        fclose($object['stream']);
        unset($object['stream']);

        return $object;
    }

    /**
     * Read a file to a stream
     *
     * @param string $path full path containing leading slash and file name
     *
     * @return array contains key of stream which contains a stream resource
     */
    public function readToStream($path)
    {
        // create the temporary stream
        $stream = fopen('php://temp', 'w+');

        // obtain the list of parts for the file (should be an array of one)
        $files = $this->listPath('/' . $path, array('include_parts' => true));

        if (is_array($files) === false || sizeof($files) !== 1) {
            throw new \Exception("Could not find file at path: '" . $path . "'");
        }

        // found it, verify its a file
        $file = array_pop($files);
        if ($file->{"type"} != "file") {
            throw new \Exception("Could not find file at path: '" . $path . "'");
        }

        // obtain each part and add it to the stream
        foreach ($file->{"revisions"}[0]->{"parts"} as $part) {
            $data = $this->getPart($part->{"fingerprint"}, $part->{"size"});
            fwrite($stream, $data);
        }

        // rewind the pointer
        rewind($stream);

        return compact('stream');
    }

    /**
     * Send a request to remove a given file.
     *
     * @param string $path full path containing leading slash and file name
     *
     * @return bool true if the file was removed successfully
     */
    public function removeFile($path)
    {
        return $this->removeItem($path, 'file');
    }

    /**
     * Send a request to remove a given dir.
     *
     * @param string $path full path containing leading slash and dir name
     *
     * @return bool true if the dir was removed successfully
     */
    public function removeDir($path)
    {
        return $this->removeItem($path, 'dir');
    }

    /**
     * Send a request to remove a given item.
     *
     * @param string $path full path containing leading slash and file name
     * @param string $type file or dir
     *
     * @return bool true if the item was removed successfully
     */
    private function removeItem($path, $type)
    {
        $request = array();
        $request["object_type"] = $type;

        $this->updateObject('remove', $path, $request);

        return true;
    }

    /**
     * Rename a file
     *
     * Object structure:
     * {
     *  object_id: "4008"
     *  path: "/example"
     *  type: "dir" || "file"
     *  share_id: "0"
     *  share_owner: "21956799"
     *  company_id: NULL
     *  size: filesize in bytes, 0 for folders
     *  created_time: unix timestamp, e.g. "1389731126"
     *  modified_time: unix timestamp, e.g. "1389731126"
     *  date_last_synced: unix timestamp, e.g. "1389731126"
     *  removed_time: unix timestamp, e.g. "1389731126" or empty string for non-deleted files/folders
     *  mime_type: string
     *  revisions: array of revision objects
     * }
     *
     * @param string $source_path full path containing leading slash and file name
     * @param string $destination_path full path containing leading slash and file name
     *
     * @return stdClass using structure as noted above
     */
    public function rename($source_path, $destination_path)
    {
        return $this->updateObject('rename', $source_path, array('new_path' => $destination_path));
    }

    /**
     * Copy an item
     *
     * Object structure:
     * {
     *  object_id: "4008"
     *  path: "/example"
     *  type: "dir" || "file"
     *  share_id: "0"
     *  share_owner: "21956799"
     *  company_id: NULL
     *  size: filesize in bytes, 0 for folders
     *  created_time: unix timestamp, e.g. "1389731126"
     *  modified_time: unix timestamp, e.g. "1389731126"
     *  date_last_synced: unix timestamp, e.g. "1389731126"
     *  removed_time: unix timestamp, e.g. "1389731126" or empty string for non-deleted files/folders
     *  mime_type: string
     *  revisions: array of revision objects
     * }
     *
     * @param string $source_path full path containing leading slash and file name
     * @param string $destination_path full path containing leading slash and file name
     *
     * @return stdClass using structure as noted above
     */
    public function copy($source_path, $destination_path)
    {
        return $this->updateObject('copy', $source_path, array('new_path' => $destination_path));
    }

    /**
     * List objects within a path
     *
     * Object structure:
     * {
     *  object_id: "4008"
     *  path: "/example"
     *  type: "dir" || "file"
     *  share_id: "0"
     *  share_owner: "21956799"
     *  company_id: NULL
     *  size: filesize in bytes, 0 for folders
     *  created_time: unix timestamp, e.g. "1389731126"
     *  modified_time: unix timestamp, e.g. "1389731126"
     *  date_last_synced: unix timestamp, e.g. "1389731126"
     *  removed_time: unix timestamp, e.g. "1389731126" or empty string for non-deleted files/folders
     *  mime_type: string
     *  revisions: array of revision objects
     * }
     *
     * @param  string $path              full path with leading slash and optionally a filename
     * @param  array  $additionalOptions used for passing options such as include_parts
     *
     * @return array List of file/folder objects described above.
     */
    public function listPath($path, $additionalOptions = null)
    {
        $list_watermark = false;
        $return = array();

        do {
            $request = array();
            $request["path"] = $path;
            $request["max_items"] = 100;
            $request["list_watermark"] = $list_watermark;

            if ($additionalOptions) {
                $request = array_merge($request, $additionalOptions);
            }

            $result = $this->post("list_objects", $this->encodeRequest("list_objects", $request), true);

            // add the children if we got some, otherwise add the root object itself to the return
            if (isset($result->result->children) && empty($result->result->children) === false) {
                $return = array_merge($return, $result->result->children);
                $list_watermark = $result->result->list_watermark;
            } else {
                $return[] = $result->result->object;
            }
        } while (isset($result->result->more_items) && $result->result->more_items == 1);

        return $return;
    }

    /**
     * Get directory or file meta data
     *
     * Object structure:
     * {
     *  id: "/copy/example"
     *  path: "/example"
     *  name: "example",
     *  type: "dir" || "file"
     *  share_id: "0"
     *  share_owner: "21956799"
     *  company_id: NULL
     *  size: filesize in bytes, 0 for folders
     *  created_time: unix timestamp, e.g. "1389731126"
     *  modified_time: unix timestamp, e.g. "1389731126"
     *  date_last_synced: unix timestamp, e.g. "1389731126"
     *  removed_time: unix timestamp, e.g. "1389731126" or empty string for non-deleted files/folders
     *  mime_type: string
     *  revisions: array of revision objects
     *  children: array of children objects
     * }
     *
     * @param  string $path  full path with leading slash and optionally a filename
     * @param  string $root  Optional, "copy" is the first level of the real filesystem
     *
     * @return array List of file/folder objects described above.
     */
    public function getMeta($path, $root = "copy")
    {
        $result = $this->get("meta/" . $root . $path);

        // Decode the json reply
        $result = json_decode($result);

        // Check for errors
        if (isset($result->error)) {
            if ($result->error == 1301) {
            	// item not found
            	return array();
            }
            throw new \Exception("Error listing path " . $path . ": (" . $result->error . ") '" . $result->message . "'");
        }

        return $result;
    }

    /**
     * Create a dir
     *
     * Object structure:
     * {
     *  object_id: "4008"
     *  path: "/example"
     *  type: "dir"
     *  share_id: "0"
     *  share_owner: "21956799"
     *  company_id: NULL
     *  size: filesize in bytes, 0 for folders
     *  created_time: unix timestamp, e.g. "1389731126"
     *  modified_time: unix timestamp, e.g. "1389731126"
     *  date_last_synced: unix timestamp, e.g. "1389731126"
     *  removed_time: unix timestamp, e.g. "1389731126" or empty string for non-deleted files/folders
     * }
     *
     * @param string $path      full path containing leading slash and dir name
     * @param bool   $recursive true to create parent directories
     *
     * @return object described above.
     */
    public function createDir($path, $recursive = true)
    {
        $request = array(
            'object_type' => 'dir',
            'recurse' => $recursive,
            );

        return $this->updateObject('create', $path, $request);
    }

    /**
     * Create a file with a set of data parts
     *
     * Object structure:
     * {
     *  object_id: "4008"
     *  path: "/example"
     *  type: "file"
     *  share_id: "0"
     *  share_owner: "21956799"
     *  company_id: NULL
     *  size: filesize in bytes, 0 for folders
     *  created_time: unix timestamp, e.g. "1389731126"
     *  modified_time: unix timestamp, e.g. "1389731126"
     *  date_last_synced: unix timestamp, e.g. "1389731126"
     *  removed_time: unix timestamp, e.g. "1389731126" or empty string for non-deleted files/folders
     *  mime_type: string
     *  revisions: array of revision objects
     * }
     *
     * @param string $path  full path containing leading slash and file name
     * @param array  $parts contains arrays of parts returned by \Barracuda\Copy\API\sendData
     *
     * @return object described above.
     */
    public function createFile($path, $parts)
    {
        $request = array();
        $request["object_type"] = "file";
        $request["parts"] = array();

        $offset = 0;
        foreach ($parts as $part) {
            $partRequest = array(
                'fingerprint' => $part["fingerprint"],
                'offset' => $offset,
                'size' => $part["size"],
                );

            array_push($request["parts"], $partRequest);

            $offset += $part["size"];
        }

        $request["size"] = $offset;

        return $this->updateObject('create', $path, $request);
    }

    /**
     * Generate the fingerprint for a string of data.
     *
     * @param string $data Data part to generate the fingerprint for.
     *
     * @return string Fingerprint for $data.
    **/
    public function fingerprint($data)
    {
        return md5($data) . sha1($data);
    }

    /**
     * Send a piece of data
     *
     * @param  string $data    binary data
     * @param  int    $shareId setting this to zero is best, unless share id is known
     *
     * @return array  contains fingerprint and size, to be used when creating a file
     */
    public function sendData($data, $shareId = 0)
    {
        // first generate a part hash
        $fingerprint = $this->fingerprint($data);
        $part_size = strlen($data);

        // see if the cloud has this part, and send if needed
        if(!$this->hasPart($fingerprint, $part_size, $shareId)) {
            $this->sendPart($fingerprint, $part_size, $data, $shareId);
        }

        // return information about this part
        return array("fingerprint" => $fingerprint, "size" => $part_size);
    }

    /**
     * Send a data part
     *
     * @param string $fingerprint md5 and sha1 concatenated
     * @param int    $size        number of bytes
     * @param string $data        binary data
     * @param int    $shareId     setting this to zero is best, unless share id is known
     *
     */
    public function sendPart($fingerprint, $size, $data, $shareId = 0)
    {
        // They must match
        if (md5($data) . sha1($data) != $fingerprint) {
            throw new \Exception("Failed to validate part hash");
        }

        $request = array(
            'parts' => array(
                array(
                    'share_id' => $shareId,
                    'fingerprint' => $fingerprint,
                    'size' => $size,
                    'data' => 'BinaryData-0-' . $size
                )
            )
        );

        $result = $this->post("send_object_parts_v2", $this->encodeRequest("send_object_parts_v2", $request) . chr(0) . $data, true);

        if ($result->result->has_failed_parts) {
            throw new \Exception("Error sending part: " . $result->result->failed_parts[0]->message);
        }
    }

    /**
     * Check to see if a part already exists
     *
     * @param  string $fingerprint md5 and sha1 concatenated
     * @param  int    $size        number of bytes
     * @param  int    $shareId     setting this to zero is best, unless share id is known
     * @return bool   true if part already exists
     */
    public function hasPart($fingerprint, $size, $shareId = 0)
    {
        $request = array(
            'parts' => array(
                array(
                    'share_id' => $shareId,
                    'fingerprint' => $fingerprint,
                    'size' => $size
                )
            )
        );

        $result = $this->post("has_object_parts_v2", $this->encodeRequest("has_object_parts_v2", $request), true);

        if (empty($result->result->needed_parts)) {
            return true;
        } else {
            $part = $result->result->needed_parts[0];
            if (!empty($part->message)) {
                throw new \Exception("Error checking for part: " . $part->message);
            } else {
                return false;
            }
        }
    }

    /**
     * Get a part
     *
     * @param  string $fingerprint md5 and sha1 concatinated
     * @param  int    $size        number of bytes
     * @param  int    $shareId     setting this to zero is best, unless share id is known
     *
     * @return string binary data
     */
    public function getPart($fingerprint, $size, $shareId = 0)
    {
        $request = array(
            'parts' => array(
                array(
                    'share_id' => $shareId,
                    'fingerprint' => $fingerprint,
                    'size' => $size
                )
            )
        );

        $result = $this->post("get_object_parts_v2", $this->encodeRequest("get_object_parts_v2", $request));

        // Find the null byte
        $null_offset = strpos($result, chr(0));

        // Grab the binary payload
        $binary = substr($result, $null_offset + 1, strlen($result) - $null_offset);

        if ($binary === false) {
            throw new \Exception("Error getting part data");
        }

        // Grab the json payload
        $json = isset($binary) ? substr($result, 0, $null_offset) : $result;

        if ($json === false) {
            throw new \Exception("Error getting part data");
        }

        // Decode the json reply
        $result = json_decode($json);

        // Check for errors
        if (isset($result->error)) {
            throw new \Exception("Error getting part data");
        }

        if (isset($result->result->parts[0]->message)) {
            throw new \Exception("Error getting part data: " . $result->result->parts[0]->message);
        }

        // Get the part data (since there is only one part the binary payload should just be the data)
        if (strlen($binary) != $size) {
            throw new \Exception("Error getting part data");
        }

        return $binary;
    }

    /**
     * Create a New Link
     * 
     * Object structure:
     * {
     *   id: "MBrss3roGDk4",
     *   name: "My Cool Shared Files",
     *   public: true,
     *   url: "https://copy.com/MBrss3roGDk4",
     *   url_short: "https://copy.com/MBrss3roGDk4",
     *   creator_id: "1381231",
     *   company_id: null,
     *   confirmation_required: false,
     *   status: "viewed",
     *   permissions: "read"
     * }
     * 
     * @param array|string  $paths   target item(s) path
     * @param array         $options option attributes, (bool) "public", (string) "name"
     * @param string        $root
     * 
     * @throws \Exception
     * 
     * @return object described above.
     */
    public function createLink($paths, $options = array(), $root = 'copy')
    {
        if (is_string($paths)) {
            $paths = array($paths);
        }
        $paths = array_map(function($p) use ($root){return '/' . $root . $p;}, $paths);
        
        $data = array("paths" => $paths);
        $data = array_merge($data, $options);
        
        $result = $this->post("links", $data);
        
        // Decode the json reply
        $result = json_decode($result);
        
        // Check for errors
        if (isset($result->error)) {
        	throw new \Exception("Error listing path " . $path . ": (" . $result->error . ") '" . $result->message . "'");
        }
        
        return $result;
    }

    /**
     * Update meta object
     *
     * Object structure:
     * {
     *  object_id: "4008"
     *  path: "/example"
     *  type: "dir" || "file"
     *  share_id: "0"
     *  share_owner: "21956799"
     *  company_id: NULL
     *  size: filesize in bytes, 0 for folders
     *  created_time: unix timestamp, e.g. "1389731126"
     *  modified_time: unix timestamp, e.g. "1389731126"
     *  date_last_synced: unix timestamp, e.g. "1389731126"
     *  removed_time: unix timestamp, e.g. "1389731126" or empty string for non-deleted files/folders
     *  mime_type: string
     *  revisions: array of revision objects
     * }
     *
     * @param string $action
     * @param string $path
     * @param array $meta contains action, path, and other attributes of the object to update
     *
     * @return stdClass using structure as noted above
     */
    private function updateObject($action, $path, $meta)
    {
        // Add action and path to meta
        $meta["action"] = $action;
        $meta["path"] = $path;

        $result = $this->post("update_objects", $this->encodeRequest("update_objects", array("meta" => array($meta))), true);

        // Return the object
        return $result->{"result"}[0]->{"object"};
    }

    /**
     * Create and execute cURL request to send data.
     *
     * @param  string  $method         API method
     * @param  string  $data           raw request
     * @param  boolean $decodeResponse true to decode response
     *
     * @return mixed  result from curl_exec
     */
    private function post($method, $data, $decodeResponse = false)
    {
        if (is_array($data)) {
            $data = str_replace('\\/', '/', json_encode($data));
        }

        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeaders($method));
        curl_setopt($this->curl, CURLOPT_URL, $this->api_url . "/" . $this->getEndpoint($method));
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_POST, 1);

        $result = curl_exec($this->curl);

        // If curl grossly failed, throw
        if ($result === false) {
            throw new \Exception("Curl failed to exec " . curl_error($this->curl));
        }

        // Decode the response if requested to do so
        if ($decodeResponse) {
            return $this->decodeResponse($result);
        } else {
            return $result;
        }
    }

    /**
     * Create and execute cURL request by GET method.
     *
     * @param  string $method API method
     *
     * @return mixed  result from curl_exec
     */
    protected function get($method)
    {
    	$method = str_replace("%2F", "/", rawurlencode($method));
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, null);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeaders($method, "GET"));
        curl_setopt($this->curl, CURLOPT_URL, $this->api_url . "/" . $this->GetEndpoint($method));
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HTTPGET, 1);

        $result = curl_exec($this->curl);

        // If curl grossly failed, throw
        if ($result == FALSE) {
            throw new \Exception("Curl failed to exec " . curl_error($this->curl));
        }

        return $result;
    }

    /**
     * Return which cloud API end point to use for a given method.
     *
     * @param  string $method API method
     *
     * @return string uri of endpoint without leading slash
     */
    private function getEndpoint($method)
    {
        if ($method == "has_object_parts_v2" || $method == "send_object_parts_v2" || $method == "get_object_parts_v2") {
	        return "jsonrpc_binary";
        } else if ($method == "update_objects" || $method == "list_objects") {
            return "jsonrpc";
        } else {
        	return "rest/" . $method;
        }
    }

    /**
     * Generate the HTTP headers need for a given Cloud API method.
     *
     * @param  string $method      API method
     * @param  string $http_method Optional, HTTP request method
     *
     * @return array  contains headers to use for HTTP requests
     */
    public function getHeaders($method, $http_method = "POST")
    {
        $headers = array();

        $consumer = new \Eher\OAuth\Consumer($this->signature['consumer_key'], $this->signature['shared_secret']);
        $signatureMethod = new \Eher\OAuth\HmacSha1();
        $token = new \Eher\OAuth\Token($this->signature['oauth_token'], $this->signature['oauth_secret']);
        $request = \Eher\OAuth\Request::from_consumer_and_token(
            $consumer,
            $token,
            $http_method,
            $this->api_url . "/" . $this->GetEndpoint($method),
            array()
        );
        $request->sign_request($signatureMethod, $consumer, $token);

        if ($method == "has_object_parts_v2" || $method == "send_object_parts_v2" || $method == "get_object_parts_v2") {
            array_push($headers, "Content-Type: application/octet-stream");
        }

        array_push($headers, "X-Api-Version: 1.0");
        array_push($headers, "X-Client-Type: api");
        array_push($headers, "X-Client-Time: " . time());
        array_push($headers, $request->to_header());

        return $headers;
    }

    /**
     * JSON encode request data.
     *
     * @param  string $method Cloud API method
     * @param  array  $json   contains data to be encoded
     *
     * @return string JSON formatted request body
     */
    private function encodeRequest($method, $json)
    {
        $request = array(
            'jsonrpc' => '2.0',
            'id' => '0',
            'method' => $method,
            'params' => $json,
            );

        return str_replace('\\/', '/', json_encode($request));
    }

    /**
     * Decode a JSON response.
     *
     * @param string $response JSON response
     *
     * @return array JSON decoded string
     */
    private function decodeResponse($response)
    {
        // Decode the json reply
        $result = json_decode($response);

        // Check for errors
        if (isset($result->error)) {
            throw new \Exception("Error: '" . $result->error->message . "'");
        }

        return $result;
    }

    /**
     * Copies the phar cacert from a phar into the temp directory.
     *
     * @param  string $pharCacertPath Path to the phar cacert.
     *
     * @return string Returns the path to the extracted cacert file.
     */
    public static function extractPharCacert($pharCacertPath)
    {
        $certFile = sys_get_temp_dir() . '/barracuda-copycom-cacert.crt';

        if (!file_exists($pharCacertPath)) {
            throw new \Exception("Could not find " . $pharCacertPath);
        }

        // Copy the cacert file from the phar if it is not in the temp folder.
        if (!file_exists($certFile) || filesize($certFile) != filesize($pharCacertPath)) {
            if (!copy($pharCacertPath, $certFile)) {
                throw new \Exception(
                    "Could not copy " . $pharCacertPath . " to " . $certFile . ": "
                    . var_export(error_get_last(), true)
                );
            }
        }

        return $certFile;
    }
}
