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
        //Create user_config table.
        Schema::create(
            'user_config',
            function (Blueprint $t){
                $t->integer('service_id')->unsigned()->primary();
                $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                $t->boolean('allow_open_registration')->default(0);
                $t->integer('open_reg_role_id')->unsigned()->nullable();
                $t->integer('open_reg_email_service_id')->unsigned()->nullable();
                $t->integer('open_reg_email_template_id')->unsigned()->nullable();
                $t->integer('invite_email_service_id')->unsigned()->nullable();
                $t->integer('invite_email_template_id')->unsigned()->nullable();
                $t->integer('password_email_service_id')->unsigned()->nullable();
                $t->integer('password_email_template_id')->unsigned()->nullable();
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
