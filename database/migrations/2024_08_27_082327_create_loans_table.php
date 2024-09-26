<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoansTable extends Migration
{
    public function up()
    {
        Schema::create('loan', function (Blueprint $table) {
            $table->id();
            $table->string('loan_id');
            $table->unsignedBigInteger('user_id');
            $table->string('employee_id');
            $table->unsignedBigInteger('category_id');
            $table->bigInteger('loan_amount');
            $table->enum('loan_category', ['weekly', 'daily', 'monthly']);
            $table->date('loan_date');
            $table->longtext('image')->nullable();
            $table->enum('status', ['pending', 'inprogress', 'completed', 'cancelled']);
            $table->datetime('added_on')->useCurrent();
            $table->datetime('updated_on')->useCurrentOnUpdate()->nullable();

            $table->date('loan_closed_date')->nullable();

            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('loan_category')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan');
    }
}
