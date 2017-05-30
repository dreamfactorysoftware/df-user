<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserConfigTable extends Migration
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

        //Create user_config table.
        Schema::create(
            'user_config',
            function (Blueprint $t) use ($onDelete) {
                $t->integer('service_id')->unsigned()->primary();
                $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                $t->boolean('allow_open_registration')->default(0);
                $t->integer('open_reg_role_id')->unsigned()->nullable();
                $t->foreign('open_reg_role_id')->references('id')->on('role')->onDelete('set null');
                $t->integer('open_reg_email_service_id')->unsigned()->nullable();
                $t->foreign('open_reg_email_service_id')->references('id')->on('service')->onDelete($onDelete);
                $t->integer('open_reg_email_template_id')->unsigned()->nullable();
                $t->foreign('open_reg_email_template_id')->references('id')->on('email_template')->onDelete($onDelete);
                $t->integer('invite_email_service_id')->unsigned()->nullable();
                $t->foreign('invite_email_service_id')->references('id')->on('service')->onDelete($onDelete);
                $t->integer('invite_email_template_id')->unsigned()->nullable();
                $t->foreign('invite_email_template_id')->references('id')->on('email_template')->onDelete($onDelete);
                $t->integer('password_email_service_id')->unsigned()->nullable();
                $t->foreign('password_email_service_id')->references('id')->on('service')->onDelete($onDelete);
                $t->integer('password_email_template_id')->unsigned()->nullable();
                $t->foreign('password_email_template_id')->references('id')->on('email_template')->onDelete($onDelete);
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Drop user_config table.
        Schema::dropIfExists('user_config');
    }
}
