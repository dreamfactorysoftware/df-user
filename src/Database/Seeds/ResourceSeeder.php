<?php

namespace DreamFactory\Core\User\Database\Seeds;

use DreamFactory\Core\Database\Seeds\BaseModelSeeder;
use DreamFactory\Core\Models\SystemResource;
use DreamFactory\Core\User\Resources\System\User;

class ResourceSeeder extends BaseModelSeeder
{
    protected $modelClass = SystemResource::class;

    protected $records = [
        [
            'name'        => 'user',
            'label'       => 'User Management',
            'description' => 'Allows user management capability.',
            'class_name'  => User::class,
            'singleton'   => false,
            'read_only'   => false
        ]
    ];
}