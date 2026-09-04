<?php

namespace App\Http\Controllers\Web\Rectorate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rectorate\StoreHolderStepRequest;
use App\Http\Requests\Rectorate\StorePlanStepRequest;
use App\Http\Requests\Rectorate\StoreStudentStepRequest;
use App\Models\CommercialOrigin;
use App\Models\EnrollmentContract;
use App\Models\Personnel;
use App\Models\Program;
use App\Models\RectorateApplication;
use App\Services\Rectorate\EnrollmentContractDisablingService;
use App\Services\Rectorate\EnrollmentContractPdfService;
use App\Services\Rectorate\EnrollmentStepService;
use App\Services\Rectorate\HolderStepService;
use App\Support\SiatIdentityDocumentTypes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class NewApplicationController extends Controller
{
    public function __construct(
        private readonly HolderStepService $holders,
        private readonly EnrollmentStepService $steps,
        private readonly EnrollmentContractPdfService $contractPdf,
        private readonly EnrollmentContractDisablingService $contractDisabling,
    ) {}

    public function create(): View
    {
        return view('rectorate.new', ['identityDocumentTypes' => SiatIdentityDocumentTypes::enrollmentOptions()]);
    }

    public function index(): View
    {
        return view('rectorate.index');
    }

    public function store(StoreHolderStepRequest $request): RedirectResponse
    {
        $application = $this->holders->create($request->user(), $request->validated());

        return redirect()->route('rectorate.applications.plan.edit', $application)
            ->with('success', 'Datos del titular guardados. Ahora selecciona el programa y plan.');
    }

    public function editHolder(RectorateApplication $application): View
    {
        $application->load('customer');

        return view('rectorate.new', [
            'application' => $application,
            'identityDocumentTypes' => SiatIdentityDocumentTypes::enrollmentOptions(),
        ]);
    }

    public function updateHolder(StoreHolderStepRequest $request, RectorateApplication $application): RedirectResponse
    {
        $this->holders->update($request->user(), $application->load('customer'), $request->validated());

        return redirect()->route('rectorate.applications.plan.edit', $application)
            ->with('success', 'Datos del titular actualizados.');
    }

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identity_document' => ['required', 'string', 'regex:/^\d{5,10}$/'],
        ]);

        $application = RectorateApplication::query()
            ->with('customer')
            ->where('identity_document', $data['identity_document'])
            ->latest('id')
            ->first();

        if (! $application) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'holder' => [
                'first_name' => $application->first_name,
                'paternal_surname' => $application->paternal_surname,
                'maternal_surname' => $application->maternal_surname,
                'birth_date' => $application->birth_date?->toDateString(),
                'email' => $application->email,
                'phone' => $application->phone,
            ],
            'billing' => [
                'identity_document_type_code' => (string) $application->customer->identity_document_type_code,
                'document_number' => $application->customer->document_number,
                'document_complement' => $application->customer->document_complement,
                'legal_name' => $application->customer->name,
            ],
        ]);
    }

    public function editPlan(RectorateApplication $application): View
    {
        return view('rectorate.plan', [
            'application' => $application->load('customer'),
            'programs' => Program::query()->with(['plans' => fn ($query) => $query->orderBy('name')])->orderBy('title')->get(),
            'commercialOrigins' => CommercialOrigin::query()->orderBy('name')->get(),
            'salesExecutives' => Personnel::query()
                ->where('is_active', true)
                ->where('is_sales_enabled', true)
                ->orderBy('first_name')
                ->orderBy('paternal_surname')
                ->get(),
        ]);
    }

    public function updatePlan(StorePlanStepRequest $request, RectorateApplication $application): RedirectResponse
    {
        $this->steps->assignPlan(
            $application,
            (int) $request->validated('program_id'),
            (int) $request->validated('plan_id'),
            (int) $request->validated('commercial_origin_id'),
            (int) $request->validated('sales_executive_id'),
        );

        return redirect()->route('rectorate.applications.student.edit', $application)
            ->with('success', 'Programa y plan seleccionados. Ahora registra los datos del estudiante.');
    }

    public function editStudent(RectorateApplication $application): View
    {
        abort_unless($application->program_id !== null && $application->plan_id !== null, 409, 'Primero debes seleccionar un programa y plan.');

        return view('rectorate.student', [
            'application' => $application->load(['customer', 'program', 'plan']),
        ]);
    }

    public function updateStudent(StoreStudentStepRequest $request, RectorateApplication $application): RedirectResponse
    {
        abort_unless($application->program_id !== null && $application->plan_id !== null, 409, 'Primero debes seleccionar un programa y plan.');
        $this->steps->saveStudent($application, $request->validated());

        return redirect()->route('rectorate.applications.confirmation.show', $application)
            ->with('success', 'Datos del estudiante guardados. Revisa el resumen antes de confirmar.');
    }

    public function confirmation(RectorateApplication $application): View
    {
        abort_unless($application->program_id !== null && $application->plan_id !== null && filled($application->student_identity_document), 409, 'Completa los pasos anteriores.');

        return view('rectorate.confirmation', [
            'application' => $application->load(['customer', 'campus', 'program', 'plan', 'commercialOrigin', 'salesExecutive', 'student']),
        ]);
    }

    public function confirm(RectorateApplication $application): RedirectResponse
    {
        $this->steps->confirm($application);

        return redirect()->route('rectorate.index')
            ->with('success', 'Inscripción confirmada y estudiante registrado correctamente.');
    }

    public function printContract(EnrollmentContract $contract): Response
    {
        $contract->load(['application.company', 'application.customer', 'campus', 'student', 'program.levels', 'plan']);
        $contents = $this->contractPdf->render($contract);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->contractPdf->filename($contract->account_number).'"',
        ]);
    }

    public function disableContract(EnrollmentContract $contract): RedirectResponse
    {
        $this->contractDisabling->disable($contract);

        return redirect()->route('rectorate.index')->with('success', 'Contrato inhabilitado correctamente y retirado de los reportes económicos.');
    }

    public function destroy(RectorateApplication $application): RedirectResponse
    {
        abort_if($application->status === 'completed', 409, 'Una inscripción aprobada no puede eliminarse.');
        $application->delete();

        return redirect()->route('rectorate.index')->with('success', 'Inscripción eliminada correctamente.');
    }
}
