<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chats',function(Blueprint $table){
            $table->id();
            $table->string('user_id_from');
            $table->foreign('user_id_from')->references('id')->on('users');
            $table->string('user_id_to');
            $table->foreign('user_id_to')->references('id')->on('users');
            $table->string('status')->nullable(); /**** 1 0 -1 active, stoped, blocked */ 
            $table->timestamps();
          });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
