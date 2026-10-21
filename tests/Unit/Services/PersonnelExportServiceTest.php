<?php

namespace Tests\Unit\Services;

use App\Models\Campus;
use App\Models\Personnel;
use App\Services\Reports\PersonnelExportService;
use Tests\TestCase;

class PersonnelExportServiceTest extends TestCase
{
    public function test_csv_contains_phone_without_personal_email_and_escapes_formulas(): void
    {
        $person = new Personnel([
            'first_name' => 'Ana', 'paternal_surname' => 'Pérez',
            'identity_document' => '=1+1', 'phone' => '+59170000000',
            'email' => 'personal@example.test',
        ]);
        foreach (['position', 'campus', 'user'] as $relation) {
            $person->setRelation($relation, null);
        }

        $contents = app(PersonnelExportService::class)->csv(collect([$person]));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contents);
        $this->assertStringContainsString('Ana Pérez', $contents);
        $this->assertStringContainsString("'=1+1", $contents);
        $this->assertStringContainsString("'+59170000000", $contents);
        $this->assertStringNotContainsString('personal@example.test', $contents);
    }

    public function test_personnel_export_uses_only_the_campus_short(): void
    {
        $person = new Personnel(['first_name' => 'Ana', 'paternal_surname' => 'Pérez']);
        $person->setRelation('campus', new Campus(['name' => 'Sede La Paz', 'short' => 'LPZ', 'code' => '1']));
        $person->setRelation('position', null);
        $person->setRelation('user', null);

        $rows = app(PersonnelExportService::class)->rows(collect([$person]));

        $this->assertSame('LPZ', $rows[0][4]);
    }

    public function test_empty_listing_produces_a_valid_pdf(): void
    {
        $this->assertStringStartsWith('%PDF-', app(PersonnelExportService::class)->pdf(collect()));
    }
}
