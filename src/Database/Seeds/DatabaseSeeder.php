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

namespace DreamFactory\Rave\User\Database\Seeds;

use DreamFactory\Rave\Models\Service;
use DreamFactory\Rave\Models\SystemResource;
use Illuminate\Database\Seeder;
use DreamFactory\Rave\Models\ServiceType;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        if ( !ServiceType::whereName( 'user_mgmt' )->exists() )
        {
            // Add the service type
            ServiceType::create(
                [
                    'name'           => 'user_mgmt',
                    'class_name'     => "DreamFactory\\Rave\\User\\Services\\User",
                    'label'          => 'User service',
                    'description'    => 'User service to allow user management.',
                    'group'          => 'users',
                    'singleton'      => 1
                ]
            );
            $this->command->info( 'User Management service type seeded!' );
        }

        if ( !Service::whereName( 'user' )->exists() )
        {
            Service::create(
                [
                    'name'        => 'user',
                    'label'       => 'User Management',
                    'description' => 'Service for managing system users.',
                    'is_active'   => 1,
                    'type'        => 'user_mgmt',
                    'mutable'     => 0,
                    'deletable'   => 0
                ]
            );
            $this->command->info( 'User Management service seeded!' );
        }

        if(!SystemResource::whereName('user')->exists())
        {
            SystemResource::create(
                [
                    'name' => 'user',
                    'label' => 'User Management',
                    'description' => 'Allows user management capability.',
                    'class_name' => "DreamFactory\\Rave\\User\\Resources\\System\\User",
                    'model_name' => "DreamFactory\\Rave\\Models\\User",
                    'singleton' => 0,
                    'read_only' => 0
                ]
            );
            $this->command->info('User management resource successfully seeded!');
        }
    }
}