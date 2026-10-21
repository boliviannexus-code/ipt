<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountCharge;
use App\Models\AdditionalService;
use App\Models\Student;
use App\Services\Enrollment\AdditionalChargeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdditionalChargeController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['q'] ?? '');
        $students = Student::query()->with('campus')->withCount('contracts')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('identity_document', 'ilike', "%{$search}%")->orWhere('account_number', 'ilike', "%{$search}%")
                    ->orWhereRaw("concat_ws(' ', first_name, paternal_surname, maternal_surname) ilike ?", ["%{$search}%"]);
            }))->orderBy('first_name')->orderBy('paternal_surname')->paginate(20)->withQueryString();

        return view('additional-charges.index', compact('students', 'search'));
    }

    public function show(Student $student): View
    {
        $contracts = $student->contracts()->with('program')->orderBy('id')->get();
        $query = AccountCharge::query()->whereIn('enrollment_contract_id', $contracts->pluck('id'));
        $totals = (clone $query)->where('status', '!=', 'cancelled')
            ->selectRaw('COALESCE(SUM(amount), 0) as charged, COALESCE(SUM(paid_amount), 0) as paid')->first();
        $charges = $query->with('contract.program')->latest('created_at')->latest('id')->paginate(20);
        $services = AdditionalService::query()->where('company_id', $student->company_id)->where('is_active', true)->orderBy('title')->get();
        $submissionToken = old('submission_token', (string) Str::uuid());

        return view('additional-charges.show', compact('student', 'contracts', 'totals', 'charges', 'services', 'submissionToken'));
    }

    public function store(Request $request, Student $student, AdditionalChargeService $service): RedirectResponse
    {
        $data = $request->validate([
            'enrollment_contract_id' => ['required', 'integer'],
            'additional_service_id' => ['required', 'integer'],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'submission_token' => ['required', 'uuid'],
        ]);
        $service->record($request->user(), $student, $data);

        return redirect()->route('additional-charges.show', $student)->with('success', 'Cargo adicional agregado a la cuenta del estudiante.');
    }
}
