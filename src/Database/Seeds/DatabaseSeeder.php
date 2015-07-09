<?php
namespace DreamFactory\Core\User\Database\Seeds;

use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\Models\SystemResource;
use DreamFactory\Core\User\Models\UserConfig;
use DreamFactory\Core\User\Services\User;
use Illuminate\Database\Seeder;
use DreamFactory\Core\Models\ServiceType;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        if (!ServiceType::whereName('user')->exists()) {
            // Add the service type
            ServiceType::create(
                [
                    'name'           => 'user',
                    'class_name'     => User::class,
                    'config_handler' => UserConfig::class,
                    'label'          => 'User service',
                    'description'    => 'User service to allow user management.',
                    'group'          => ServiceTypeGroups::USER,
                    'singleton'      => true
                ]
            );
            $this->command->info('User Management service type seeded!');
        }

        if (!Service::whereName('user')->exists()) {
            Service::create(
                [
                    'name'        => 'user',
                    'label'       => 'User Management',
                    'description' => 'Service for managing system users.',
                    'is_active'   => true,
                    'type'        => 'user',
                    'mutable'     => true,
                    'deletable'   => false,
                    'config'      => [
                        'allow_open_registration' => false
                    ]
                ]
            );
            $this->command->info('User Management service seeded!');
        }

        if (!SystemResource::whereName('user')->exists()) {
            SystemResource::create(
                [
                    'name'        => 'user',
                    'label'       => 'User Management',
                    'description' => 'Allows user management capability.',
                    'class_name'  => \DreamFactory\Core\User\Resources\System\User::class,
                    'model_name'  => \DreamFactory\Core\Models\User::class,
                    'singleton'   => false,
                    'read_only'   => false
                ]
            );
            $this->command->info('User system resource successfully seeded!');
        }
    }
}