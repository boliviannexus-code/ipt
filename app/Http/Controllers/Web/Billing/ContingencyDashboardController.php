<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Billing;

use App\Enums\InvoicePackageStatus;
use App\Enums\SignificantEventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\MonitorContingenciesRequest;
use App\Http\Requests\Billing\RegisterOpenSignificantEventRequest;
use App\Jobs\BuildContingencyPackagesJob;
use App\Jobs\CheckPackageValidationJob;
use App\Jobs\RegisterSignificantEventJob;
use App\Jobs\SendContingencyPackageJob;
use App\Models\SinApiToken;
use App\Models\SinInvoiceIssue;
use App\Models\SinInvoicePackage;
use App\Models\SinPointOfSale;
use App\Models\SinSignificantEvent;
use App\Services\Billing\ContingencyDashboardService;
use App\Services\Siat\ContingencyRecoveryService;
use App\Services\Siat\SiatCommunicationService;
use App\Services\Siat\SiatLogSanitizer;
use App\Support\CompanyContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

final class ContingencyDashboardController extends Controller
{
    public function __construct(
        private readonly ContingencyDashboardService $dashboard,
        private readonly SiatCommunicationService $communication,
        private readonly ContingencyRecoveryService $recovery,
        private readonly SiatLogSanitizer $sanitizer,
    ) {}

    public function index(MonitorContingenciesRequest $request): View
    {
        return view('billing.contingencies.index', $this->dashboard->dashboard($request->user(), $request->validated()));
    }

    public function event(Request $request, SinSignificantEvent $event): View
    {
        $this->owned($request, (int) $event->company_id);
        $event->load([
            'company', 'authorization', 'branch', 'pointOfSale', 'cuis', 'cufd', 'recoveryCufd',
            'creator', 'registrar', 'closer', 'packages',
            'invoiceIssues.customer', 'manualInvoices.customer', 'attempts.messages',
        ]);

        return view('billing.contingencies.partials.event', compact('event'));
    }

    public function technical(Request $request, string $type, int $id): View
    {
        $target = match ($type) {
            'invoice' => SinInvoiceIssue::query()->withoutGlobalScope('company')->with('attempts.messages')->findOrFail($id),
            'package' => SinInvoicePackage::query()->withoutGlobalScope('company')->with('attempts.messages')->findOrFail($id),
            'event' => SinSignificantEvent::query()->withoutGlobalScope('company')->with('attempts.messages')->findOrFail($id),
            default => abort(404),
        };
        $this->owned($request, (int) $target->company_id);

        return view('billing.contingencies.partials.technical', [
            'type' => $type,
            'target' => $target,
            'response' => $this->sanitizer->data($target->response),
            'message' => $this->sanitizer->text($target->message),
        ]);
    }

    public function verifyCommunication(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            'point_of_sale_id' => ['required', 'integer', Rule::exists('sin_points_of_sale', 'id')],
        ]);
        $this->owned($request, (int) $data['company_id']);
        $token = SinApiToken::query()->withoutGlobalScope('company')->where('company_id', $data['company_id'])->first();
        $point = SinPointOfSale::query()->withoutGlobalScope('company')
            ->where('company_id', $data['company_id'])->findOrFail($data['point_of_sale_id']);

        if (! $token) {
            return back()->with('error', 'La empresa seleccionada no tiene un token API configurado.');
        }

        $actor = (int) $request->user()->company_id === (int) $data['company_id'] ? $request->user() : null;
        $result = $this->communication->verify($token, $point, $actor);

        return back()->with($result->available ? 'success' : 'error', $result->userMessage);
    }

    public function retryEvent(Request $request, SinSignificantEvent $event): RedirectResponse
    {
        $this->owned($request, (int) $event->company_id);
        abort_unless(in_array($event->event_status, [
            SignificantEventStatus::RecoveryDetected,
            SignificantEventStatus::PendingRegistration,
            SignificantEventStatus::Failed,
        ], true), 409);
        $actorId = $this->actorId($request, (int) $event->company_id);
        RegisterSignificantEventJob::dispatch((int) $event->company_id, (int) $event->id, $actorId);

        return back()->with('success', 'Registro del evento significativo encolado.');
    }

    public function registerEvent(RegisterOpenSignificantEventRequest $request, SinSignificantEvent $event): RedirectResponse
    {
        $this->owned($request, (int) $event->company_id);
        abort_unless(in_array($event->event_status, [
            SignificantEventStatus::Open,
            SignificantEventStatus::RecoveryDetected,
            SignificantEventStatus::PendingRegistration,
            SignificantEventStatus::Failed,
        ], true) && ! $event->transaccion && $event->registration_claim === null, 409);
        $data = $request->validated();
        try {
            $result = $this->recovery->prepareAndDetectRecovery(
                $event,
                $request->user(),
                (int) $data['event_code'],
                (string) $data['description'],
            );
        } catch (Throwable $exception) {
            report($exception);
            $message = $this->sanitizer->text($exception::class.': '.$exception->getMessage())
                ?: 'No se pudo iniciar el registro del evento significativo en SIAT.';

            return back()->with('error', $message);
        }

        return back()->with(
            $result->registered ? 'success' : 'error',
            $result->message,
        );
    }

    public function buildPackages(Request $request, SinSignificantEvent $event): RedirectResponse
    {
        $this->owned($request, (int) $event->company_id);
        if (! in_array($event->event_status, [SignificantEventStatus::Registered, SignificantEventStatus::Packaging, SignificantEventStatus::Sending, SignificantEventStatus::Validating], true)) {
            $packageCount = $event->packages()->count();
            $message = $event->event_status === SignificantEventStatus::Completed
                ? "El evento ya fue procesado y tiene {$packageCount} paquete(s). Revisa el resultado en Paquetes recientes."
                : 'El evento no está listo para generar paquetes. Estado actual: '.$event->event_status->value.'.';

            return back()->with('warning', $message);
        }

        BuildContingencyPackagesJob::dispatch((int) $event->company_id, (int) $event->id, $this->actorId($request, (int) $event->company_id));

        return back()->with('success', 'Generación de paquetes encolada.');
    }

    public function sendPackage(Request $request, SinInvoicePackage $package): RedirectResponse
    {
        $this->owned($request, (int) $package->company_id);
        abort_unless(in_array($package->package_status, [InvoicePackageStatus::Created, InvoicePackageStatus::PendingSend, InvoicePackageStatus::Failed], true), 409);
        SendContingencyPackageJob::dispatch((int) $package->company_id, (int) $package->id, $this->actorId($request, (int) $package->company_id));

        return back()->with('success', 'Reintento de envío del paquete encolado.');
    }

    public function validatePackage(Request $request, SinInvoicePackage $package): RedirectResponse
    {
        $this->owned($request, (int) $package->company_id);
        abort_unless(in_array($package->package_status, [InvoicePackageStatus::Sent, InvoicePackageStatus::PendingValidation, InvoicePackageStatus::Observed], true), 409);
        CheckPackageValidationJob::dispatch((int) $package->company_id, (int) $package->id, $this->actorId($request, (int) $package->company_id));

        return back()->with('success', 'Consulta de validación encolada.');
    }

    private function owned(Request $request, int $companyId): void
    {
        abort_unless(CompanyContext::belongsToUser($companyId, $request->user()), 403);
    }

    private function actorId(Request $request, int $companyId): ?int
    {
        return (int) $request->user()->company_id === $companyId ? (int) $request->user()->id : null;
    }
}
