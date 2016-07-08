<?php
namespace DreamFactory\Core\User\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\User\Resources\Custom;
use DreamFactory\Core\User\Resources\Password;
use DreamFactory\Core\User\Resources\Profile;
use DreamFactory\Core\User\Resources\Register;
use DreamFactory\Core\User\Resources\Session;
use DreamFactory\Core\Utility\Session as SessionUtility;

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
        foreach ($this->getResources() as $resource)
        {
            $name = array_get($resource, $nameField);
            if (!empty($this->getPermissions())) {
                $list[] = $name . '/';
            }
        }

        return $list;
    }

    public function getApiDocInfo()
    {
        $base = parent::getApiDocInfo();

        $apis = [];
        $models = [];
        foreach (static::$resources as $resourceInfo) {
            $resourceClass = array_get($resourceInfo, 'class_name');

            if (!class_exists($resourceClass)) {
                throw new InternalServerErrorException('Service configuration class name lookup failed for resource ' .
                    $resourceClass);
            }

            $resourceName = array_get($resourceInfo, static::RESOURCE_IDENTIFIER);
            if (SessionUtility::checkForAnyServicePermissions($this->name, $resourceName)) {
                $results = $resourceClass::getApiDocInfo($this->name, $resourceInfo);
                if (isset($results, $results['paths'])) {
                    $apis = array_merge($apis, $results['paths']);
                }
                if (isset($results, $results['definitions'])) {
                    $models = array_merge($models, $results['definitions']);
                }
            }
        }

        $base['paths'] = array_merge($base['paths'], $apis);
        $base['definitions'] = array_merge($base['definitions'], $models);

        return $base;
    }
}