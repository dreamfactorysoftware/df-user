<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUserConfigTableForExternalAuthSupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();
        // Even though we take care of this scenario in the code,
        // SQL Server does not allow potential cascading loops,
        // so set the default no action and clear out created/modified by another user when deleting a user.

        $onDelete = (('sqlsrv' === $driver) ? 'no action' : 'set null');

        if (!Schema::hasColumn('user_config', 'alt_auth_db_service_id')) {
            Schema::table('user_config', function (Blueprint $t) use ($onDelete){
                $t->integer('alt_auth_db_service_id')->unsigned()->nullable();
                $t->foreign('alt_auth_db_service_id')->references('id')->on('service')->onDelete($onDelete);
                $t->string('alt_auth_table')->nullable();
                $t->string('alt_auth_username_field')->nullable();
                $t->string('alt_auth_password_field')->nullable();
                $t->string('alt_auth_email_field')->nullable();
                $t->string('alt_auth_other_fields')->nullable();
                $t->string('alt_auth_filter')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::getDriverName() !== 'sqlite' && Schema::hasColumn('user_config', 'alt_auth_db_service_id')) {
            Schema::table('user_config', function (Blueprint $t) {
                $t->dropForeign(['alt_auth_db_service_id']);

            });
        }
        if (Schema::hasColumn('user_config', 'alt_auth_db_service_id')) {
            Schema::table('user_config', function (Blueprint $t) {
                $t->dropColumn([
                    'alt_auth_db_service_id',
                    'alt_auth_table',
                    'alt_auth_username_field',
                    'alt_auth_password_field',
                    'alt_auth_email_field',
                    'alt_auth_other_fields',
                    'alt_auth_filter',
                ]);
            });
        }
    }
}
