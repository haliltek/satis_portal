<?php
// classes/TokenManager.php

namespace Proje;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TokenManager
{
    private Client $client;
    private array $config;
    private ?string $accessToken = null;
    private int $tokenExpiry = 0;
    private string $logFile;

    public function __construct(array $config)
    {
        $defaults = [
            'tokenUrl'     => '',
            'clientId'     => '',
            'clientSecret' => '',
            'username'     => '',
            'password'     => '',
            'firmNr'       => '',
        ];
        $this->config  = array_merge($defaults, $config);
        $this->client  = new Client();
        $this->logFile = __DIR__ . '/../debug.log';
    }

    public function getAccessToken(): array
    {
        if ($this->accessToken && $this->tokenExpiry > time()) {
            $cached = [
                'access_token'  => $this->accessToken,
                'token_type'    => 'Bearer',
                'expires_in'    => $this->tokenExpiry - time(),
                'refresh_token' => '',
                'as:client_id'  => $this->config['clientId'],
                'userName'      => $this->config['username'],
                'firmNo'        => $this->config['firmNr'],
            ];
            return $cached;
        }

        return $this->requestAccessToken();
    }

    private function requestAccessToken(): array
    {
        try {
            $response = $this->client->request('POST', $this->config['tokenUrl'], [
                'headers'     => [
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($this->config['clientId'] . ':' . $this->config['clientSecret']),
                ],
                'form_params' => [
                    'grant_type' => 'password',
                    'username'   => $this->config['username'],
                    'password'   => $this->config['password'],
                    'firmno'     => $this->config['firmNr'],
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            // Token bilgilerini sakla
            $this->accessToken = $data['access_token'] ?? '';
            $this->tokenExpiry = time() + (($data['expires_in'] ?? 3600));

            return $data;
        } catch (RequestException $e) {
            $msg = $e->getMessage();
            if ($e->hasResponse()) {
                $errorBody = (string) $e->getResponse()->getBody();
                $errorData = json_decode($errorBody, true) ?: [];
                $description = $errorData['error_description'] ?? $msg;
                $msg = 'Token alınırken hata oluştu: ' . $description;
            }
            error_log("[TokenManager] Exception: {$msg}\n", 3, $this->logFile);
            throw new \Exception($msg);
        }
    }
}
