<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserCustomTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //create user_custom
        Schema::create(
            'user_custom',
            function (Blueprint $t){
                $t->increments('id');
                $t->integer('user_id')->unsigned();
                $t->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
                $t->string('name');
                $t->longText('value')->nullable();
                $t->timestamp('created_date')->useCurrent();
                $t->timestamp('last_modified_date')->useCurrent();
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
        //drop user_custom
        Schema::dropIfExists('user_custom');
    }

}
