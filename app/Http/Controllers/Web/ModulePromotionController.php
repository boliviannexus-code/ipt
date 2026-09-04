<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AcademicModule;
use Illuminate\View\View;

final class ModulePromotionController extends Controller
{
    public function index(): View
    {
        return view('academic-promotions.index', [
            'modules' => AcademicModule::query()
                ->whereNotNull('closed_at')
                ->with(['program', 'level', 'currentTeacherAssignment.personnel', 'closedBy'])
                ->withCount(['studentAssignments', 'studentResults'])
                ->orderByDesc('closed_at')
                ->paginate(20),
        ]);
    }
}
