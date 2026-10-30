<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAcademicPersonalDataRequest;
use App\Models\RectorateApplication;
use App\Services\Academic\AcademicPersonalDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AcademicPersonalDataController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));
        $applications = RectorateApplication::query()->with(['student', 'program'])
            ->whereHas('student', fn ($query) => $query->whereNull('deleted_at'))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    foreach (['account_number', 'identity_document', 'first_name', 'paternal_surname', 'maternal_surname'] as $field) {
                        $query->orWhere($field, 'ilike', "%{$search}%");
                    }
                    $query->orWhereHas('student', function ($query) use ($search): void {
                        foreach (['account_number', 'identity_document', 'first_name', 'paternal_surname', 'maternal_surname'] as $field) {
                            $query->orWhere($field, 'ilike', "%{$search}%");
                        }
                    });
                });
            })->latest('id')->paginate(15)->withQueryString();

        return view('academic-personal-data.index', compact('applications', 'search'));
    }

    public function edit(RectorateApplication $application): View
    {
        $application->load(['student', 'program']);
        abort_if($application->student === null || $application->student->trashed(), 404);

        return view('academic-personal-data.edit', compact('application'));
    }

    public function update(UpdateAcademicPersonalDataRequest $request, RectorateApplication $application, AcademicPersonalDataService $service): RedirectResponse
    {
        $service->update($application, $request->validated());

        return redirect()->route('academic.personal-data.edit', $application)->with('success', 'Datos del titular y estudiante actualizados correctamente.');
    }
}
