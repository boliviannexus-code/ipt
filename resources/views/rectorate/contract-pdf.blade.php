@php
    $application = $contract->application;
    $student = $contract->student;
    $company = $application->company;
    $date = $contract->confirmed_at;
    $holderName = mb_strtoupper(trim("{$application->first_name} {$application->paternal_surname} {$application->maternal_surname}"));
    $studentName = mb_strtoupper(trim("{$student->first_name} {$student->paternal_surname} {$student->maternal_surname}"));
    $holderAge = $application->birth_date?->age;
    $studentAge = $student->birth_date?->age;
    $city = mb_strtoupper($company?->city ?: 'LA PAZ');
    $durationMonths = (int) $contract->program->duration_months;
    $durationLabel = $durationMonths % 6 === 0
        ? ($durationMonths / 6).' '.(($durationMonths / 6) === 1 ? 'SEMESTRE' : 'SEMESTRES')
        : $durationMonths.' MESES';
    $installments = max(1, $durationMonths);
    $totalPrice = (float) $contract->monthly_amount * $installments;
@endphp

<table cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td width="17%" style="padding-top:18px;">
            <table border="1" cellpadding="2" cellspacing="0" width="100%">
                <tr style="background-color:#000000;color:#ffffff;font-size:8px;font-weight:bold;"><td align="center">CONTRATO</td></tr>
                <tr><td align="center" style="color:#e00000;font-size:11px;font-weight:bold;">Matrícula N.º {{ $contract->account_number }}</td></tr>
            </table>
        </td>
        <td width="66%" align="center">
            <div style="font-size:13px;font-weight:bold;font-style:italic;">INSTITUTO TÉCNICO “INGLÉS PARA TODOS”</div>
            <div style="font-size:10px;font-weight:bold;">R.M. 0557/2021</div>
            <div style="font-size:15px;font-weight:bold;font-style:italic;line-height:24px;">INSCRIPCIÓN CURSOS DE CAPACITACIÓN</div>
        </td>
        <td width="17%" style="padding-top:18px;">
            <table border="1" cellpadding="2" cellspacing="0" width="100%">
                <tr style="background-color:#000000;color:#ffffff;font-size:8px;font-weight:bold;"><td colspan="3" align="center"><b>{{ $city }}</b></td></tr>
                <tr style="color:#777777;font-size:7px;font-weight:bold;"><td align="center">DÍA<br><span style="color:#000000;font-size:9px;">{{ $date?->format('d') }}</span></td><td align="center">MES<br><span style="color:#000000;font-size:9px;">{{ $date?->format('m') }}</span></td><td align="center">AÑO<br><span style="color:#000000;font-size:9px;">{{ $date?->format('Y') }}</span></td></tr>
            </table>
        </td>
    </tr>
</table>

<div style="font-size:9px;font-weight:bold;line-height:22px;">I. &nbsp;&nbsp;&nbsp;&nbsp; DATOS GENERALES</div>

<table border="1" cellpadding="3" cellspacing="0" width="100%" style="font-size:7.5px;">
    <tr style="font-weight:bold;">
        <td width="15%"></td>
        <td width="50%" align="center">NOMBRES Y APELLIDOS COMPLETOS</td>
        <td width="15%" align="center">CARNET IDENTIDAD</td>
        <td width="10%" align="center" style="color:#2222ff;">FECHA NACIDO</td>
        <td width="10%" align="center">EDAD</td>
    </tr>
    <tr>
        <td align="center" style="font-weight:bold;">RESPONSABLE/<br>TUTOR</td>
        <td style="font-size:8.5px;"><b>{{ $holderName }}</b></td>
        <td align="center" style="font-size:8.5px;"><b>{{ $application->identity_document }}</b></td>
        <td align="center" style="font-size:8.5px;"><b>{{ $application->birth_date?->format('d/m/Y') }}</b></td>
        <td align="center" style="font-size:8.5px;"><b>{{ $holderAge }}</b></td>
    </tr>
    <tr>
        <td align="center" style="font-weight:bold;">ESTUDIANTE</td>
        <td style="font-size:8.5px;"><b>{{ $studentName }}</b></td>
        <td align="center" style="font-size:8.5px;"><b>{{ $student->identity_document }}</b></td>
        <td align="center" style="font-size:8.5px;"><b>{{ $student->birth_date?->format('d/m/Y') }}</b></td>
        <td align="center" style="font-size:8.5px;"><b>{{ $studentAge }}</b></td>
    </tr>
</table>

<table border="1" cellpadding="3" cellspacing="0" width="100%" style="font-size:7.5px;">
    <tr style="font-weight:bold;">
        <td width="24%" align="center">No. De Celular Tutor</td>
        <td width="25%" align="center">No. De Celular Estudiante</td>
        <td width="51%" align="center">CORREOS ELECTRÓNICOS ESTUDIANTE</td>
    </tr>
    <tr>
        <td align="center" style="font-size:8.5px;"><b>{{ $application->phone }}</b></td>
        <td align="center" style="font-size:8.5px;"><b>{{ $student->phone }}</b></td>
        <td align="center" style="font-size:8.5px;"><b>{{ $student->email }}</b></td>
    </tr>
</table>

<table cellpadding="0" cellspacing="0" width="100%" style="font-size:8px;">
    <tr style="font-weight:bold;">
        <td width="68%" style="line-height:20px;">II.- &nbsp;&nbsp;&nbsp; PROGRAMA “INGLÉS PARA TODOS”</td>
        <td width="2%"></td>
        <td width="30%" style="line-height:20px;">III.- &nbsp;&nbsp;&nbsp; PRECIO</td>
    </tr>
    <tr>
        <td width="68%">
            <table border="1" cellpadding="3" cellspacing="0" width="100%">
                <tr style="font-size:7px;font-weight:bold;">
                    <td width="22%" align="center">TIEMPO DE<br>CAPACITACIÓN</td>
                    <td width="51%" align="center">NOMBRE DEL CURSO</td>
                    <td width="27%" align="center">CONTENIDO</td>
                </tr>
                <tr>
                    <td width="22%" align="center" style="font-size:8px;font-weight:bold;line-height:62px;">{{ $durationLabel }}</td>
                    <td width="51%" align="center" style="font-size:11px;font-weight:bold;line-height:28px;">CURSO DE<br>{{ mb_strtoupper($contract->program->title) }}</td>
                    <td width="27%" style="font-size:7.5px;line-height:14px;">
                        @forelse ($contract->program->levels as $level)
                            <span style="font-family:dejavusans;">&#9744;</span>&nbsp; <b>{{ mb_strtoupper($level->name) }}</b><br>
                        @empty
                            <span style="font-family:dejavusans;">&#9744;</span>&nbsp; CONTENIDO SEGÚN PROGRAMA
                        @endforelse
                    </td>
                </tr>
            </table>
        </td>
        <td width="2%"></td>
        <td width="30%">
            <table border="1" cellpadding="4" cellspacing="0" width="100%">
                <tr><td align="center" style="font-weight:bold;line-height:18px;">Precio Total</td></tr>
                <tr><td align="center"><span style="background-color:#bdbdbd;font-size:10px;font-weight:bold;">&nbsp;&nbsp; Bs. {{ number_format($totalPrice, 2, ',', '.') }} &nbsp;&nbsp;</span></td></tr>
                <tr><td align="center" style="font-weight:bold;line-height:18px;">Cuotas</td></tr>
                <tr><td align="center"><span style="background-color:#bdbdbd;font-size:10px;font-weight:bold;">&nbsp;&nbsp; {{ $installments }} &nbsp;&nbsp;</span></td></tr>
                <tr><td align="center" style="font-weight:bold;">de</td></tr>
                <tr><td align="center"><span style="background-color:#bdbdbd;font-size:10px;font-weight:bold;">&nbsp;&nbsp; Bs. {{ number_format((float) $contract->monthly_amount, 2, ',', '.') }} &nbsp;&nbsp;</span></td></tr>
            </table>
        </td>
    </tr>
</table>

<table cellpadding="0" cellspacing="0" width="100%" style="font-size:7.5px;">
    <tr style="font-weight:bold;">
        <td width="32%" style="line-height:20px;">IV.- &nbsp;&nbsp;&nbsp; REQUISITOS</td>
        <td width="2%"></td>
        <td width="66%" style="line-height:20px;">V.- &nbsp;&nbsp;&nbsp; MODALIDAD DE PAGO</td>
    </tr>
    <tr>
        <td width="32%">
            <table border="1" cellpadding="4" cellspacing="0" width="100%">
                <tr><td style="line-height:15px;font-weight:bold;">&#8226;&nbsp; MAYOR A 13 AÑOS DE EDAD<br>&#8226;&nbsp; FOTOCOPIA DE C.I. DEL ESTUDIANTE<br>&#8226;&nbsp; FOTOCOPIA DE C.I. DEL RESPONSABLE Y/O TUTOR<br>&#8226;&nbsp; FOTOCOPIA DEL CERTIFICADO DE NACIMIENTO</td></tr>
            </table>
        </td>
        <td width="2%"></td>
        <td width="66%">
            <table border="1" cellpadding="4" cellspacing="0" width="100%">
                <tr><td colspan="2" style="font-weight:bold;line-height:16px;">BS. {{ number_format((float) $contract->monthly_amount, 2, ',', '.') }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-family:dejavusans;">&#9744;</span> COMPLETA &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-family:dejavusans;">&#9744;</span> ABONO<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-family:dejavusans;">&#9744;</span> EFECTIVO &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-family:dejavusans;">&#9744;</span> TARJETA DE CRÉDITO O DÉBITO<br><span style="font-size:6.5px;">(*) VALOR CORRESPONDIENTE A LA PRIMERA MENSUALIDAD</span></td></tr>
                <tr><td width="20%" align="center" style="font-weight:bold;">DATOS<br>FACTURA</td><td width="80%"><b>RAZÓN SOCIAL Y/O NOMBRE: {{ mb_strtoupper($application->customer?->name ?? '') }}</b><br><b>NIT: {{ $application->customer?->document_number }}</b></td></tr>
            </table>
        </td>
    </tr>
</table>

<div style="font-size:8px;font-weight:bold;line-height:18px;">VI.- &nbsp;&nbsp; OBSERVACIONES Y/O INFORMACIÓN ADICIONAL:</div>
<div style="border:1.5px solid #000000;border-radius:16px;padding:8px 12px;font-size:7.5px;">
    <table cellpadding="2" cellspacing="0" width="100%" style="font-size:7px;">
        <tr><td style="border-bottom:1px dotted #555555;line-height:13px;">&nbsp;</td></tr>
        <tr><td style="border-bottom:1px dotted #555555;line-height:13px;">&nbsp;</td></tr>
        <tr><td style="border-bottom:1px dotted #555555;line-height:13px;">&nbsp;</td></tr>
        <tr><td style="border-bottom:1px dotted #555555;line-height:13px;">&nbsp;</td></tr>
    </table>
</div>

<table cellpadding="3" cellspacing="0" width="100%" style="font-size:7px;font-weight:bold;"><tr><td align="center" style="line-height:14px;"><br><br><br>________________________________________<br>FIRMA ESTUDIANTE Y/O RESPONSABLE Y/O TUTOR<br>N.º C.I. ______________________________</td></tr></table>

<br pagebreak="true" />

<table cellpadding="2" cellspacing="0" width="100%" style="font-size:8px;line-height:10.5px;text-align:justify;">
    <tr style="background-color:#b8b7b2;font-size:10px;font-weight:bold;"><td align="center">CONTRATO PRIVADO DE PRESTACIÓN DE SERVICIO EDUCATIVO</td></tr>
    <tr><td>Conste por el presente documento privado un Contrato de Prestación de Servicio Educativo, que con reconocimiento de firmas y rúbricas surtirá efectos de instrumento público, y que se suscribe a tenor de las cláusulas siguientes:</td></tr>
    <tr><td><b>Primera (Partes).-</b> El presente documento se encuentra suscrito por las siguientes partes:<br>
        &nbsp;&nbsp;&nbsp;&nbsp;<b>1.1</b> La institución <b>{{ mb_strtoupper($company?->legal_name ?: $company?->name ?: 'INSTITUTO TÉCNICO “INGLÉS PARA TODOS”') }}</b>, legalmente constituida bajo las leyes del Estado Plurinacional de Bolivia, con Número de Identificación Tributaria <b>{{ $company?->tax_id ?: '________________' }}</b>, que para efectos del presente contrato se denominará el <b>“INSTITUTO”</b>.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;<b>1.2</b> El/la Sr.(a) <b>{{ $holderName }}</b>, con C.I. <b>{{ $application->identity_document }}</b>, en calidad de <b>{{ mb_strtoupper($application->student_relationship ?: 'RESPONSABLE Y/O TUTOR') }}</b> del estudiante <b>{{ $studentName }}</b>, con C.I. <b>{{ $student->identity_document }}</b>, quien para efectos del presente contrato será denominado(a) el/la <b>“ESTUDIANTE y/o TUTOR”</b>.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;<b>1.3</b> Cuando el presente contrato se refiera a la <b>“INSTITUCIÓN”</b> y al <b>“ESTUDIANTE”</b> de forma conjunta, los mismos serán denominados <b>“LAS PARTES”</b>.</td></tr>
    <tr><td><b>Segunda (Antecedentes).-</b> El <b>INSTITUTO</b> es una institución educativa de formación Técnica Superior que opera legalmente en la ciudad de <b>{{ $city }}</b>, en virtud de la Resolución Ministerial N.º 557/2021, que lo hace parte del Subsistema de Educación Superior en Idiomas.</td></tr>
    <tr><td><b>Tercera (Reglamento).-</b> El <b>ESTUDIANTE</b> se declara conforme a la disciplina y normas del <b>INSTITUTO</b>, aceptando conocer el reglamento interno y el plan de estudios del programa <b>{{ mb_strtoupper($contract->program->title) }}</b>, adjuntos al presente documento.</td></tr>
    <tr><td><b>Cuarta (Objeto).-</b> Por los antecedentes expuestos, el objeto del presente Contrato es la formalización de la solicitud de admisión al <b>INSTITUTO</b> por parte del <b>ESTUDIANTE y/o TUTOR</b>, para que asista regularmente durante el periodo académico de <b>{{ $durationLabel }}</b>, declarando conocer, aceptar y cumplir todas las normas referidas a su organización, funcionamiento, plan de estudios y forma de evaluación.</td></tr>
    <tr><td><b>Quinta (Costo del Servicio Educativo).-</b> El costo total del servicio educativo será de <b>Bs. {{ number_format($totalPrice, 2, ',', '.') }}</b>, monto que podrá cancelarse en <b>{{ $installments }} cuotas</b> de <b>Bs. {{ number_format((float) $contract->monthly_amount, 2, ',', '.') }}</b> cada una.<br>
        Este monto deberá ser abonado en forma anticipada, mediante pago directo o depósito en la cuenta bancaria establecida por el <b>INSTITUTO</b>.<br>
        Las <b>PARTES</b> acuerdan que en caso de existir dos o más cuotas adeudadas por el <b>ESTUDIANTE</b>, el <b>INSTITUTO</b> elaborará una liquidación de la deuda, pudiendo tomar las acciones que considere convenientes para cobrar lo adeudado.</td></tr>
    <tr><td><b>Sexta (Derechos del Estudiante).-</b> Durante la vigencia del contrato el <b>ESTUDIANTE</b> tendrá los siguientes derechos:<br>
        &nbsp;&nbsp;&nbsp;&nbsp;<b>6.1</b> Recibir el Servicio Educativo en forma integral.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;<b>6.2</b> Ser tratado con respeto, sin discriminación ni racismo y con la consideración que corresponde a toda persona.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;<b>6.3</b> Participar en todas las actividades curriculares y extracurriculares.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;<b>6.4</b> Conocer el calendario académico y las modalidades de evaluación.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;<b>6.5</b> Recibir ayuda oportuna y adecuada acorde a sus necesidades individuales.</td></tr>
    <tr><td><b>Séptima (Obligaciones de las Partes).-</b> Durante la vigencia del contrato las partes acuerdan:<br>
        &nbsp;&nbsp;&nbsp;&nbsp;<b>7.1</b> El <b>ESTUDIANTE</b> se compromete a cumplir con lo establecido en el Reglamento Interno del <b>INSTITUTO</b>, específicamente en lo que corresponde a derechos, obligaciones y prohibiciones. Igualmente, el estudiante declara tener pleno conocimiento de las siguientes normas:<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;a) No está permitido fumar dentro de las instalaciones del <b>INSTITUTO</b>.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;b) No está permitido el uso y/o manejo de drogas, alcohol y/o sustancias que se puedan inhalar en las instalaciones.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;c) No está permitido portar armas punzocortantes, de fuego u otros similares.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;d) Valorar y respetar al Plantel Directivo, Administrativo y Docente del Instituto.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;e) Cumplir con el pago de las cuotas mensuales conforme a la Cláusula Quinta anterior.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;<b>7.2</b> El <b>INSTITUTO</b> se compromete, en su sentido más amplio y de manera enunciativa y no limitativa, a:<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;a) Brindar los servicios educativos, ofreciendo una educación integral de acuerdo con los planes educativos aprobados por el Ministerio de Educación mediante Resolución Ministerial 557/2021.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;b) Disponer de un Plantel Directivo, Administrativo y Docente de calidad que garantice los servicios educativos.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;c) Cumplir y hacer cumplir el Reglamento Interno del <b>INSTITUTO</b> y lo establecido en la Ley de Educación N.º 070 “Avelino Siñani – Elizardo Pérez” y la Ley Contra el Racismo y Toda Forma de Discriminación N.º 045.<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;d) Prestar el servicio educativo en el marco de la excelencia académica y de los valores humanos establecidos.</td></tr>
    <tr><td><b>Octava (Conciliación).-</b> Las <b>PARTES</b> acuerdan que toda discrepancia, cuestión o reclamación que se pueda suscitar durante la vigencia del presente contrato de prestación de servicio educativo o relacionada con el mismo será resuelta mediante conciliación entre las <b>PARTES</b>.</td></tr>
    <tr><td><b>Novena (Aceptación).-</b> Las <b>PARTES</b> declaran conocer el contenido de cada una de las cláusulas, por lo que suscriben el presente documento en total acto de buena fe y voluntad, sin que medie vicio del consentimiento, en doble ejemplar.</td></tr>
    <tr><td>Suscrito en la ciudad de <b>{{ $city }}</b> a los <b>{{ $date?->format('d') }}</b> días del mes de <b>{{ mb_strtoupper($date?->locale('es')->translatedFormat('F') ?? '') }}</b> de <b>{{ $date?->format('Y') }}</b>.</td></tr>
    <tr><td>
        <table cellpadding="3" cellspacing="0" width="100%" style="font-size:7.5px;font-weight:bold;">
            <tr><td width="50%" align="center" style="line-height:25px;">__________________________________<br>FIRMA ESTUDIANTE Y/O TUTOR<br>N.º C.I. <b>{{ $application->identity_document }}</b></td><td width="50%" align="center" style="line-height:25px;">__________________________________<br>REPRESENTANTE LEGAL<br><b>{{ mb_strtoupper($company?->legal_name ?: $company?->name ?: 'INSTITUTO TÉCNICO “INGLÉS PARA TODOS”') }}</b></td></tr>
        </table>
    </td></tr>
</table>
