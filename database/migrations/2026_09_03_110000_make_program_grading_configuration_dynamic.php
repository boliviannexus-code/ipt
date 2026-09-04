<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_grading_schemes', function (Blueprint $table): void {
            $table->dropUnique(['program_id']);
            $table->unsignedSmallInteger('version')->default(1)->after('program_id');
            $table->string('status', 20)->default('draft')->after('passing_score');
            $table->boolean('is_active')->default(false)->after('status');
            $table->unique(['program_id', 'version']);
        });

        Schema::table('program_grading_components', function (Blueprint $table): void {
            $table->string('frequency', 20)->default('single')->after('weight');
            $table->string('skill_mode', 30)->default('single_skill')->after('frequency');
            $table->unique(['program_grading_scheme_id', 'name']);
        });

        Schema::create('program_grading_skills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_grading_component_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('weight', 5, 2);
            $table->unsignedTinyInteger('position');
            $table->timestamps();
            $table->unique(['program_grading_component_id', 'position']);
            $table->unique(['program_grading_component_id', 'name']);
        });

        DB::statement("ALTER TABLE program_grading_schemes ADD CONSTRAINT program_grading_schemes_status_check CHECK (status IN ('draft', 'finalized'))");
        DB::statement("ALTER TABLE program_grading_components ADD CONSTRAINT program_grading_components_frequency_check CHECK (frequency IN ('daily', 'single'))");
        DB::statement("ALTER TABLE program_grading_components ADD CONSTRAINT program_grading_components_skill_mode_check CHECK (skill_mode IN ('single_skill', 'multiple_skills'))");
        DB::statement('ALTER TABLE program_grading_skills ADD CONSTRAINT program_grading_skills_weight_check CHECK (weight > 0 AND weight <= 100)');
        DB::statement("CREATE UNIQUE INDEX program_grading_schemes_one_draft_per_program ON program_grading_schemes (program_id) WHERE status = 'draft'");
        DB::statement('CREATE UNIQUE INDEX program_grading_schemes_one_active_per_program ON program_grading_schemes (program_id) WHERE is_active = true');

        DB::table('program_grading_components')->whereIn('program_grading_scheme_id', DB::table('program_grading_schemes')->whereNull('finalized_at')->select('id'))->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('program_grading_skills');
        DB::statement('DROP INDEX IF EXISTS program_grading_schemes_one_draft_per_program');
        DB::statement('DROP INDEX IF EXISTS program_grading_schemes_one_active_per_program');
        DB::statement('ALTER TABLE program_grading_schemes DROP CONSTRAINT IF EXISTS program_grading_schemes_status_check');
        DB::statement('ALTER TABLE program_grading_components DROP CONSTRAINT IF EXISTS program_grading_components_frequency_check');
        DB::statement('ALTER TABLE program_grading_components DROP CONSTRAINT IF EXISTS program_grading_components_skill_mode_check');

        $schemeIdsToDelete = DB::table('program_grading_schemes as scheme')
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('program_grading_schemes as earlier')->whereColumn('earlier.program_id', 'scheme.program_id')->whereColumn('earlier.version', '<', 'scheme.version'))
            ->pluck('id');
        DB::table('program_grading_components')->whereIn('program_grading_scheme_id', $schemeIdsToDelete)->delete();
        DB::table('program_grading_schemes')->whereIn('id', $schemeIdsToDelete)->delete();

        Schema::table('program_grading_components', function (Blueprint $table): void {
            $table->dropUnique(['program_grading_scheme_id', 'name']);
            $table->dropColumn(['frequency', 'skill_mode']);
        });
        Schema::table('program_grading_schemes', function (Blueprint $table): void {
            $table->dropUnique(['program_id', 'version']);
            $table->dropColumn(['version', 'status', 'is_active']);
            $table->unique('program_id');
        });
    }
};
