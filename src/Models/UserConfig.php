<?php
namespace DreamFactory\Core\User\Models;

use DreamFactory\Core\Contracts\ServiceConfigHandlerInterface;
use DreamFactory\Core\Models\BaseServiceConfigModel;
use DreamFactory\Core\Models\SingleRecordModel;
use DreamFactory\Core\Models\Role;

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

    protected $casts = [
        'allow_open_registration'    => 'boolean',
        'service_id'                 => 'integer',
        'open_reg_role_id'           => 'integer',
        'open_reg_email_service_id'  => 'integer',
        'open_reg_email_template_id' => 'integer',
        'invite_email_service_id'    => 'integer',
        'invite_email_template_id'   => 'integer',
        'password_email_service_id'  => 'integer',
        'password_email_template_id' => 'integer',
    ];

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        $roles = Role::whereIsActive(1)->get();
        $roleList = [];

        foreach($roles as $role){
            $roleList[] = [
                'label' => $role->name,
                'name'  => $role->id
            ];
        }

        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'open_reg_role_id':
                $schema['type'] = 'picklist';
                $schema['values'] = $roleList;
                $schema['description'] = 'Select a role for self registered users.';
                break;
        }
    }
}