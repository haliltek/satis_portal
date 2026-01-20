<?php
namespace Proje;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class RestClient
{
    private $client;
    private $tokenManager;
    private $config;
    private string $logFile;

    public function __construct(TokenManager $tokenManager, array $config)
    {
        $this->tokenManager = $tokenManager;
        $this->config = $config;
        $this->logFile      = __DIR__ . '/../debug.log';
        $this->client = new Client([
            'base_uri' => $this->config['apiBaseUrl'],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    private function getAuthHeaders()
    {
        $tokenData = $this->tokenManager->getAccessToken();
        
        return [
            'Authorization' => 'Bearer ' . $tokenData['access_token'],
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
    }

    public function get(string $endpoint, array $query = [])
    {
        $headers = $this->getAuthHeaders();
        error_log(
            "[RestClient][REQUEST] GET {$endpoint}\n" .
            "Headers: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n" .
            "Query:   " . json_encode($query, JSON_UNESCAPED_UNICODE) . "\n\n",
            3,
            $this->logFile
        );

        try {
            $response = $this->client->request('GET', $endpoint, [
                'headers' => $headers,
                'query'   => $query,
            ]);

            $status      = $response->getStatusCode();
            $respHeaders = $response->getHeaders();
            $body        = (string) $response->getBody();

            error_log(
                "[RestClient][RESPONSE] GET {$endpoint}\n" .
                "Status:  {$status}\n" .
                "Headers: " . json_encode($respHeaders, JSON_UNESCAPED_UNICODE) . "\n" .
                "Body:    {$body}\n\n",
                3,
                $this->logFile
            );

            return json_decode($body, true) ?? [];
        } catch (RequestException $e) {
            error_log(
                "[RestClient][EXCEPTION] GET {$endpoint} failed: " . $e->getMessage() . "\n\n",
                3,
                $this->logFile
            );
            return $this->handleException($e);
        }
    }
    
    /**
     * Send a raw JSON string as the request body.
     */
    public function postRaw(string $endpoint, string $rawJsonBody): array
    {
        try {
            $response = $this->client->request('POST', $endpoint, [
                'headers' => array_merge(
                    $this->getAuthHeaders(),
                    ['Content-Type' => 'application/json']
                ),
                'body'    => $rawJsonBody,
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function post(string $endpoint, array $data = []): array
    {
        // 1) Hazırlanan header ve body'i logla
        $headers = $this->getAuthHeaders();
        error_log(
            "[RestClient][REQUEST-JSON] POST {$endpoint}\n" .
            "Headers: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n" .
            "Body:    " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n",
            3,
            $this->logFile
        );

        try {
            // 2) İsteği at
            $response = $this->client->request('POST', $endpoint, [
                'headers' => $headers,
                'json'    => $data,
            ]);

            $status    = $response->getStatusCode();
            $respHeaders = $response->getHeaders();
            $body      = (string) $response->getBody();

            // 3) Gelen cevabı da logla
            error_log(
                "[RestClient][RESPONSE-JSON] POST {$endpoint}\n" .
                "Status:  {$status}\n" .
                "Headers: " . json_encode($respHeaders, JSON_UNESCAPED_UNICODE) . "\n" .
                "Body:    {$body}\n\n",
                3,
                $this->logFile
            );

            return json_decode($body, true) ?? [];

        } catch (RequestException $e) {
            // 4) Eğer hata varsa, önce API'den gelen cevabı handleException zaten logluyor
            $errorMsg = $e->getMessage();
            error_log(
                "[RestClient][EXCEPTION] POST {$endpoint} failed: {$errorMsg}\n\n",
                3,
                $this->logFile
            );
            return $this->handleException($e);
        }
    }

    public function put(string $endpoint, array $data = [])
    {
        $headers = $this->getAuthHeaders();
        error_log(
            "[RestClient][REQUEST-JSON] PUT {$endpoint}\n" .
            "Headers: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n" .
            "Body:    " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n",
            3,
            $this->logFile
        );

        try {
            $response = $this->client->request('PUT', $endpoint, [
                'headers' => $headers,
                'json'    => $data,
            ]);

            $status      = $response->getStatusCode();
            $respHeaders = $response->getHeaders();
            $body        = (string) $response->getBody();

            error_log(
                "[RestClient][RESPONSE-JSON] PUT {$endpoint}\n" .
                "Status:  {$status}\n" .
                "Headers: " . json_encode($respHeaders, JSON_UNESCAPED_UNICODE) . "\n" .
                "Body:    {$body}\n\n",
                3,
                $this->logFile
            );

            return json_decode($body, true) ?? [];
        } catch (RequestException $e) {
            error_log(
                "[RestClient][EXCEPTION] PUT {$endpoint} failed: " . $e->getMessage() . "\n\n",
                3,
                $this->logFile
            );
            return $this->handleException($e);
        }
    }

    public function patch(string $endpoint, array $data = [])
    {
        $headers = $this->getAuthHeaders();
        error_log(
            "[RestClient][REQUEST-JSON] PATCH {$endpoint}\n" .
            "Headers: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n" .
            "Body:    " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n",
            3,
            $this->logFile
        );

        try {
            $response = $this->client->request('PATCH', $endpoint, [
                'headers' => $headers,
                'json'    => $data,
            ]);

            $status      = $response->getStatusCode();
            $respHeaders = $response->getHeaders();
            $body        = (string) $response->getBody();

            error_log(
                "[RestClient][RESPONSE-JSON] PATCH {$endpoint}\n" .
                "Status:  {$status}\n" .
                "Headers: " . json_encode($respHeaders, JSON_UNESCAPED_UNICODE) . "\n" .
                "Body:    {$body}\n\n",
                3,
                $this->logFile
            );

            return json_decode($body, true) ?? [];
        } catch (RequestException $e) {
            error_log(
                "[RestClient][EXCEPTION] PATCH {$endpoint} failed: " . $e->getMessage() . "\n\n",
                3,
                $this->logFile
            );
            return $this->handleException($e);
        }
    }

    private function handleException(RequestException $e)
    {
        if ($e->hasResponse()) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            error_log("[RestClient] Full Error Response: " . $responseBody . "\n", 3, __DIR__ . '/../debug.log');
            $error = json_decode($responseBody, true);
            if (isset($error['Message']) && isset($error['ModelState'])) {
                $errorMessages = "";
                foreach ($error['ModelState'] as $key => $errors) {
                    foreach ($errors as $err) {
                        $errorMessages .= $err . " ";
                    }
                }
                return [
                    'error' => $error['Message'],
                    'error_description' => trim($errorMessages)
                ];
            }
            // Eğer farklı bir hata yapısı varsa:
            $msg = "REST API error: " . ($error['error'] ?? 'Bilinmeyen bir hata oluştu.') .
                   " - " . ($error['error_description'] ?? 'Hata açıklaması bulunamadı.');
            error_log("[RestClient] Error: $msg\n", 3, __DIR__ . '/../debug.log');
            return [
                'error' => $error['error'] ?? 'Bilinmeyen bir hata oluştu.',
                'error_description' => $error['error_description'] ?? 'Hata açıklaması bulunamadı.'
            ];
        }
        $msg = "REST API request error: " . $e->getMessage();
        error_log("[RestClient] Error: $msg\n", 3, __DIR__ . '/../debug.log');
        return [
            'error' => 'İstek sırasında bir hata oluştu.',
            'error_description' => $e->getMessage()
        ];
    }
}
