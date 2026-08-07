<?php

namespace App\Http\Controllers\Web\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\RegisterPointOfSaleSignificantEventRequest;
use App\Http\Requests\Billing\RegisterSignificantEventRequest;
use App\Models\SinCatalogItem;
use App\Models\SinInvoiceIssue;
use App\Models\SinPointOfSale;
use App\Models\SinSignificantEvent;
use App\Services\Siat\SignificantEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SignificantEventController extends Controller
{
    public function __construct(private readonly SignificantEventService $events) {}

    public function create(SinInvoiceIssue $invoice): View
    {
        abort_unless($invoice->allowsSignificantEvent(), 404);

        $invoice->load(['pointOfSale.branch', 'significantEvents' => fn ($query) => $query->latest()]);

        return view('billing.significant-events.create', [
            'invoice' => $invoice,
            'pointOfSale' => $invoice->pointOfSale,
            'registeredEvents' => $invoice->significantEvents,
            'events' => SinCatalogItem::query()
                ->where('catalog_key', 'eventos_significativos')
                ->active()
                ->orderByRaw("nullif(classifier_code, '')::integer nulls last")
                ->get(),
        ]);
    }

    public function createForPointOfSale(int $pointOfSale): View
    {
        $point = SinPointOfSale::query()->with('branch')->findOrFail($pointOfSale);

        return view('billing.significant-events.create', [
            'invoice' => null,
            'pointOfSale' => $point,
            'registeredEvents' => SinSignificantEvent::query()
                ->whereNull('sin_invoice_issue_id')
                ->whereHas('cufd', fn ($query) => $query->where('sin_point_of_sale_id', $point->id))
                ->latest()
                ->get(),
            'events' => SinCatalogItem::query()
                ->where('catalog_key', 'eventos_significativos')
                ->active()
                ->orderByRaw("nullif(classifier_code, '')::integer nulls last")
                ->get(),
        ]);
    }

    public function store(RegisterSignificantEventRequest $request, SinInvoiceIssue $invoice): RedirectResponse
    {
        $event = $this->events->register($request->user(), $invoice, $request->validated());

        return redirect()
            ->route('billing.significant-events.create', $invoice)
            ->with($event->transaccion ? 'success' : 'error', $event->message);
    }

    public function storeForPointOfSale(RegisterPointOfSaleSignificantEventRequest $request): RedirectResponse
    {
        $point = SinPointOfSale::query()->with('branch')->findOrFail((int) $request->validated('sin_point_of_sale_id'));
        $event = $this->events->registerForPointOfSale($request->user(), $point, $request->validated());

        return redirect()
            ->route('billing.significant-events.point-of-sale.create', $point)
            ->with($event->transaccion ? 'success' : 'error', $event->message);
    }
}
