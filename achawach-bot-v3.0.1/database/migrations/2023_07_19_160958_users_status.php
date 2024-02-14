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
        Schema::table('users', function (Blueprint $table) {
          
            //refral, decline,bonus, withdraw
            $table->string('refral')->default('0');
            $table->string('decline')->default('0');
            $table->string('bonus')->default('0');
            $table->string('withdraw')->default('0');

        }   );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
