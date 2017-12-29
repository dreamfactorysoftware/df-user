<?php

namespace DreamFactory\Core\User\Models;

use DreamFactory\Core\Components\AppRoleMapper;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Models\BaseServiceConfigModel;
use DreamFactory\Core\Models\EmailTemplate;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\Models\SingleRecordModel;
use DreamFactory\Core\Models\Role;
use ServiceManager;

class UserConfig extends BaseServiceConfigModel
{
    use SingleRecordModel;
    use AppRoleMapper {
        getConfigSchema as public getConfigSchemaMapper;
    }

    protected $table = 'user_config';

    /** @var array */
    protected static $altAuthFields = [
        'alt_auth_db_service_id',
        'alt_auth_table',
        'alt_auth_username_field',
        'alt_auth_password_field',
        'alt_auth_email_field',
        'alt_auth_other_fields',
        'alt_auth_filter'
    ];

    protected $fillable = [
        'service_id',
        'allow_open_registration',
        'open_reg_role_id',
        'open_reg_email_service_id',
        'open_reg_email_template_id',
        'invite_email_service_id',
        'invite_email_template_id',
        'password_email_service_id',
        'password_email_template_id',
        'alt_auth_db_service_id',
        'alt_auth_table',
        'alt_auth_username_field',
        'alt_auth_password_field',
        'alt_auth_email_field',
        'alt_auth_other_fields',
        'alt_auth_filter'
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
        'alt_auth_db_service_id'     => 'integer',
    ];

    protected $rules = [
        'alt_auth_table'          => 'required_unless:alt_auth_db_service_id,null,',
        'alt_auth_username_field' => 'required_unless:alt_auth_db_service_id,null,',
        'alt_auth_password_field' => 'required_unless:alt_auth_db_service_id,null,',
        'alt_auth_email_field'    => 'required_unless:alt_auth_db_service_id,null,',
    ];

    protected $validationMessages = [
        'required_unless' => 'The :attribute field is required unless Alt Auth DB Service is blank.'
    ];

    /**
     * {@inheritdoc}
     */
    public static function getConfigSchema()
    {
        $schema = static::getConfigSchemaMapper();
        $map = array_pop($schema);
        $map['label'] = 'Per App Open Reg Role';
        array_splice($schema, 2, 0, [$map]);
        $out = [];
        // If alternative is not enabled then remove all
        // related config options.
        if (!config('df.alternate_auth')) {
            foreach ($schema as $col) {
                if (!in_array(array_get($col, 'name'), static::$altAuthFields)) {
                    $out[] = $col;
                }
            }
        } else {
            $out = $schema;
        }

        return $out;
    }

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'open_reg_role_id':
                $roles = Role::whereIsActive(1)->get();
                $roleList = [
                    [
                        'label' => '',
                        'name'  => null
                    ]
                ];
                foreach ($roles as $role) {
                    $roleList[] = [
                        'label' => $role->name,
                        'name'  => $role->id
                    ];
                }
                $schema['type'] = 'picklist';
                $schema['values'] = $roleList;
                $schema['label'] = 'Default Open Reg Role';
                $schema['description'] = 'Select a role for self registered users.';
                break;
            case 'open_reg_email_service_id':
            case 'invite_email_service_id':
            case 'password_email_service_id':
                $label = substr($schema['label'], 0, strlen($schema['label']) - 11);
                $services = ServiceManager::getServiceListByGroup(ServiceTypeGroups::EMAIL, ['id', 'label'], true);
                $emailSvcList = [
                    [
                        'label' => '',
                        'name'  => null
                    ]
                ];
                foreach ($services as $service) {
                    $emailSvcList[] = ['label' => array_get($service, 'label'), 'name' => array_get($service, 'id')];
                }
                $schema['type'] = 'picklist';
                $schema['values'] = $emailSvcList;
                $schema['label'] = $label . ' Service';
                $schema['description'] =
                    'Select an Email service for sending out ' .
                    $label .
                    '.';
                break;
            case 'open_reg_email_template_id':
            case 'invite_email_template_id':
            case 'password_email_template_id':
                $label = substr($schema['label'], 0, strlen($schema['label']) - 11);
                $templates = EmailTemplate::get();
                $templateList = [
                    [
                        'label' => '',
                        'name'  => null
                    ]
                ];
                foreach ($templates as $template) {
                    $templateList[] = [
                        'label' => $template->name,
                        'name'  => $template->id
                    ];
                }
                $schema['type'] = 'picklist';
                $schema['values'] = $templateList;
                $schema['label'] = $label . ' Template';
                $schema['description'] = 'Select an Email template to use for ' .
                    $label .
                    '.';
                break;
            case 'alt_auth_db_service_id':
                $services = ServiceManager::getServiceListByGroup(ServiceTypeGroups::DATABASE, ['id', 'label'], true);
                $dbServiceList = [['label' => '', 'name' => null]];
                foreach ($services as $service) {
                    $dbServiceList[] = ['label' => array_get($service, 'label'), 'name' => array_get($service, 'id')];
                }
                $schema['type'] = 'picklist';
                $schema['values'] = $dbServiceList;
                $schema['label'] = 'Alt. Auth. DB Service';
                $schema['description'] =
                    'If you do not want to use the default DreamFactory user table for authentication ' .
                    'then you can pick a DB service for alternative authentication.';
                break;
            case 'alt_auth_table':
                $schema['label'] = 'Alt. Auth. Table';
                $schema['description'] = 'If you are using a db service for alternative authentication ' .
                    'then you must specify the table that will be used for authentication.';
                break;
            case 'alt_auth_username_field':
                $schema['label'] = 'Alt. Auth. Username field';
                $schema['description'] = 'If you are using a db service and table for alternative authentication ' .
                    'then you must specify the username field that will be checked for authentication.';
                break;
            case 'alt_auth_password_field':
                $schema['label'] = 'Alt. Auth. Password field';
                $schema['description'] = 'If you are using a db service and table for alternative authentication ' .
                    'then you must specify the password field that will be checked for authentication. ' .
                    'Currently <b>bcrypt</b> and <b>md5</b> hashes are supported for password.';
                break;
            case 'alt_auth_email_field':
                $schema['label'] = 'Alt. Auth. Email field';
                $schema['description'] = 'If you are using a db service and table for alternative authentication ' .
                    'then you must specify the email field that will be used by DreamFactory to retrieve ' .
                    'user\'s email address in order to uniquely identify this user within DreamFactory.';
                break;
            case 'alt_auth_other_fields':
                $schema['label'] = 'Alt. Auth. Other field(s)';
                $schema['description'] = 'If you are using a db service and table for alternative authentication ' .
                    'and your authentication process requires checking other fields in addition to username and ' .
                    'password then you can specify those field(s) here. Separate multiple fields by comma. ' .
                    'You can pass the values for these fields using request parameter or body.';
                break;
            case 'alt_auth_filter':
                $schema['label'] = 'Alt. Auth. Filter(s)';
                $schema['description'] = 'If you are using a db service and table for alternative authentication ' .
                    'and you like to limit authentication to a certain type/group of user ' .
                    '(example: all active users only) then you can specify the filter here. Filters can be ' .
                    'specified by field=value format. Multiple filters can be separated by comma and will be ' .
                    'used with AND operator. Example: field1=value1,field2=value2';
                break;
        }
    }
}