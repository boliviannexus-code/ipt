<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS program_grading_schemes_one_draft_per_program');
        DB::statement('DROP INDEX IF EXISTS program_grading_schemes_one_active_per_program');
        Schema::table('program_grading_schemes', function (Blueprint $table): void {
            $table->dropUnique(['program_id', 'version']);
            $table->dropForeign(['program_id']);
            $table->unsignedBigInteger('program_id')->nullable()->change();
            $table->string('name', 150)->default('Ponderación');
        });
        Schema::table('program_levels', function (Blueprint $table): void {
            $table->foreignId('program_grading_scheme_id')->nullable()->constrained()->restrictOnDelete();
        });
        foreach (DB::table('program_grading_schemes')->orderBy('id')->get() as $scheme) {
            $program = DB::table('programs')->find($scheme->program_id);
            DB::table('program_grading_schemes')->where('id', $scheme->id)->update([
                'name' => mb_substr(($program?->title ?? 'Ponderación').' · versión '.$scheme->version, 0, 150),
            ]);
            if ($scheme->is_active) {
                DB::table('program_levels')->where('program_id', $scheme->program_id)
                    ->where('company_id', $scheme->company_id)->update(['program_grading_scheme_id' => $scheme->id]);
            }
        }
        DB::table('program_grading_schemes')->update(['program_id' => null]);
    }

    public function down(): void
    {
        // Independent configurations cannot be mapped back to a single program without losing assignments.
        throw new RuntimeException('Esta migración requiere una restauración del respaldo para revertir el cambio de flujo.');
    }
};
