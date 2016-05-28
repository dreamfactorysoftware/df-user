<?php

namespace DreamFactory\Core\User\Models\Seeds;

use DreamFactory\Core\Models\Seeds\BaseModelSeeder;
use DreamFactory\Core\Models\Service;

class EmailServiceSeeder extends BaseModelSeeder
{
    protected $modelClass = Service::class;

    protected $records = [
        [
            'name'        => 'email',
            'label'       => 'Local Email Service',
            'description' => 'Email service used for sending user invites and/or password reset confirmation.',
            'is_active'   => true,
            'type'        => 'local_email',
            'mutable'     => true,
            'deletable'   => true
        ]
    ];
}