<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2025 Mikael Nordin <kano@sunet.se>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\ImapManager\Service;

use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

class StalwartService
{
    public function __construct(
        private IAppConfig $appConfig,
        private IClientService $clientService,
        private LoggerInterface $logger,
    ) {}

    private function getBaseUrl(): string
    {
        return rtrim($this->appConfig->getValueString('imap_manager', 'stalwart_url', ''), '/');
    }

    private function getAuthHeader(): string
    {
        $user = $this->appConfig->getValueString('imap_manager', 'stalwart_admin_user', '');
        $password = $this->appConfig->getValueString('imap_manager', 'stalwart_admin_password', '');
        return 'Basic ' . base64_encode($user . ':' . $password);
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        $client = $this->clientService->newClient();
        $url = $this->getBaseUrl() . $path;
        $options = [
            'headers' => [
                'Authorization' => $this->getAuthHeader(),
                'Content-Type' => 'application/json',
            ],
        ];
        if ($body !== null) {
            $options['body'] = json_encode($body);
        }
        $response = $client->$method($url, $options);
        $responseBody = $response->getBody();
        return json_decode($responseBody, true) ?? [];
    }

    public function listPasswords(string $email): array
    {
        $data = $this->request('get', '/api/principal/' . urlencode($email));
        $secrets = $data['data']['secrets'] ?? [];
        $passwords = [];
        foreach ($secrets as $secret) {
            if (str_starts_with($secret, '$app$')) {
                $parts = explode('$', $secret);
                // Format: $app$name$password — split gives ['', 'app', 'name', 'password']
                if (count($parts) >= 4) {
                    $passwords[] = [
                        'name' => $parts[2],
                        'password' => $parts[3],
                    ];
                }
            }
        }
        return $passwords;
    }

    public function createPassword(string $email, string $name, string $password): void
    {
        $value = '$app$' . $name . '$' . $password;
        $this->request('patch', '/api/principal/' . urlencode($email), [
            ['action' => 'addItem', 'field' => 'secrets', 'value' => $value],
        ]);
    }

    public function deletePassword(string $email, string $name, string $password): void
    {
        $value = '$app$' . $name . '$' . $password;
        $this->request('patch', '/api/principal/' . urlencode($email), [
            ['action' => 'removeItem', 'field' => 'secrets', 'value' => $value],
        ]);
    }

    public function testConnection(): bool
    {
        try {
            $this->request('get', '/api/principal/admin');
            return true;
        } catch (\Exception $e) {
            $this->logger->warning('Stalwart connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}
