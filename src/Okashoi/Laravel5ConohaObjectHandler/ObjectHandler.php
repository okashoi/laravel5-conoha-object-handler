<?php

namespace Okashoi\Laravel5ConohaObjectHandler;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

/**
 * Class ObjectHandler
 * @package Okashoi\Laravel5ConohaObjectHandler
 *
 * Conoha Object Handler with REST API
 */
class ObjectHandler
{

    /**
     * @var \GuzzleHttp\Client GuzzleHttp client.
     */
    protected $_client;

    /**
     * @var string Auth token.
     */
    protected $_token;

    /**
     * @var \Carbon\Carbon Token expiration datetime.
     */
    protected $_tokenExpiresAt;

    /**
     * @var string|null Cache key to store an auth token. If null is set, the token is not cached.
     */
    protected $_tokenCacheKey;

    /**
     * constructor
     *
     * @param string|null $tokenCacheKey This is set to $_tokenCacheKey property.
     */
    public function __construct($tokenCacheKey = null)
    {
        $this->_client = new Client(['base_uri' => config('conoha.base_uri')]);
        $this->_tokenCacheKey = $tokenCacheKey;
        $this->_setToken();
    }

    /**
     * Set an auth token.
     */
    protected function _setToken()
    {

        // check if the token is cached
        if (isset($this->_tokenCacheKey) && Cache::has($this->_tokenCacheKey)) {
            $token = Cache::get($this->_tokenCacheKey);
            $this->_token = $token->id;
            $this->_tokenExpiresAt = Carbon::parse($token->expires);
        }

        // validate the token
        if ($this->_hasValidToken()) {
            return;
        }

        // if the token is not cached or expired, get a new auth token
        $requestBody = [
            'auth' => [
                'tenantId' => config('conoha.tenant_id'),
                'passwordCredentials' => [
                    'username' => config('conoha.username'),
                    'password' => config('conoha.password'),
                ],
            ],
        ];

        $response = $this->_client->post(
            config('conoha.auth_endpoint'),
            [
                'json' => $requestBody,
            ]
        );

        $responseBody = json_decode($response->getBody());

        if (isset($this->_tokenCacheKey)) {
            // cache the token
            Cache::put($this->_tokenCacheKey, $responseBody->access->token, 60 * 24);
        }

        // set the token to properties
        $this->_token = $responseBody->access->token->id;
        $this->_tokenExpiresAt = Carbon::parse($responseBody->access->token->expires);
    }

    /**
     * Validate an auth token in properties.
     *
     * @return boolean false if the token is not set or expired, true otherwise.
     */
    protected function _hasValidToken()
    {
        if (!isset($this->_token) || !isset($this->_tokenExpiresAt)) {
            return false;
        }
        return $this->_tokenExpiresAt > Carbon::now();
    }

    /**
     * Get a list of the objects in specified container.
     *
     * @param string $containerName Container name.
     * @return mixed List of the objects.
     * @throws \Exception when fails.
     */
    public function getList($containerName)
    {
        if (!$this->_hasValidToken()) {
            $this->_setToken();
        }

        try {
            $response = $this->_client->get(
                'nc_' . config('conoha.tenant_id') . '/' . $containerName,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'X-Auth-Token' => $this->_token,
                    ],
                ]
            );
        } catch (\Exception $e) {
            throw new \Exception('Failed to get a list of objects.', $e->getCode(), $e);
        }

        return json_decode($response->getBody());
    }

    /**
     * Upload an object to specified container.
     *
     * @param string $containerName Container name.
     * @param string $objectName Object name.
     * @param string $filePath Path of file to upload.
     * @param string $contentType Content type of the object.
     * @throws \InvalidArgumentException if file doesn't exist or directory name is given.
     * @throws \RuntimeException when fails to open the file.
     * @throws \Exception when fails to upload.
     */
    public function upload($containerName, $objectName, $filePath, $contentType)
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('File doesn\'t exist.');
        }

        if (is_dir($filePath)) {
            throw new \InvalidArgumentException('Directory name is given.');
        }

        $file = fopen($filePath, 'r');
        if ($file === false) {
            throw new \RuntimeException('Failed to open the file.');
        }

        if (!$this->_hasValidToken()) {
            $this->_setToken();
        }

        try {
            $this->_client->put(
                'nc_' . config('conoha.tenant_id') . '/' . $containerName . '/' . $objectName,
                [
                    'headers' => [
                        'X-Auth-Token' => $this->_token,
                        'Content-Type' => $contentType,
                        'Content-length' => 0,
                    ],
                    'body' => $file,
                ]
            );
        } catch (\Exception $e) {
            throw new \Exception('Failed to upload.', $e->getCode(), $e);
        }
    }

    /**
     * Download an object.
     *
     * @param string $containerName Container name.
     * @param string $objectName Object name.
     * @return \Psr\Http\Message\ResponseInterface Response.
     * @throws \Exception when failed to download.
     */
    public function download($containerName, $objectName)
    {
        if (!$this->_hasValidToken()) {
            $this->_setToken();
        }

        try {
            $response = $this->_client->get(
                'nc_' . config('conoha.tenant_id') . '/' . $containerName . '/' . $objectName,
                [
                    'headers' => [
                        'X-Auth-Token' => $this->_token,
                        'Content-length' => 0,
                    ],
                ]
            );
        } catch (\Exception $e) {
            throw new \Exception('Failed to download.', $e->getCode(), $e);
        }

        return $response;
    }

    /**
     * Delete an object.
     *
     * @param string $containerName Container name.
     * @param string $objectName Object name.
     * @throws \Exception when failed to delete.
     */
    public function delete($containerName, $objectName)
    {
        if (!$this->_hasValidToken()) {
            $this->_setToken();
        }

        try {
            $this->_client->delete(
                'nc_' . config('conoha.tenant_id') . '/' . $containerName . '/' . $objectName,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'X-Auth-Token' => $this->_token,
                        'Content-length' => 0,
                    ],
                ]
            );
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete.', $e->getCode(), $e);
        }
    }
}
