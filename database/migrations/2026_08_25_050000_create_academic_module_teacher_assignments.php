<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->boolean('is_academic')->default(false)->after('name');
            $table->index(['company_id', 'is_academic', 'is_active']);
        });

        DB::table('positions')
            ->whereIn('area_id', DB::table('areas')->select('id')->whereRaw("LOWER(name) = 'académico'"))
            ->update(['is_academic' => true]);

        Schema::create('academic_module_teacher_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'academic_module_id']);
            $table->index(['company_id', 'personnel_id']);
        });

        DB::statement('CREATE UNIQUE INDEX academic_module_one_active_teacher ON academic_module_teacher_assignments (academic_module_id) WHERE unassigned_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_module_teacher_assignments');
        Schema::table('positions', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'is_academic', 'is_active']);
            $table->dropColumn('is_academic');
        });
    }
};
