<?php
namespace DreamFactory\Core\User\Models;

use DreamFactory\Core\Contracts\ServiceConfigHandlerInterface;
use DreamFactory\Core\Models\BaseServiceConfigModel;
use DreamFactory\Core\Models\EmailTemplate;
use DreamFactory\Core\Models\Service;
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
        parent::prepareConfigSchemaField($schema);

        $roleList = [
            [
                'label'=>'',
                'name'=>null
            ]
        ];
        $emailSvcList = [
            [
                'label'=>'',
                'name'=>null
            ]
        ];
        $templateList = [
            [
                'label'=>'',
                'name'=>null
            ]
        ];
        switch ($schema['name']) {
            case 'open_reg_role_id':
                $roles = Role::whereIsActive(1)->get();
                foreach ($roles as $role) {
                    $roleList[] = [
                        'label' => $role->name,
                        'name'  => $role->id
                    ];
                }
                $schema['type'] = 'picklist';
                $schema['values'] = $roleList;
                $schema['label'] = 'Open Reg Role';
                $schema['description'] = 'Select a role for self registered users.';
                break;
            case 'open_reg_email_service_id':
            case 'invite_email_service_id':
            case 'password_email_service_id':
                $label = substr($schema['label'], 0, strlen($schema['label']) - 11);
                $services = Service::whereIsActive(1)
                    ->whereIn('type', ['aws_ses', 'smtp_email', 'mailgun_email', 'mandrill_email', 'local_email'])
                    ->get();
                foreach ($services as $service) {
                    $emailSvcList[] = [
                        'label' => $service->label,
                        'name'  => $service->id
                    ];
                }
                $schema['type'] = 'picklist';
                $schema['values'] = $emailSvcList;
                $schema['label'] = $label.' Service';
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
                foreach ($templates as $template) {
                    $templateList[] = [
                        'label' => $template->name,
                        'name'  => $template->id
                    ];
                }
                $schema['type'] = 'picklist';
                $schema['values'] = $templateList;
                $schema['label'] = $label.' Template';
                $schema['description'] = 'Select an Email template to use for ' .
                    $label .
                    '.';
                break;
        }
    }
}