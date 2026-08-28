<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->string('primary_contact_type', 20)->nullable()->after('student_gender');
            $table->string('reference_first_name', 100)->nullable()->after('primary_contact_type');
            $table->string('reference_last_name', 150)->nullable()->after('reference_first_name');
            $table->string('reference_relationship', 60)->nullable()->after('reference_last_name');
            $table->string('reference_phone', 30)->nullable()->after('reference_relationship');
        });
    }

    public function down(): void
    {
        Schema::table('rectorate_applications', function (Blueprint $table): void {
            $table->dropColumn([
                'primary_contact_type',
                'reference_first_name',
                'reference_last_name',
                'reference_relationship',
                'reference_phone',
            ]);
        });
    }
};
