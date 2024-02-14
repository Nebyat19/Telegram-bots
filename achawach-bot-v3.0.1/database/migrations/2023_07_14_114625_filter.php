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
      Schema::create('filters',function(Blueprint $table){
        $table->id();
        $table->string('user_id');
        $table->foreign('user_id')->references('id')->on('users');
        $table->string('gender')->default('F');
        $table->string('age')->default("18-22");
        $table->string('location')->default("Addis Ababa");
        $table->timestamps();
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filters');
    }
};
