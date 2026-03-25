<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applicants', function (Blueprint $table) {
            // Drop old employee_id foreign key
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');

            // Add new user_id foreign key
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('job_applicants', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
        });
    }
};
