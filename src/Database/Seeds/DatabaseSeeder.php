<?php
namespace DreamFactory\Core\User\Database\Seeds;

use DreamFactory\Core\Models\Service;
use DreamFactory\Core\Models\SystemResource;
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
                    'class_name'     => "DreamFactory\\Core\\User\\Services\\User",
                    'config_handler' => "DreamFactory\\Core\\User\\Models\\UserConfig",
                    'label'          => 'User service',
                    'description'    => 'User service to allow user management.',
                    'group'          => 'users',
                    'singleton'      => 1
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
                    'is_active'   => 1,
                    'type'        => 'user',
                    'mutable'     => 1,
                    'deletable'   => 0,
                    'config'      => [
                        'allow_open_registration' => 0
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
                    'class_name'  => "DreamFactory\\Core\\User\\Resources\\System\\User",
                    'model_name'  => "DreamFactory\\Core\\Models\\User",
                    'singleton'   => 0,
                    'read_only'   => 0
                ]
            );
            $this->command->info('User system resource successfully seeded!');
        }
    }
}