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
            'subject'     => 'Invitation',
            'body_html'   => 'Hi {first_name},<br/>
                            You have been invited to {df.name}. Go to the following url, enter the code below, and set your password to confirm your account.<br/>
                            <br/>
                            {df.confirm_invite_url}<br/>
                            <br/>
                            Confirmation Code: {confirm_code}<br/>
                            <br/>
                            Thanks,<br/>
                            {from_name}',
            'from_name'   => 'DreamFactory',
            'from_email'  => 'no-reply@dreamfactory.com'
        ],
        [
            'name'        => 'User Registration Default',
            'description' => 'Email sent to new users to complete registration.',
            'subject'     => 'Registration Confirmation',
            'body_html'   => 'Hi {first_name},<br/>
                            You have registered as a {df.name} user. Go to the following url, enter the code below, and set your password to confirm your account.<br/>
                            <br/>
                            {df.confirm_register_url}<br/>
                            <br/>
                            Confirmation Code: {confirm_code}<br/>
                            <br/>
                            Thanks,<br/>
                            {from_name}',
            'from_name'   => 'DreamFactory',
            'from_email'  => 'no-reply@dreamfactory.com'
        ],
        [
            'name'        => 'Password Reset Default',
            'description' => 'Email sent to users following a request to reset their password.',
            'subject'     => 'Password Reset',
            'body_html'   => 'Hi {first_name},<br/>
                            <br/>
                            You have requested to reset your password. Go to the following url, enter the code below, and set your new password.<br/>
                            <br/>
                            {df.confirm_reset_url}<br/>
                            <br/>
                            Confirmation Code: {confirm_code}<br/>
                            <br/>
                            Thanks,<br/>
                            {from_name}',
            'from_name'   => 'DreamFactory',
            'from_email'  => 'no-reply@dreamfactory.com'
        ]
    ];
}