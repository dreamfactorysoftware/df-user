<?php
namespace DreamFactory\Core\User\Services;

use DreamFactory\Core\User\Models\UserCustom;
use DreamFactory\Core\User\Resources\Custom;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Resources\BaseRestResource;
use DreamFactory\Core\User\Resources\Password;
use DreamFactory\Core\User\Resources\Profile;
use DreamFactory\Core\User\Resources\Register;
use DreamFactory\Core\User\Resources\Session;

class User extends BaseRestService
{
    protected $resources = [
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
            'model_name' => UserCustom::class,
            'label'      => 'Custom'
        ]
    ];

    public function getResources($only_handlers = false)
    {
        return ($only_handlers) ? $this->resources : array_values($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessList()
    {
        $list = parent::getAccessList();
        $nameField = $this->getResourceIdentifier();
        foreach ($this->getResources() as $resource)
        {
            $name = ArrayUtils::get($resource, $nameField);
            if (!empty($this->getPermissions())) {
                $list[] = $name . '/';
                $list[] = $name . '/*';
            }
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiDocInfo()
    {
        $base = parent::getApiDocInfo();

        $apis = [];
        $models = [];

        foreach ($this->getResources(true) as $resourceInfo) {
            $className = ArrayUtils::get($resourceInfo, 'class_name');

            if (!class_exists($className)) {
                throw new InternalServerErrorException('Service configuration class name lookup failed for resource ' .
                    $this->resourcePath);
            }

            /** @var BaseRestResource $resource */
            $resource = $this->instantiateResource($className, $resourceInfo);

            $name = ArrayUtils::get($resourceInfo, 'name', '') . '/';
            $access = $this->getPermissions($name);
            if (!empty($access)) {
                $results = $resource->getApiDocInfo();
                if (isset($results, $results['apis'])) {
                    $apis = array_merge($apis, $results['apis']);
                }
                if (isset($results, $results['models'])) {
                    $models = array_merge($models, $results['models']);
                }
            }
        }

        $base['apis'] = array_merge($base['apis'], $apis);
        $base['models'] = array_merge($base['models'], $models);

        return $base;
    }
}