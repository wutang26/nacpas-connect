<?php

namespace App\Console\Commands;

use App\Models\NetworkCredential;
use App\Models\NetworkIntegration;
use App\Models\Site;
use App\Services\Network\NetworkManager;
use Illuminate\Console\Command;

class ConfigureOmadaIntegration extends Command
{
    protected $signature = 'omada:configure {site_id}';

    protected $description = 'Configure Omada Cloud Essentials integration for a site';

    public function handle(NetworkManager $networkManager): int
    {
        $site = Site::with('organization')->find(
            (int) $this->argument('site_id')
        );

        if (!$site) {
            $this->error('Site was not found.');
            return self::FAILURE;
        }

        $defaultUrl = 'https://euw1-api-omada-essential-controller.tplinkcloud.com';

        $this->info('NACPASI - Omada Cloud Essentials Configuration');
        $this->line('Site: '.$site->name);
        $this->line('Organization: '.$site->organization?->name);

        $apiUrl = trim((string) $this->ask('Omada API URL', $defaultUrl));

        $controllerId = trim((string) $this->ask(
            'Omada Controller ID',
            'ffff16b02b5dc7ea5872cb5c6738d655'
        ));

        $username = trim((string) $this->ask(
            'Hotspot Operator Username',
            'nacpas_api_operator'
        ));

        $password = $this->secret('Hotspot Operator Password');

        if (!$password) {
            $this->error('Password is required.');
            return self::FAILURE;
        }

        if (
            !filter_var($apiUrl, FILTER_VALIDATE_URL) ||
            !str_starts_with($apiUrl, 'https://')
        ) {
            $this->error('A valid HTTPS API URL is required.');
            return self::FAILURE;
        }

        if ($controllerId === '' || $username === '') {
            $this->error('Controller ID and username are required.');
            return self::FAILURE;
        }

        $integration = NetworkIntegration::firstOrNew([
            'site_id' => $site->id,
            'provider' => 'omada',
        ]);

        $settings = $integration->settings ?? [];

        $settings['controller_id'] = $controllerId;
        $settings['api_region'] = 'euw1';

        $integration->fill([
            'organization_id' => $site->organization_id,
            'name' => 'Omada Cloud Essentials',
            'controller_url' => rtrim($apiUrl, '/'),
            'controller_version' => '6.3.0.200',
            'status' => 'pending',
            'last_error' => null,
            'settings' => $settings,
        ]);

        $integration->save();

        $credential = NetworkCredential::query()
            ->where('site_id', $site->id)
            ->where('provider', 'omada')
            ->where('credential_type', 'hotspot_operator')
            ->latest('id')
            ->first();

        if (!$credential) {
            $credential = new NetworkCredential();
        }

        $credential->fill([
            'site_id' => $site->id,
            'device_id' => null,
            'provider' => 'omada',
            'credential_type' => 'hotspot_operator',
            'username' => $username,
            'secret' => $password,
            'endpoint' => rtrim($apiUrl, '/'),
            'metadata' => [
                'integration_id' => $integration->id,
                'controller_id' => $controllerId,
            ],
            'status' => 'active',
        ]);

        $credential->save();

        unset($password);

        $this->info('Omada integration and encrypted credentials saved.');
        $this->newLine();
        $this->info('Testing Omada API authentication...');

        $result = $networkManager->testConnection($integration);

        if ($result['success'] ?? false) {
            $this->info('OMADA CONNECTION SUCCESSFUL');
            $this->line($result['message']);

            $this->line(
                'CSRF token received: '.(
                    ($result['csrf_token_received'] ?? false) ? 'YES' : 'NO'
                )
            );

            $this->line(
                'Session cookie received: '.(
                    ($result['session_cookie_received'] ?? false) ? 'YES' : 'NO'
                )
            );

            return self::SUCCESS;
        }

        $this->error('OMADA CONNECTION FAILED');
        $this->line($result['message'] ?? 'Unknown error.');

        return self::FAILURE;
    }
}
