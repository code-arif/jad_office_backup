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
        Schema::table('company_jobs', function (Blueprint $table) {
            //  $table->string('employment_type')->nullable()->after('education');
            //  $table->string('vacency')->nullable()->after('employment_type');
            //  $table->string('requirement')->nullable()->after('responsivilities');
            //  $table->string('benifits')->nullable()->after('requirement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_jobs', function (Blueprint $table) {
             $table->dropColumn([
                // 'vacency',
                // 'responsivilities',
                // 'requirement',
                // 'benifits',
                // 'employment_type'
            ]);
        });
    }
};
