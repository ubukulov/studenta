<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableUserProfile extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_profile', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('university_id');
            $table->unsignedBigInteger('speciality_id');
            $table->integer('start_year');
            $table->integer('end_year');
            $table->string('identity_card')->nullable();
            $table->string('student_card')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('city_id')
                ->references('id')
                ->on('cities')
                ->onDelete('cascade');

            $table->foreign('university_id')
                ->references('id')
                ->on('universities')
                ->onDelete('cascade');

            $table->foreign('speciality_id')
                ->references('id')
                ->on('specialities')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_profile');
    }
}
