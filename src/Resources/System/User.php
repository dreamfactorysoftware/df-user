<?php
namespace DreamFactory\Core\User\Resources\System;

use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Core\Models\BaseSystemModel;
use DreamFactory\Core\Resources\System\BaseSystemResource;

class User extends BaseSystemResource
{
    /**
     * {@inheritdoc}
     */
    protected function getSelectionCriteria()
    {
        $criteria = parent::getSelectionCriteria();

        $condition = ArrayUtils::get($criteria, 'condition');

        if (!empty($condition)) {
            $condition .= ' AND is_sys_admin = "0" ';
        } else {
            $condition = ' is_sys_admin = "0" ';
        }

        ArrayUtils::set($criteria, 'condition', $condition);

        return $criteria;
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveById($id, array $related = [])
    {
        /** @var BaseSystemModel $modelClass */
        $modelClass = $this->model;
        $criteria = $this->getSelectionCriteria();
        $fields = ArrayUtils::get($criteria, 'select');
        $model = $modelClass::whereIsSysAdmin(0)->with($related)->find($id, $fields);

        $data = (!empty($model)) ? $model->toArray() : [];

        return $data;
    }
}