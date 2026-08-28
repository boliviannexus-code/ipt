@extends('layouts.admin')

@section('title', 'Dashboard académico')
@section('page-title', 'Dashboard académico')
@section('page-subtitle', $dashboardCompany ? 'Panorama académico de '.$dashboardCompany->name : 'Panorama académico general')

@section('content')
    <div class="dashboard-hero mb-3">
        <div class="dashboard-company">
            @if ($dashboardCompany?->logo_url)
                <img class="dashboard-company-logo" src="{{ $dashboardCompany->logo_url }}" alt="{{ $dashboardCompany->name }}">
            @else
                <span class="dashboard-company-mark"><i class="ti ti-school"></i></span>
            @endif
            <div>
                <div class="text-body-secondary small text-uppercase fw-semibold">Pulso académico</div>
                <h2 class="mb-1">{{ $dashboardCompany?->name ?? config('app.name') }}</h2>
                <div class="text-body-secondary">{{ now()->translatedFormat('l d \d\e F \d\e Y') }} · Seguimiento de matrícula, clases y avance curricular</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-sm-6 col-xl-3"><x-ui.stat-card label="Estudiantes inscritos" :value="$enrolledStudents" icon="ti ti-users-group" tone="primary" /><div class="dashboard-stat-note">Matrícula activa</div></div>
        <div class="col-sm-6 col-xl-3"><x-ui.stat-card label="Programas" :value="$totalPrograms" icon="ti ti-certificate" tone="success" /><div class="dashboard-stat-note">Oferta académica registrada</div></div>
        <div class="col-sm-6 col-xl-3"><x-ui.stat-card label="Planes" :value="$totalPlans" icon="ti ti-calendar-dollar" tone="warning" /><div class="dashboard-stat-note">Planes disponibles</div></div>
        <div class="col-sm-6 col-xl-3"><x-ui.stat-card label="Módulos vigentes" :value="$currentModules" icon="ti ti-books" tone="info" /><div class="dashboard-stat-note">En curso a la fecha</div></div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-7">
            <x-ui.table-card title="Programas y matrícula">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Programa</th><th class="text-center">Estudiantes</th><th class="text-center">Planes</th><th class="text-center">Módulos</th></tr></thead>
                    <tbody>@forelse($programSummary as $program)<tr><td class="fw-semibold">{{ $program->title }}</td><td class="text-center"><span class="badge text-bg-primary">{{ $program->enrolled_students_count }}</span></td><td class="text-center">{{ $program->plans_count }}</td><td class="text-center">{{ $program->academic_modules_count }}</td></tr>@empty<x-ui.empty-row colspan="4" message="Aún no existen programas registrados." />@endforelse</tbody>
                </table>
                @can('programs.view')
                    <x-slot:footer>
                        <div class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('parameters.programs.index') }}">Ver programas</a></div>
                    </x-slot:footer>
                @endcan
            </x-ui.table-card>
        </div>
        <div class="col-xl-5">
            <x-ui.card title="Actividad de hoy">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between rounded bg-primary-lt p-3 mb-3">
                        <div><span class="text-secondary small d-block">Clases iniciadas</span><strong class="h2 mb-0">{{ $totalClassesToday }}</strong></div>
                        <div class="text-end"><span class="text-secondary small d-block">Asistencias registradas</span><strong class="h2 mb-0">{{ $attendanceToday }}</strong></div>
                    </div>
                    @if ($attendanceToday > 0)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Presencia y puntualidad</span>
                                <strong>{{ round(($presentToday / $attendanceToday) * 100) }}%</strong>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width: {{ ($presentToday / $attendanceToday) * 100 }}%"></div>
                            </div>
                        </div>
                    @endif
                    @forelse($classesToday as $class)<div class="dashboard-list-row"><span class="avatar avatar-sm bg-azure-lt text-azure"><i class="ti ti-presentation"></i></span><div class="flex-fill"><div class="fw-semibold">{{ $class->module->name }}</div><div class="text-secondary small">{{ $class->teacher->full_name }} · {{ $class->started_at->format('H:i') }}</div></div><span class="badge text-bg-secondary">{{ $class->attendances_count }}/{{ $class->module->student_assignments_count }}</span></div>@empty<div class="text-center text-secondary py-4"><i class="ti ti-calendar-off fs-1 d-block mb-2"></i>No se iniciaron clases hoy.</div>@endforelse
                </div>
            </x-ui.card>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-7">
            <x-ui.table-card title="Próximos cierres de módulo">
                <table class="table table-hover align-middle mb-0"><thead><tr><th>Módulo</th><th>Programa / nivel</th><th>Fecha fin</th><th class="text-end">Días</th></tr></thead><tbody>@forelse($upcomingClosures as $module)<tr><td class="fw-semibold">{{ $module->name }}</td><td>{{ $module->program->title }}<small class="d-block text-secondary">{{ $module->level->name }}</small></td><td>{{ $module->end_date->format('d/m/Y') }}</td><td class="text-end"><span class="badge {{ today()->diffInDays($module->end_date) <= 7 ? 'text-bg-warning' : 'text-bg-secondary' }}">{{ (int) today()->diffInDays($module->end_date) }} días</span></td></tr>@empty<x-ui.empty-row colspan="4" message="No hay cierres próximos." />@endforelse</tbody></table>
                @can('academic-modules.view')
                    <x-slot:footer>
                        <div class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('academic.modules.index') }}">Ver módulos</a></div>
                    </x-slot:footer>
                @endcan
            </x-ui.table-card>
        </div>
        <div class="col-xl-5">
            <x-ui.card title="Atención académica">
                <div class="card-body">
                    <div class="dashboard-alert-row"><span class="avatar avatar-sm {{ $modulesWithoutTeacher ? 'bg-warning-lt text-warning' : 'bg-success-lt text-success' }}"><i class="ti ti-user-question"></i></span><div class="flex-fill"><div class="fw-semibold">Módulos sin docente</div><div class="text-secondary small">Requieren una asignación académica.</div></div><strong class="h3 mb-0">{{ $modulesWithoutTeacher }}</strong></div>
                    <div class="dashboard-alert-row"><span class="avatar avatar-sm {{ $modulesWithoutStudents ? 'bg-warning-lt text-warning' : 'bg-success-lt text-success' }}"><i class="ti ti-users-minus"></i></span><div class="flex-fill"><div class="fw-semibold">Módulos sin estudiantes</div><div class="text-secondary small">Vigentes o próximos sin nómina.</div></div><strong class="h3 mb-0">{{ $modulesWithoutStudents }}</strong></div>
                    <div class="dashboard-alert-row"><span class="avatar avatar-sm {{ $studentsWithoutModules ? 'bg-warning-lt text-warning' : 'bg-success-lt text-success' }}"><i class="ti ti-book-off"></i></span><div class="flex-fill"><div class="fw-semibold">Estudiantes sin módulo</div><div class="text-secondary small">Inscritos pendientes de asignación.</div></div><strong class="h3 mb-0">{{ $studentsWithoutModules }}</strong></div>
                </div>
                @can('students.view')
                    <x-slot:footer>
                        <div class="text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('students.index') }}">Revisar estudiantes</a></div>
                    </x-slot:footer>
                @endcan
            </x-ui.card>
        </div>
    </div>
@endsection
