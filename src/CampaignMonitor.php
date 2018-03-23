<?php

namespace CliveWalkden\CampaignMonitor;

class CampaignMonitor
{
    private $api_key;
    private $api_endpoint = 'https://api.createsend.com/api/v3.1';
    private $format = 'json';

    private $client_id;
    private $list_id;

    const TIMEOUT = 10;

    public $verify_ssl = true;

    private $request_successful = false;
    private $last_error = '';
    private $last_response = [];
    private $last_request = [];

    public function __construct($api_key, $client_id = null)
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new \Exception('cURL not found and is required for this to work.');
        }

        if (!preg_match('/^[a-f0-9]{30,100}$/', $api_key)) {
            throw new \Exception("Invalid Campaign Monitor API Key `{$api_key}` supplied");
        } else {
            $this->api_key = $api_key;
        }

        if ($client_id) {
            $this->client_id = trim($client_id);
        }

        $this->last_response = ['headers' => null, 'body' => null];
    }

    /**
     * @param string $client_id
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;

        return $this;
    }

    /**
     * @param mixed $list_id
     */
    public function setListId($list_id)
    {
        $this->list_id = $list_id;

        return $this;
    }

    public function getApiEndpoint()
    {
        return $this->api_endpoint;
    }

    public function success()
    {
        return $this->request_successful;
    }

    /**
     * @return string|boolean The error message
     */
    public function getLastError()
    {
        return $this->last_error ?: false;
    }

    /**
     * @return array
     */
    public function getLastRequest()
    {
        return $this->last_request;
    }

    /**
     * Make an HTTP DELETE request - for deleting data
     *
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (if any)
     * @param   int $timeout Timeout limit for request in seconds
     *
     * @return  array|boolean   Assoc array of API response, decoded from JSON
     */
    public function delete($method, $args = array(), $timeout = self::TIMEOUT)
    {
        return $this->makeRequest('delete', $method, $args, $timeout);
    }

    /**
     * Make an HTTP GET request - for retrieving data
     *
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     *
     * @return  array|boolean   Assoc array of API response, decoded from JSON
     */
    public function get($method, $args = array(), $timeout = self::TIMEOUT)
    {
        return $this->makeRequest('get', $method, $args, $timeout);
    }

    /**
     * Make an HTTP PATCH request - for performing partial updates
     *
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     *
     * @return  array|boolean   Assoc array of API response, decoded from JSON
     */
    public function patch($method, $args = array(), $timeout = self::TIMEOUT)
    {
        return $this->makeRequest('patch', $method, $args, $timeout);
    }

    /**
     * Make an HTTP POST request - for creating and updating items
     *
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     *
     * @return  array|boolean   Assoc array of API response, decoded from JSON
     */
    public function post($method, $args = array(), $timeout = self::TIMEOUT)
    {
        return $this->makeRequest('post', $method, $args, $timeout);
    }

    /**
     * Make an HTTP PUT request - for creating new items
     *
     * @param   string $method URL of the API request method
     * @param   array $args Assoc array of arguments (usually your data)
     * @param   int $timeout Timeout limit for request in seconds
     *
     * @return  array|boolean   Assoc array of API response, decoded from JSON
     */
    public function put($method, $args = array(), $timeout = self::TIMEOUT)
    {
        return $this->makeRequest('put', $method, $args, $timeout);
    }

    private function formatMethod($method)
    {
        $method = str_replace(
            ['{client_id}', '{list_id}'],
            [$this->client_id, $this->list_id],
            $method
        );

        return $method;
    }

    private function makeRequest($http_verb, $method, $args = [], $timeout = self::TIMEOUT)
    {
        $method = $this->formatMethod($method);

        $url = $this->api_endpoint.$method.'.'.$this->format;

        $response = $this->prepareStateForRequest($http_verb, $method, $url, $timeout);

        $httpHeader = [
            'Accept: application/vnd.api+json',
            'Content-Type: application/vnd.api+json'
        ];

        if (isset($args["language"])) {
            $httpHeader[] = "Accept-Language: ".$args["language"];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_USERAGENT,
            'CliveWalkden/CampaignMonitor-API/3.1 (github.com/clivewalkden/campaign-monitor-api)');
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->api_key.':nopass');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);

        switch ($http_verb) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                $this->attachRequestPayload($ch, $args);
                break;

            case 'get':
                $query = http_build_query($args, '', '&');
                curl_setopt($ch, CURLOPT_URL, $url.'?'.$query);
                break;

            case 'delete':
                if ($args['EmailAddress']) {
                    $query = http_build_query(['email' => $args['EmailAddress']], '', '&');
                    curl_setopt($ch, CURLOPT_URL, $url.'?'.$query);
                }

                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            case 'patch':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                $this->attachRequestPayload($ch, $args);
                break;

            case 'put':
                if ($args['EmailAddress']) {
                    $query = http_build_query(['email' => $args['EmailAddress']], '', '&');
                    curl_setopt($ch, CURLOPT_URL, $url.'?'.$query);
                }

                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->attachRequestPayload($ch, $args);
                break;
        }

        $responseContent = curl_exec($ch);
        $response['headers'] = curl_getinfo($ch);
        $response = $this->setResponseState($response, $responseContent, $ch);
        $formattedResponse = $this->formatResponse($response);

        curl_close($ch);

        $isSuccess = $this->determineSuccess($response, $formattedResponse, $timeout);

        return is_array($formattedResponse) || !is_bool($formattedResponse) ? $formattedResponse : $isSuccess;
    }

    private function prepareStateForRequest($http_verb, $method, $url, $timeout)
    {
        $this->last_error = '';

        $this->request_successful = false;

        $this->last_response = array(
            'headers' => null, // array of details from curl_getinfo()
            'httpHeaders' => null, // array of HTTP headers
            'body' => null // content of the response
        );

        $this->last_request = array(
            'method' => $http_verb,
            'path' => $method,
            'url' => $url,
            'body' => '',
            'timeout' => $timeout,
        );

        return $this->last_response;
    }

    /**
     * Get the HTTP headers as an array of header-name => header-value pairs.
     *
     * The "Link" header is parsed into an associative array based on the
     * rel names it contains. The original value is available under
     * the "_raw" key.
     *
     * @param string $headersAsString
     *
     * @return array
     */
    private function getHeadersAsArray($headersAsString)
    {
        $headers = array();

        foreach (explode("\r\n", $headersAsString) as $i => $line) {
            if ($i === 0) { // HTTP code
                continue;
            }

            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            list($key, $value) = explode(': ', $line);

            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * Encode the data and attach it to the request
     *
     * @param   resource $ch cURL session handle, used by reference
     * @param   array $data Assoc array of data to attach
     */
    private function attachRequestPayload(&$ch, $data)
    {
        $encoded = json_encode($data);
        $this->last_request['body'] = $encoded;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }

    /**
     * Decode the response and format any error messages for debugging
     *
     * @param array $response The response from the curl request
     *
     * @return array|boolean    The JSON decoded into an array
     */
    private function formatResponse($response)
    {
        $this->last_response = $response;

        if (!empty($response['body'])) {
            return json_decode($response['body'], true);
        }

        return false;
    }

    /**
     * Do post-request formatting and setting state from the response
     *
     * @param array $response The response from the curl request
     * @param string $responseContent The body of the response from the curl request
     * @param resource $ch The curl resource
     *
     * @return array    The modified response
     */
    private function setResponseState($response, $responseContent, $ch)
    {
        if ($responseContent === false) {
            $this->last_error = curl_error($ch);
        } else {

            $headerSize = $response['headers']['header_size'];

            $response['httpHeaders'] = $this->getHeadersAsArray(substr($responseContent, 0, $headerSize));
            $response['body'] = substr($responseContent, $headerSize);

            if (isset($response['headers']['request_header'])) {
                $this->last_request['headers'] = $response['headers']['request_header'];
            }
        }

        return $response;
    }

    /**
     * Check if the response was successful or a failure. If it failed, store the error.
     *
     * @param array $response The response from the curl request
     * @param array|false $formattedResponse The response body payload from the curl request
     * @param int $timeout The timeout supplied to the curl request.
     *
     * @return boolean     If the request was successful
     */
    private function determineSuccess($response, $formattedResponse, $timeout)
    {
        $status = $this->findHTTPStatus($response, $formattedResponse);

        if ($status >= 200 && $status <= 299) {
            $this->request_successful = true;
            return true;
        }

        if (isset($formattedResponse['detail'])) {
            $this->last_error = sprintf('%d: %s', $formattedResponse['status'], $formattedResponse['detail']);
            return false;
        }

        if ($timeout > 0 && $response['headers'] && $response['headers']['total_time'] >= $timeout) {
            $this->last_error = sprintf('Request timed out after %f seconds.', $response['headers']['total_time']);
            return false;
        }

        $this->last_error = 'Unknown error, call getLastResponse() to find out what happened.';
        return false;
    }

    /**
     * Find the HTTP status code from the headers or API response body
     *
     * @param array $response The response from the curl request
     * @param array|false $formattedResponse The response body payload from the curl request
     *
     * @return int  HTTP status code
     */
    private function findHTTPStatus($response, $formattedResponse)
    {
        if (!empty($response['headers']) && isset($response['headers']['http_code'])) {
            return (int) $response['headers']['http_code'];
        }

        if (!empty($response['body']) && isset($formattedResponse['status'])) {
            return (int) $formattedResponse['status'];
        }

        return 418;
    }
}