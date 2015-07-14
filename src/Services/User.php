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
use DreamFactory\Core\Utility\ApiDocUtilities;

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

    public function getResources()
    {
        return $this->resources;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiDocInfo()
    {
        $base = parent::getApiDocInfo();

        $apis = [
            [
                'path'        => '/' . $this->name,
                'description' => 'Operations available for the user management service.',
                'operations'  => [
                    [
                        'method'           => 'GET',
                        'summary'          => 'getResourceList() - List all resource names.',
                        'nickname'         => 'getResourceList',
                        'notes'            => 'List the resource names available in this service.',
                        'type'             => 'ComponentList',
                        'event_name'       => [$this->name . '.list'],
                        'parameters'       => [
                            [
                                'name'          => 'refresh',
                                'description'   => 'Refresh any cached copy of the resource list.',
                                'allowMultiple' => false,
                                'type'          => 'boolean',
                                'paramType'     => 'query',
                                'required'      => false,
                            ],
                        ],
                        'responseMessages' => ApiDocUtilities::getCommonResponses([400, 401, 500]),
                    ],
                    [
                        'method'           => 'GET',
                        'summary'          => 'getResources() - List all resources.',
                        'nickname'         => 'getResources',
                        'notes'            => 'List the resources available on this service. ',
                        'type'             => 'Resources',
                        'event_name'       => [$this->name . '.list'],
                        'parameters'       => [
                            [
                                'name'          => 'include_properties',
                                'description'   => 'Return other properties available for each resource.',
                                'allowMultiple' => false,
                                'type'          => 'boolean',
                                'paramType'     => 'query',
                                'required'      => true,
                                'default'       => true,
                            ],
                            [
                                'name'          => 'refresh',
                                'description'   => 'Refresh any cached copy of the resource list.',
                                'allowMultiple' => false,
                                'type'          => 'boolean',
                                'paramType'     => 'query',
                                'required'      => false,
                            ],
                        ],
                        'responseMessages' => ApiDocUtilities::getCommonResponses([400, 401, 500]),
                    ],
                    [
                        'method'           => 'GET',
                        'summary'          => 'getAccessComponents() - List all role accessible components.',
                        'nickname'         => 'getAccessComponents',
                        'notes'            => 'List the names of all the role accessible components.',
                        'type'             => 'ComponentList',
                        'event_name'       => [$this->name . '.list'],
                        'parameters'       => [
                            [
                                'name'          => 'as_access_components',
                                'description'   => 'Return the names of all the accessible components.',
                                'allowMultiple' => false,
                                'type'          => 'boolean',
                                'paramType'     => 'query',
                                'required'      => true,
                                'default'       => true,
                            ],
                            [
                                'name'          => 'refresh',
                                'description'   => 'Refresh any cached copy of the resource list.',
                                'allowMultiple' => false,
                                'type'          => 'boolean',
                                'paramType'     => 'query',
                                'required'      => false,
                            ],
                        ],
                        'responseMessages' => ApiDocUtilities::getCommonResponses([400, 401, 500]),
                    ],
                ],
            ],
        ];

        $models = [];

        foreach ($this->getResources() as $resourceInfo) {
            $className = ArrayUtils::get($resourceInfo, 'class_name');

            if (!class_exists($className)) {
                throw new InternalServerErrorException('Service configuration class name lookup failed for resource ' .
                    $this->resourcePath);
            }

            /** @var BaseRestResource $resource */
            $resource = $this->instantiateResource($className, $resourceInfo);

            $name = ArrayUtils::get($resourceInfo, 'name', '') . '/';
            $_access = $this->getPermissions($name);
            if (!empty($_access)) {
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