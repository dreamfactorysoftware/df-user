<?php
/**
 * This file is part of the DreamFactory Rave(tm)
 *
 * DreamFactory Rave(tm) <http://github.com/dreamfactorysoftware/rave>
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

namespace DreamFactory\Rave\User\Resources;

use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Rave\Resources\BaseRestResource;

class Profile extends BaseRestResource
{
    const RESOURCE_NAME = 'profile';

    /**
     * @param array $settings
     */
    public function __construct( $settings = [ ] )
    {
        parent::__construct( $settings );
        $this->verbAliases = [
            Verbs::PUT   => Verbs::POST,
            Verbs::MERGE => Verbs::POST,
            Verbs::PATCH => Verbs::POST
        ];
    }

    /**
     * @return array
     */
    protected function handleGET()
    {
        $user = \Auth::getUser();

        $data = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'security_question' => $user->security_question
        ];

        return $data;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function handlePOST()
    {
        $data = $this->getPayloadData();

        $user = \Auth::getUser();

        $user->update($data);

        return ['success' => true];
    }
}