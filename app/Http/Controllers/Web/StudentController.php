<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));
        $students = Student::query()
            ->with(['campus', 'contracts' => fn ($query) => $query->with('program')->where('status', 'enrolled')])
            ->withCount('moduleAssignments')
            ->where('is_active', true)
            ->whereHas('contracts', fn ($query) => $query->where('status', 'enrolled'))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('identity_document', 'ilike', "%{$search}%")
                    ->orWhere('account_number', 'ilike', "%{$search}%")
                    ->orWhere('first_name', 'ilike', "%{$search}%")
                    ->orWhere('paternal_surname', 'ilike', "%{$search}%")
                    ->orWhere('maternal_surname', 'ilike', "%{$search}%");
            }))
            ->orderBy('first_name')
            ->orderBy('paternal_surname')
            ->paginate(15)
            ->withQueryString();

        return view('students.index', compact('students', 'search'));
    }
}
