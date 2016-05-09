<?php
namespace DreamFactory\Core\User\Models\Seeds;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(EmailTemplateSeeder::class);
        $this->call(EmailServiceSeeder::class);
        $this->call(UserServiceSeeder::class);
    }
}