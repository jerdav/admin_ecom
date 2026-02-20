<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Ecommerce\FeatureFlagService;
use App\Services\Ecommerce\SettingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ShopInitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shop:init
        {project : Nom du projet e-commerce}
        {--admin-name=Admin : Nom de l\'administrateur principal}
        {--admin-email=admin@example.com : Email de l\'administrateur principal}
        {--admin-password=admin12345 : Mot de passe initial de l\'administrateur principal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialise la configuration minimale e-commerce (settings, feature flags, admin).';

    /**
     * Execute the console command.
     */
    public function handle(SettingService $settings, FeatureFlagService $featureFlags): int
    {
        $project = trim((string) $this->argument('project'));

        if ($project === '') {
            $this->error('Le nom du projet est obligatoire.');

            return self::FAILURE;
        }

        $adminEmail = strtolower(trim((string) $this->option('admin-email')));
        $adminName = trim((string) $this->option('admin-name'));
        $adminPassword = (string) $this->option('admin-password');

        DB::transaction(function () use ($settings, $featureFlags, $project, $adminEmail, $adminName, $adminPassword) {
            $defaultSettings = [
                'shop.name' => $project,
                'shop.currency' => (string) config('ecommerce.currency', 'EUR'),
                'shop.locale' => (string) config('app.locale', 'en'),
                'orders.auto_confirm' => (bool) config('ecommerce.orders.auto_confirm', false),
                'mail.order_notifications' => (bool) config('ecommerce.mail.order_notifications', true),
                'users.allow_secondary_users' => (bool) config('ecommerce.users.allow_secondary_users', false),
                'users.default_role' => (string) config('ecommerce.users.default_role', User::ROLE_CUSTOMER),
            ];

            foreach ($defaultSettings as $key => $value) {
                $settings->set($key, $value, $this->inferType($value));
            }

            $defaultFlags = (array) config('ecommerce.feature_flags', []);

            foreach ($defaultFlags as $code => $enabled) {
                $featureFlags->set((string) $code, (bool) $enabled, 'global');
            }

            $admin = User::query()->updateOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => $adminName !== '' ? $adminName : 'Admin',
                    'password' => Hash::make($adminPassword),
                    'role' => (string) config('ecommerce.users.admin_role', User::ROLE_ADMIN),
                ]
            );

            $settings->set('users.admin.id', (int) $admin->id, 'int', 'users');
            $settings->set('users.admin.role', (string) $admin->role, 'string', 'users');
        });

        $this->info('Initialisation terminee.');
        $this->line('Commande: php artisan shop:init "MonShop"');

        return self::SUCCESS;
    }

    private function inferType(mixed $value): string
    {
        return match (true) {
            is_int($value) => 'int',
            is_bool($value) => 'bool',
            is_array($value) => 'json',
            default => 'string',
        };
    }
}
