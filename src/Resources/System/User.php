<?php

namespace DreamFactory\Core\User\Resources\System;

use DreamFactory\Core\Components\Invitable;
use DreamFactory\Core\Contracts\ServiceResponseInterface;
use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Models\NonAdminUser;
use DreamFactory\Core\Resources\System\BaseSystemResource;
use DreamFactory\Core\Utility\ResponseFactory;

class User extends BaseSystemResource
{
    use Invitable;
    /**
     * @var string DreamFactory\Core\Models\BaseSystemModel Model Class name.
     */
    protected static $model = NonAdminUser::class;

    /**
     * {@inheritdoc}
     */
    protected function getSelectionCriteria()
    {
        $criteria = parent::getSelectionCriteria();

        $condition = array_get($criteria, 'condition');

        if (!empty($condition)) {
            $condition = "($condition) AND is_sys_admin = '0'";
        } else {
            $condition = " is_sys_admin = '0'";
        }

        $criteria['condition'] = $condition;

        return $criteria;
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePOST()
    {
        $response = parent::handlePOST();
        if ($this->request->getParameterAsBool('send_invite')) {
            $this->handleInvitation($response, true);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePATCH()
    {
        $response = parent::handlePATCH();
        if ($this->request->getParameterAsBool('send_invite')) {
            if (!$response instanceof ServiceResponseInterface) {
                $response = ResponseFactory::create($response);
            }
            $this->handleInvitation($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePUT()
    {
        $response = parent::handlePUT();
        if ($this->request->getParameterAsBool('send_invite')) {
            $this->handleInvitation($response);
        }

        return $response;
    }

    /** {@inheritdoc} */
    public static function getApiDocInfo($service, array $resource = [])
    {
        $baseDoc = parent::getApiDocInfo($service, $resource);

        $inviteOption = ApiOptions::documentOption(ApiOptions::SEND_INVITE);

        $post = array_get($baseDoc, 'paths./system/user.post.parameters', []);
        $post[] = $inviteOption;
        $patch = array_get($baseDoc, 'paths./system/user.patch.parameters', []);
        $patch[] = $inviteOption;

        array_set($baseDoc, 'paths./system/user.post.parameters', $post);
        array_set($baseDoc, 'paths./system/user.patch.parameters', $patch);

        return $baseDoc;
    }
}