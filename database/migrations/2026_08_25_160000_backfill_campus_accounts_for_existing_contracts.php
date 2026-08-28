<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_contracts', function (Blueprint $table): void {
            $table->foreignId('campus_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
            $table->string('account_number', 60)->nullable()->after('campus_id');
            $table->unique(['company_id', 'account_number']);
            $table->index(['campus_id', 'status']);
        });

        DB::transaction(function (): void {
            DB::table('enrollment_contracts')
                ->orderBy('id')
                ->get()
                ->each(function (object $contract): void {
                    $application = DB::table('rectorate_applications')->where('id', $contract->rectorate_application_id)->first();
                    $student = DB::table('students')->where('id', $contract->student_id)->first();

                    $campusId = $application?->campus_id
                        ?? $student?->campus_id
                        ?? $this->salesExecutiveCampusId($application?->sales_executive_id)
                        ?? $this->firstOrCreateCampusId((int) $contract->company_id);

                    $accountNumber = $application?->account_number;
                    if (blank($accountNumber)) {
                        $accountNumber = $this->nextAccountNumber($campusId);
                        DB::table('rectorate_applications')->where('id', $contract->rectorate_application_id)->update([
                            'campus_id' => $campusId,
                            'account_number' => $accountNumber,
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('enrollment_contracts')->where('id', $contract->id)->update([
                        'campus_id' => $campusId,
                        'account_number' => $accountNumber,
                        'updated_at' => now(),
                    ]);
                    DB::table('students')->where('id', $contract->student_id)->update([
                        'campus_id' => $campusId,
                        'account_number' => $accountNumber,
                        'updated_at' => now(),
                    ]);
                });
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_contracts', function (Blueprint $table): void {
            $table->dropIndex(['campus_id', 'status']);
            $table->dropUnique(['company_id', 'account_number']);
            $table->dropConstrainedForeignId('campus_id');
            $table->dropColumn('account_number');
        });
    }

    private function salesExecutiveCampusId(?int $salesExecutiveId): ?int
    {
        if ($salesExecutiveId === null) {
            return null;
        }

        return DB::table('personnel')->where('id', $salesExecutiveId)->value('campus_id');
    }

    private function firstOrCreateCampusId(int $companyId): int
    {
        $campusId = DB::table('campuses')->where('company_id', $companyId)->orderBy('id')->value('id');
        if ($campusId !== null) {
            return (int) $campusId;
        }

        return (int) DB::table('campuses')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Sede Principal',
            'code' => '1',
            'address' => DB::table('companies')->where('id', $companyId)->value('address') ?: 'Sin dirección registrada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function nextAccountNumber(int $campusId): string
    {
        DB::table('campus_enrollment_sequences')->insertOrIgnore([
            'campus_id' => $campusId,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sequence = DB::table('campus_enrollment_sequences')->where('campus_id', $campusId)->lockForUpdate()->first();
        $number = ((int) $sequence->last_number) + 1;

        if ($number > 9999) {
            throw new RuntimeException("La sede {$campusId} superó el límite de 9.999 cuentas.");
        }

        DB::table('campus_enrollment_sequences')->where('campus_id', $campusId)->update([
            'last_number' => $number,
            'updated_at' => now(),
        ]);
        $code = (string) DB::table('campuses')->where('id', $campusId)->value('code');

        return $code.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
};
