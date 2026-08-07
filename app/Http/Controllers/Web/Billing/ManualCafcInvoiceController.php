<?php

namespace App\Http\Controllers\Web\Billing;

use App\Enums\CafcRangeStatus;
use App\Enums\ManualContingencyInvoiceStatus;
use App\Enums\SignificantEventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\RecordManualCafcInvoiceRequest;
use App\Http\Requests\Billing\TranscribeManualCafcInvoiceRequest;
use App\Jobs\SendManualCafcInvoiceJob;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SinCafcRange;
use App\Models\SinManualContingencyInvoice;
use App\Models\SinPointOfSale;
use App\Models\SinSignificantEvent;
use App\Services\Billing\ManualCafcService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ManualCafcInvoiceController extends Controller
{
    public function __construct(private readonly ManualCafcService $cafc) {}

    public function index(): View
    {
        return view('billing.manual-cafc.index', [
            'manualInvoices' => SinManualContingencyInvoice::query()->with(['cafcRange', 'branch', 'pointOfSale', 'transcriber'])->latest('issued_manually_at')->paginate(15),
            'ranges' => SinCafcRange::query()->with(['branch', 'pointOfSale'])
                ->whereIn('range_status', [CafcRangeStatus::Available, CafcRangeStatus::InUse])->orderBy('authorized_until')->get(),
            'points' => SinPointOfSale::query()->with('branch')->where('is_active', true)->orderBy('sin_branch_id')->orderBy('point_of_sale_code')->get(),
            'events' => SinSignificantEvent::query()->whereNull('closed_at')->latest('started_at')->get(),
        ]);
    }

    public function store(RecordManualCafcInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $range = SinCafcRange::query()->findOrFail($data['cafc_range_id']);
        $point = SinPointOfSale::query()->findOrFail($data['sin_point_of_sale_id']);
        $event = SinSignificantEvent::query()->findOrFail($data['significant_event_id']);
        $issuedAt = CarbonImmutable::parse($data['issued_manually_at']);

        $manual = $data['operation'] === 'cancelled'
            ? $this->cafc->recordCancelled($range, $point, (int) $data['manual_invoice_number'], $issuedAt, $request->user(), $data['void_reason'], $event)
            : $this->cafc->recordUsed($range, $point, (int) $data['manual_invoice_number'], $issuedAt, $request->user(), $event);

        return $manual->manual_status === ManualContingencyInvoiceStatus::Cancelled
            ? back()->with('success', 'Número físico anulado y bloqueado para reutilización.')
            : redirect()->route('billing.manual-cafc.transcribe.edit', $manual)->with('success', 'Número físico registrado. Complete la transcripción.');
    }

    public function edit(SinManualContingencyInvoice $manualInvoice): View
    {
        abort_unless($manualInvoice->manual_status === ManualContingencyInvoiceStatus::PendingTranscription, 404);

        return view('billing.manual-cafc.transcribe', [
            'manual' => $manualInvoice->load(['cafcRange', 'pointOfSale.branch', 'significantEvent']),
            'customers' => Customer::query()->active()->orderBy('name')->get(),
            'products' => Product::query()->active()->orderBy('description')->get(),
        ]);
    }

    public function update(TranscribeManualCafcInvoiceRequest $request, SinManualContingencyInvoice $manualInvoice): RedirectResponse
    {
        $customer = Customer::query()->findOrFail((int) $request->validated('customer_id'));
        $manual = $this->cafc->transcribe($manualInvoice, $customer, $request->safe()->except(['items', 'document_image']), $request->validated('items'), $request->user(), $request->file('document_image'));
        if (in_array($manual->significantEvent?->event_status, [SignificantEventStatus::Registered, SignificantEventStatus::Packaging, SignificantEventStatus::Sending, SignificantEventStatus::Validating], true)) {
            SendManualCafcInvoiceJob::dispatch((int) $manual->company_id, (int) $manual->id, (int) $request->user()->id);
        }

        return redirect()->route('billing.manual-cafc.index')->with('success', 'Factura transcrita y XML guardado. El envío iniciará cuando el evento esté registrado ante el SIAT.');
    }

    public function send(SinManualContingencyInvoice $manualInvoice): RedirectResponse
    {
        SendManualCafcInvoiceJob::dispatch((int) $manualInvoice->company_id, (int) $manualInvoice->id, (int) request()->user()->id);

        return back()->with('success', 'Reintento de envío encolado usando la misma factura y XML.');
    }
}
