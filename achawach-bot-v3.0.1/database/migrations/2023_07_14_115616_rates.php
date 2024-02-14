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
        Schema::create('rates',function(Blueprint $table){
            $table->id();
            $table->string('rater_id');
            $table->foreign('rater_id')->references('id')->on('users');
            $table->string('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('rate');/**** 1, 0, -1 ***//** like, dislike, report */
            $table->string('report')->nullable();
            $table->timestamps();
          });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('rates');
    }
};
