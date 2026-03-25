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
        Schema::create('company_jobs', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->string('salary')->nullable();
            $table->string('job_type')->nullable(); 
            $table->json('benefits')->nullable(); 
            $table->integer('vacancy')->nullable(); 
            $table->json('responsivilities')->nullable(); 
            $table->json('requirement')->nullable(); 
            $table->string('location')->nullable();
            $table->string('year_of_experience')->nullable();
            $table->string('certification')->nullable();
            $table->string('education')->nullable();
            $table->date('dedline')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_jobs');
    }
};
