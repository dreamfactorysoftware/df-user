<?php
namespace DreamFactory\Core\User\Models;

use DreamFactory\Core\Contracts\ServiceConfigHandlerInterface;
use DreamFactory\Core\Models\BaseServiceConfigModel;
use DreamFactory\Core\Models\SingleRecordModel;

class UserConfig extends BaseServiceConfigModel implements ServiceConfigHandlerInterface
{
    use SingleRecordModel;

    protected $table = 'user_config';

    protected $fillable = [
        'service_id',
        'allow_open_registration',
        'open_reg_role_id',
        'open_reg_email_service_id',
        'open_reg_email_template_id',
        'invite_email_service_id',
        'invite_email_template_id',
        'password_email_service_id',
        'password_email_template_id'
    ];
}