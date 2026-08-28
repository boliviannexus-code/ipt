<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use App\Models\ClassSession;
use App\Models\Plan;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('dashboard.view');

        $company = CompanyContext::activeCompany();

        return view('dashboard.index', [
            'dashboardCompany' => $company,
            'enrolledStudents' => Student::query()->where('is_active', true)->whereHas('contracts', fn ($query) => $query->where('status', 'enrolled'))->count(),
            'totalPrograms' => Program::query()->count(),
            'totalPlans' => Plan::query()->count(),
            'currentModules' => AcademicModule::query()->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->count(),
            'totalClassesToday' => ClassSession::query()->whereDate('class_date', today())->count(),
            'classesToday' => ClassSession::query()->whereDate('class_date', today())->with(['module' => fn ($query) => $query->withCount('studentAssignments'), 'teacher'])->withCount('attendances')->latest('started_at')->limit(6)->get(),
            'attendanceToday' => StudentAttendance::query()->whereHas('session', fn ($query) => $query->whereDate('class_date', today()))->count(),
            'presentToday' => StudentAttendance::query()->whereIn('status', ['present', 'late'])->whereHas('session', fn ($query) => $query->whereDate('class_date', today()))->count(),
            'programSummary' => Program::query()->withCount([
                'contracts as enrolled_students_count' => fn ($query) => $query->where('status', 'enrolled'),
                'plans',
                'academicModules',
            ])->orderByDesc('enrolled_students_count')->limit(6)->get(),
            'upcomingClosures' => AcademicModule::query()->with(['program', 'level'])->whereDate('end_date', '>=', today())->orderBy('end_date')->limit(6)->get(),
            'modulesWithoutTeacher' => AcademicModule::query()->whereDate('end_date', '>=', today())->whereDoesntHave('currentTeacherAssignment')->count(),
            'modulesWithoutStudents' => AcademicModule::query()->whereDate('end_date', '>=', today())->whereDoesntHave('studentAssignments')->count(),
            'studentsWithoutModules' => Student::query()->where('is_active', true)->whereHas('contracts', fn ($query) => $query->where('status', 'enrolled'))->whereDoesntHave('moduleAssignments')->count(),
        ]);
    }
}
