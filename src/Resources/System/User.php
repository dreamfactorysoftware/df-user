<?php

namespace DreamFactory\Core\User\Resources\System;

use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Models\NonAdminUser;
use DreamFactory\Core\Resources\System\BaseUserResource;

class User extends BaseUserResource
{
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

    protected function getApiDocPaths()
    {
        $baseDoc = parent::getApiDocPaths();

        $inviteOption = ApiOptions::documentOption(ApiOptions::SEND_INVITE);

        $post = array_get($baseDoc, '/user.post.parameters', []);
        $post[] = $inviteOption;
        $patch = array_get($baseDoc, '/user.patch.parameters', []);
        $patch[] = $inviteOption;

        array_set($baseDoc, '/user.post.parameters', $post);
        array_set($baseDoc, '/user.patch.parameters', $patch);

        return $baseDoc;
    }
}