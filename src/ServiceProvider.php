<?php
namespace DreamFactory\Core\User;

use DreamFactory\Core\Resources\System\SystemResourceManager;
use DreamFactory\Core\Resources\System\SystemResourceType;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\User\Models\UserConfig;
use DreamFactory\Core\User\Services\User;
use DreamFactory\Core\User\Resources\System\User as UserResource;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df){
            $df->addType(
                new ServiceType([
                    'name'           => 'user',
                    'label'          => 'User service',
                    'description'    => 'User service to allow user management.',
                    'group'          => ServiceTypeGroups::USER,
                    'singleton'      => true,
                    'config_handler' => UserConfig::class,
                    'factory'        => function ($config){
                        return new User($config);
                    },
                ])
            );
        });

        // Add our service types.
        $this->app->resolving('df.system.resource', function (SystemResourceManager $df){
            $df->addType(
                new SystemResourceType([
            'name'        => 'user',
            'label'       => 'User Management',
            'description' => 'Allows user management capability.',
            'class_name'  => UserResource::class,
            'singleton'   => false,
            'read_only'   => false
                ])
            );
        });
    }
}
