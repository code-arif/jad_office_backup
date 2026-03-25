<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->string('f_name')->nullable()->after('id');
            $table->string('l_name')->nullable()->after('f_name');

            $table->string('provider')->nullable()->after('password');
            $table->string('provider_id')->nullable()->after('provider');

            $table->string('phone')->nullable()->after('email');
            $table->string('zip_code')->nullable()->after('phone');
            $table->date('dob')->nullable()->after('zip_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'f_name',
                'l_name',
                'provider',
                'provider_id',
                'avatar',
                'is_social_logged',
                'phone',
                'zip_code',
                'dob',
            ]);
        });
    }
};
