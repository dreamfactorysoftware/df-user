<?php

namespace DreamFactory\Core\User\Database\Seeds;

use DreamFactory\Core\Database\Seeds\BaseModelSeeder;
use DreamFactory\Core\Models\ServiceType;
use DreamFactory\Core\User\Models\UserConfig;
use DreamFactory\Core\User\Services\User;
use DreamFactory\Core\Enums\ServiceTypeGroups;

class ServiceTypeSeeder extends BaseModelSeeder
{
    protected $modelClass = ServiceType::class;

    protected $records = [
        [
            'name'           => 'user',
            'class_name'     => User::class,
            'config_handler' => UserConfig::class,
            'label'          => 'User service',
            'description'    => 'User service to allow user management.',
            'group'          => ServiceTypeGroups::USER,
            'singleton'      => true
        ]
    ];
}