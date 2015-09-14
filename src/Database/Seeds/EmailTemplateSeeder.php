<?php

namespace DreamFactory\Core\User\Database\Seeds;

use DreamFactory\Core\Models\EmailTemplate;
use DreamFactory\Core\Database\Seeds\BaseModelSeeder;

class EmailTemplateSeeder extends BaseModelSeeder
{
    protected $modelClass = EmailTemplate::class;

    protected $records = [
        [
            'name'        => 'User Invite Default',
            'description' => 'Email sent to invite new users to your DreamFactory instance.',
            'subject'     => '[DF] New User Invitation',
            'body_html'   => 'emails.invite',
            'from_name'   => 'DO NOT REPLY',
            'from_email'  => 'no-reply@dreamfactory.com'
        ],
        [
            'name'        => 'User Registration Default',
            'description' => 'Email sent to new users to complete registration.',
            'subject'     => '[DF] Registration Confirmation',
            'body_html'   => 'emails.register',
            'from_name'   => 'DO NOT REPLY',
            'from_email'  => 'no-reply@dreamfactory.com'
        ],
        [
            'name'        => 'Password Reset Default',
            'description' => 'Email sent to users following a request to reset their password.',
            'subject'     => '[DF] Password Reset',
            'body_html'   => 'emails.password',
            'from_name'   => 'DO NOT REPLY',
            'from_email'  => 'no-reply@dreamfactory.com'
        ]
    ];
}