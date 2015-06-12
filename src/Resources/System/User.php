<?php
/**
 * This file is part of the DreamFactory(tm)
 *
 * DreamFactory(tm) <http://github.com/dreamfactorysoftware/rave>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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

        $condition = ArrayUtils::get( $criteria, 'condition' );

        if ( !empty( $condition ) )
        {
            $condition .= ' AND is_sys_admin = "0" ';
        }
        else
        {
            $condition = ' is_sys_admin = "0" ';
        }

        ArrayUtils::set( $criteria, 'condition', $condition );

        return $criteria;
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveById( $id, array $related = [ ] )
    {
        /** @var BaseSystemModel $modelClass */
        $modelClass = $this->model;
        $criteria = $this->getSelectionCriteria();
        $fields = ArrayUtils::get( $criteria, 'select' );
        $model = $modelClass::whereIsSysAdmin( 0 )->with( $related )->find( $id, $fields );

        $data = ( !empty( $model ) ) ? $model->toArray() : [ ];

        return $data;
    }
}