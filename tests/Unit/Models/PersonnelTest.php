<?php

namespace Tests\Unit\Models;

use App\Models\Personnel;
use PHPUnit\Framework\TestCase;

class PersonnelTest extends TestCase
{
    public function test_names_are_normalized_to_title_case(): void
    {
        $personnel = new Personnel([
            'first_name' => '  áLVARO   josÉ ',
            'paternal_surname' => 'pACHECO',
            'maternal_surname' => '  de la CRUZ ',
        ]);

        $this->assertSame('Álvaro José', $personnel->first_name);
        $this->assertSame('Pacheco', $personnel->paternal_surname);
        $this->assertSame('De La Cruz', $personnel->maternal_surname);
        $this->assertSame('Álvaro José Pacheco De La Cruz', $personnel->full_name);
    }
}
