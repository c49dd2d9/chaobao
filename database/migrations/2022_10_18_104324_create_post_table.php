<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id');
            $table->integer('sockpuppet_id');
            $table->string('platform');
            $table->string('content', 1000);
            $table->string('image_url', 1000);
            $table->string('likes', 1000);
            $table->boolean('is_top');
            $table->boolean('is_delete');
            $table->index(['sockpuppet_id', 'is_delete', 'is_top']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
