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
        Schema::table('companies', function (Blueprint $table) {
            $table->longText('head_office_location')->nullable()->after('bio');
            $table->string('separate_email')->nullable()->after('head_office_location');
            $table->string('abn')->nullable()->after('separate_email');
            $table->string('industry_type')->nullable()->after('abn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'head_office_location',
                'separate_email',
                'abn',
                'industry_type'
            ]);
        });
    }
};
