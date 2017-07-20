<?php

namespace DreamFactory\Core\User\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\User\Resources\Custom;
use DreamFactory\Core\User\Resources\Password;
use DreamFactory\Core\User\Resources\Profile;
use DreamFactory\Core\User\Resources\Register;
use DreamFactory\Core\User\Resources\Session;

class User extends BaseRestService
{
    protected static $resources = [
        Password::RESOURCE_NAME => [
            'name'       => Password::RESOURCE_NAME,
            'class_name' => Password::class,
            'label'      => 'Password'
        ],
        Profile::RESOURCE_NAME  => [
            'name'       => Profile::RESOURCE_NAME,
            'class_name' => Profile::class,
            'label'      => 'Profile'
        ],
        Register::RESOURCE_NAME => [
            'name'       => Register::RESOURCE_NAME,
            'class_name' => Register::class,
            'label'      => 'Register'
        ],
        Session::RESOURCE_NAME  => [
            'name'       => Session::RESOURCE_NAME,
            'class_name' => Session::class,
            'label'      => 'Session'
        ],
        Custom::RESOURCE_NAME   => [
            'name'       => Custom::RESOURCE_NAME,
            'class_name' => Custom::class,
            'label'      => 'Custom'
        ]
    ];

    /**
     * @var boolean Allow open registration
     */
    public $allowOpenRegistration = false;
    /**
     * @var integer|null Default role Id to be assigned to registered users
     */
    public $openRegRoleId;
    /**
     * @var integer|null Email service Id used for open registration
     */
    public $openRegEmailServiceId;
    /**
     * @var integer|null Email template Id used for open registration
     */
    public $openRegEmailTemplateId;
    /**
     * @var integer|null Email service Id used for user invite
     */
    public $inviteEmailServiceId;
    /**
     * @var integer|null Email template Id used for user invite
     */
    public $inviteEmailTemplateId;
    /**
     * @var integer|null Email service Id used for password reset
     */
    public $passwordEmailServiceId;
    /**
     * @var integer|null Email template Id used for password reset
     */
    public $passwordEmailTemplateId;

    /**
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        parent::__construct($settings);

        foreach ($this->config as $key => $value) {
            if (!property_exists($this, $key)) {
                // try camel cased
                $camel = camel_case($key);
                if (property_exists($this, $camel)) {
                    $this->{$camel} = $value;
                    continue;
                }
            } else {
                $this->{$key} = $value;
            }
        }
    }

    public function getResources($only_handlers = false)
    {
        return ($only_handlers) ? static::$resources : array_values(static::$resources);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessList()
    {
        $list = parent::getAccessList();
        $nameField = static::getResourceIdentifier();
        foreach ($this->getResources() as $resource) {
            $name = array_get($resource, $nameField);
            if (!empty($this->getPermissions())) {
                $list[] = $name . '/';
            }
        }

        return $list;
    }
}