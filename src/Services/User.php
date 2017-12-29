<?php

namespace DreamFactory\Core\User\Services;

use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\User\Components\AlternateAuth;
use DreamFactory\Core\User\Resources\Custom;
use DreamFactory\Core\User\Resources\Password;
use DreamFactory\Core\User\Resources\Profile;
use DreamFactory\Core\User\Resources\Register;
use DreamFactory\Core\User\Resources\Session;
use DreamFactory\Core\Exceptions\InternalServerErrorException;

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

    /**
     * Checks to see if the service handles alternative authentication
     *
     * @return bool
     */
    public function handlesAlternateAuth()
    {
        if (
            config('df.alternate_auth') === true &&
            !empty(array_get($this->config, 'alt_auth_db_service_id')) &&
            !empty(array_get($this->config, 'alt_auth_table')) &&
            !empty(array_get($this->config, 'alt_auth_username_field')) &&
            !empty(array_get($this->config, 'alt_auth_password_field')) &&
            !empty(array_get($this->config, 'alt_auth_email_field'))
        ) {
            return true;
        }

        return false;
    }

    /**
     * If the service handles alternative authentication then
     * this method will return the alternative authenticator
     *
     * @return \DreamFactory\Core\User\Components\AlternateAuth
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function getAltAuthenticator()
    {
        if ($this->handlesAlternateAuth()) {
            $authenticator = new AlternateAuth(
                array_get($this->config, 'alt_auth_db_service_id'),
                array_get($this->config, 'alt_auth_table'),
                array_get($this->config, 'alt_auth_username_field'),
                array_get($this->config, 'alt_auth_password_field'),
                array_get($this->config, 'alt_auth_email_field')
            );
            $authenticator->setOtherFields(array_get($this->config, 'alt_auth_other_fields'));
            $authenticator->setFilters(array_get($this->config, 'alt_auth_filter'));

            return $authenticator;
        } else {
            throw new InternalServerErrorException('No alternate authentication is configured for this service.');
        }
    }
}