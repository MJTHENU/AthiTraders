<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanDuesTable extends Migration
{
    public function up()
    {
        Schema::create('loan_due', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id'); // Update to unsignedBigInteger
            $table->unsignedBigInteger('user_id'); // Update to unsignedBigInteger
            $table->bigInteger('due_amount');
            $table->date('due_date');
            $table->date('paid_on')->nullable();
            $table->string('collection_by'); // Change to unsignedBigInteger to match employees' id
            $table->rememberToken();
            $table->timestamps();

            // Foreign key constraints
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('collection_by')->references('id')->on('users')->onDelete('cascade'); // Ensure 'id' is the primary key on 'employees'
            // $table->foreign('loan_id')->references('id')->on('loan')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_due');
    }
}
