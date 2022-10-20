<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldToPostGroupDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('post_group_data', function (Blueprint $table) {
            $table->integer('group_id');
            $table->index([ 'user_id', 'group_id', 'sockpuppet_id' ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('post_group_data', function (Blueprint $table) {
            //
        });
    }
}
