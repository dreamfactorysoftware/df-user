<?php
namespace DreamFactory\Core\User\Database\Seeds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(EmailTemplateSeeder::class);
        $this->call(ServiceTypeSeeder::class);
        $this->call(EmailServiceSeeder::class);
        $this->call(UserServiceSeeder::class);
        $this->call(ResourceSeeder::class);
    }
}