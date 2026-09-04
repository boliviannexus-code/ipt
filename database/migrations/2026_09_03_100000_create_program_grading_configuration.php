<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_grading_schemes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->decimal('passing_score', 5, 2)->default(51);
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique('program_id');
            $table->index(['company_id', 'finalized_at']);
        });

        Schema::create('program_grading_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_grading_scheme_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('weight', 5, 2);
            $table->unsignedTinyInteger('position');
            $table->timestamps();
            $table->unique(['program_grading_scheme_id', 'position']);
        });

        DB::statement('ALTER TABLE program_grading_schemes ADD CONSTRAINT program_grading_schemes_passing_score_check CHECK (passing_score >= 0 AND passing_score <= 100)');
        DB::statement('ALTER TABLE program_grading_components ADD CONSTRAINT program_grading_components_weight_check CHECK (weight > 0 AND weight <= 100)');

        $now = now();
        $programs = DB::table('programs')->select(['id', 'company_id'])->orderBy('id')->get();

        foreach ($programs as $program) {
            $schemeId = DB::table('program_grading_schemes')->insertGetId([
                'company_id' => $program->company_id,
                'program_id' => $program->id,
                'passing_score' => 51,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('program_grading_components')->insert($this->defaultComponents((int) $program->company_id, $schemeId, $now));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('program_grading_components');
        Schema::dropIfExists('program_grading_schemes');
    }

    private function defaultComponents(int $companyId, int $schemeId, mixed $timestamp): array
    {
        return collect(['Overall Performance', 'Attendance and Participation', 'Speaking Test', 'Final Test'])
            ->values()
            ->map(fn (string $name, int $position): array => [
                'company_id' => $companyId,
                'program_grading_scheme_id' => $schemeId,
                'name' => $name,
                'weight' => 25,
                'position' => $position + 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->all();
    }
};
