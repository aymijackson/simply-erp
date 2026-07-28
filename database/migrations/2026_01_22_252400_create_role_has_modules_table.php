<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleHasModulesTable extends Migration
{
    public function up()
    {
        Schema::create('role_has_modules', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('module_id');
        
            $table->primary(['role_id', 'module_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('role_has_modules');
    }
}
