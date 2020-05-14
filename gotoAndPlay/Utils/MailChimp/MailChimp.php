<?php
namespace gotoAndPlay\Utils\MailChimp;

use gotoAndPlay\Utils\Batch as Batch;

class MailChimp {

    private $api_key;
    private $api_endpoint = 'https://<dc>.api.mailchimp.com/3.0';

    const TIMEOUT = 10;

    /*  SSL Verification
        Read before disabling:
        http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/
    */
    public $verify_ssl = true;

    private $request_successful = false;
    private $last_error         = '';
    private $last_response      = [];
    private $last_request       = [];

    public function __construct($api_key, $api_endpoint = null) {
        $this->api_key = $api_key;

        if ($api_endpoint === null) {
            if (strpos($this->api_key, '-') === false) {
                throw new \Exception("Invalid MailChimp API key `{$api_key}` supplied.");
            }

            list(, $data_center) = explode('-', $this->api_key);
            $this->api_endpoint = str_replace('<dc>', $data_center, $this->api_endpoint);
        } else {
            $this->api_endpoint = $api_endpoint;
        }

        $this->last_response = ['headers' => null, 'body' => null];
    }

    public function newBatch($batch_id = null) {
        return new Batch($this, $batch_id);
    }

    public function getApiEndpoint() {
        return $this->api_endpoint;
    }

    public function subscriberHash($email) {
        return md5(strtolower($email));
    }

    public function success() {
        return $this->request_successful;
    }

    public function getLastError() {
        return $this->last_error ?: false;
    }

    public function getLastResponse() {
        return $this->last_response;
    }

    public function getLastRequest() {
        return $this->last_request;
    }

    public function delete($method, $args = [], $timeout = self::TIMEOUT) {
        return $this->makeRequest('delete', $method, $args, $timeout);
    }

    public function get($method, $args = [], $timeout = self::TIMEOUT) {
        return $this->makeRequest('get', $method, $args, $timeout);
    }

    public function patch($method, $args = [], $timeout = self::TIMEOUT) {
        return $this->makeRequest('patch', $method, $args, $timeout);
    }

    public function post($method, $args = [], $timeout = self::TIMEOUT) {
        return $this->makeRequest('post', $method, $args, $timeout);
    }

    public function put($method, $args = [], $timeout = self::TIMEOUT) {
        return $this->makeRequest('put', $method, $args, $timeout);
    }

    private function makeRequest($http_verb, $method, $args = [], $timeout = self::TIMEOUT) {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new \Exception("cURL support is required, but can't be found.");
        }

        $url = $this->api_endpoint . '/' . $method;

        $response = $this->prepareStateForRequest($http_verb, $method, $url, $timeout);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/vnd.api+json',
            'Content-Type: application/vnd.api+json',
            'Authorization: apikey ' . $this->api_key,
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DrewM/MailChimp-API/3.0 (github.com/drewm/mailchimp-api)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        switch ($http_verb) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                $this->attachRequestPayload($ch, $args);
            break;

            case 'get':
                $query = http_build_query($args, '', '&');
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
            break;

            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;

            case 'patch':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                $this->attachRequestPayload($ch, $args);
            break;

            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->attachRequestPayload($ch, $args);
            break;
        }

        $responseContent     = curl_exec($ch);
        $response['headers'] = curl_getinfo($ch);
        $response            = $this->setResponseState($response, $responseContent, $ch);
        $formattedResponse   = $this->formatResponse($response);

        curl_close($ch);

        $this->determineSuccess($response, $formattedResponse, $timeout);

        return $formattedResponse;
    }

    private function prepareStateForRequest($http_verb, $method, $url, $timeout) {
        $this->last_error = '';

        $this->request_successful = false;

        $this->last_response = [
            'headers' => null, // array of details from curl_getinfo()
            'httpHeaders' => null, // array of HTTP headers
            'body' => null // content of the response
        ];

        $this->last_request = [
            'method' => $http_verb,
            'path' => $method,
            'url' => $url,
            'body' => '',
            'timeout' => $timeout,
        ];

        return $this->last_response;
    }

    private function getHeadersAsArray($headersAsString) {
        $headers = [];

        foreach (explode("\r\n", $headersAsString) as $i => $line) {
            if ($i === 0) { // HTTP code
                continue;
            }

            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            list($key, $value) = explode(': ', $line);

            if ($key == 'Link') {
                $value = array_merge(
                    ['_raw' => $value],
                    $this->getLinkHeaderAsArray($value)
                );
            }

            $headers[$key] = $value;
        }

        return $headers;
    }

    private function getLinkHeaderAsArray($linkHeaderAsString) {
        $urls = [];

        if (preg_match_all('/<(.*?)>\s*;\s*rel="(.*?)"\s*/', $linkHeaderAsString, $matches)) {
            foreach ($matches[2] as $i => $relName) {
                $urls[$relName] = $matches[1][$i];
            }
        }

        return $urls;
    }

    private function attachRequestPayload(&$ch, $data) {
        $encoded                    = json_encode($data);
        $this->last_request['body'] = $encoded;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }

    private function formatResponse($response) {
        $this->last_response = $response;

        if (!empty($response['body'])) {
            return json_decode($response['body'], true);
        }

        return false;
    }

    private function setResponseState($response, $responseContent, $ch) {
        if ($responseContent === false) {
            $this->last_error = curl_error($ch);
        } else {
            $headerSize = $response['headers']['header_size'];

            $response['httpHeaders'] = $this->getHeadersAsArray(substr($responseContent, 0, $headerSize));
            $response['body']        = substr($responseContent, $headerSize);

            if (isset($response['headers']['request_header'])) {
                $this->last_request['headers'] = $response['headers']['request_header'];
            }
        }

        return $response;
    }

    private function determineSuccess($response, $formattedResponse, $timeout) {
        $status = $this->findHttpStatus($response, $formattedResponse);

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

    private function findHttpStatus($response, $formattedResponse) {
        if (!empty($response['headers']) && isset($response['headers']['http_code'])) {
            return (int) $response['headers']['http_code'];
        }

        if (!empty($response['body']) && isset($formattedResponse['status'])) {
            return (int) $formattedResponse['status'];
        }

        return 418;
    }

}
