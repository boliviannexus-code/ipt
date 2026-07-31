<?php

namespace App\Http\Controllers\Web\Billing;

use App\Http\Controllers\Controller;
use App\Models\SinInvoiceIssue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = SinInvoiceIssue::query()
            ->with(['customer', 'pointOfSale.branch'])
            ->when($request->filled('status'), function ($query) use ($request): void {
                $query->where('status_label', $request->string('status'));
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('invoice_number', is_numeric($search) ? (int) $search : 0)
                        ->orWhere('attempted_invoice_number', is_numeric($search) ? (int) $search : 0)
                        ->orWhere('cuf', 'ilike', "%{$search}%")
                        ->orWhere('reception_code', 'ilike', "%{$search}%")
                        ->orWhereHas('customer', function ($query) use ($search): void {
                            $query
                                ->where('name', 'ilike', "%{$search}%")
                                ->orWhere('document_number', 'ilike', "%{$search}%");
                        });
                });
            })
            ->latest('issued_at')
            ->paginate(15)
            ->withQueryString();

        return view('billing.invoices.index', [
            'invoices' => $invoices,
            'statuses' => SinInvoiceIssue::query()
                ->select('status_label')
                ->distinct()
                ->orderBy('status_label')
                ->pluck('status_label'),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }
}
