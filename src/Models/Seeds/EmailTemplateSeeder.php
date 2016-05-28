<?php

namespace DreamFactory\Core\User\Models\Seeds;

use DreamFactory\Core\Models\EmailTemplate;
use DreamFactory\Core\Models\Seeds\BaseModelSeeder;

class EmailTemplateSeeder extends BaseModelSeeder
{
    protected $modelClass = EmailTemplate::class;

    protected $records = [
        [
            'name'        => 'User Invite Default',
            'description' => 'Email sent to invite new users to your DreamFactory instance.',
            'subject'     => '[DF] New User Invitation',
            'body_html'   => '<div style="padding: 10px;">
                                <p>
                                Hi {first_name},
                                </p>
                                <p>
                                    You have been invited to the DreamFactory Instance of {instance_name}. Go to the following url, enter the code below, and set
                                    your password to confirm your account.
                                    <br/>
                                    <br/>
                                    {link}
                                    <br/>
                                    <br/>
                                    Confirmation Code: {confirm_code}<br/>
                                </p>
                                <p>
                                    <cite>-- The Dream Team</cite>
                                </p>
                              </div>',
            'from_name'   => 'DO NOT REPLY',
            'from_email'  => 'no-reply@dreamfactory.com'
        ],
        [
            'name'        => 'User Registration Default',
            'description' => 'Email sent to new users to complete registration.',
            'subject'     => '[DF] Registration Confirmation',
            'body_html'   => '<div style="padding: 10px;">
                                <p>
                                    Hi {first_name},
                                </p>
                                <p>
                                    You have registered an user account on the DreamFactory instance of {instance_name}. Go to the following url, enter the
                                    code below, and set your password to confirm your account.
                                    <br/>
                                    <br/>
                                    {link}
                                    <br/>
                                    <br/>
                                    Confirmation Code: {confirm_code}
                                    <br/>
                                </p>
                                <p>
                                    <cite>-- The Dream Team</cite>
                                </p>
                            </div>',
            'from_name'   => 'DO NOT REPLY',
            'from_email'  => 'no-reply@dreamfactory.com'
        ],
        [
            'name'        => 'Password Reset Default',
            'description' => 'Email sent to users following a request to reset their password.',
            'subject'     => '[DF] Password Reset',
            'body_html'   => '<div style="padding: 10px;">
                                <p>
                                    Hi {first_name},
                                </p>
                                <p>
                                    You have requested to reset your password. Go to the following url, enter the code below, and set your new password.
                                    <br>
                                    <br>
                                    {link}
                                    <br>
                                    <br>
                                    Confirmation Code: {confirm_code}
                                </p>
                                <p>
                                    <cite>-- The Dream Team</cite>
                                </p>
                            </div>',
            'from_name'   => 'DO NOT REPLY',
            'from_email'  => 'no-reply@dreamfactory.com'
        ]
    ];
}