<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_modules', function (Blueprint $table): void {
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });

        DB::table('academic_modules')->whereNull('start_date')->update([
            'start_date' => DB::raw('CURRENT_DATE'),
            'end_date' => DB::raw('CURRENT_DATE'),
        ]);

        DB::statement('ALTER TABLE academic_modules ALTER COLUMN start_date SET NOT NULL');
        DB::statement('ALTER TABLE academic_modules ALTER COLUMN end_date SET NOT NULL');
        DB::statement('ALTER TABLE academic_modules DROP CONSTRAINT academic_modules_duration_unit_check');
        DB::statement('ALTER TABLE academic_modules DROP CONSTRAINT academic_modules_duration_check');

        Schema::table('academic_modules', function (Blueprint $table): void {
            $table->dropColumn(['duration_value', 'duration_unit']);
            $table->index(['company_id', 'start_date', 'end_date']);
        });

        DB::statement('ALTER TABLE academic_modules ADD CONSTRAINT academic_modules_dates_check CHECK (end_date >= start_date)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE academic_modules DROP CONSTRAINT academic_modules_dates_check');

        Schema::table('academic_modules', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'start_date', 'end_date']);
            $table->unsignedSmallInteger('duration_value')->default(1);
            $table->string('duration_unit', 15)->default('days');
            $table->dropColumn(['start_date', 'end_date']);
        });

        DB::statement("ALTER TABLE academic_modules ADD CONSTRAINT academic_modules_duration_unit_check CHECK (duration_unit IN ('hours', 'days', 'weeks', 'months'))");
        DB::statement('ALTER TABLE academic_modules ADD CONSTRAINT academic_modules_duration_check CHECK (duration_value > 0)');
    }
};
