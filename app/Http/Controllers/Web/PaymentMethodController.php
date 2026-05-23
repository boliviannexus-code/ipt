<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('payment-methods.view'), 403);

        return view('payment-methods.index');
    }

    public function create(Request $request): View
    {
        abort_unless(auth()->user()?->can('payment-methods.create'), 403);

        return view($request->ajax() ? 'payment-methods.partials.create-form' : 'payment-methods.create');
    }

    public function store(StorePaymentMethodRequest $request): JsonResponse|RedirectResponse
    {
        $paymentMethod = PaymentMethod::query()->create(CompanyContext::applyToData($request->validated(), $request->user()));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Metodo de pago creado correctamente.',
                'data' => ['id' => $paymentMethod->id],
            ], 201);
        }

        return redirect()->route('payment-methods.index')->with('success', 'Metodo de pago creado correctamente.');
    }

    public function show(Request $request, PaymentMethod $paymentMethod): View
    {
        abort_unless(auth()->user()?->can('payment-methods.view'), 403);
        abort_unless(CompanyContext::belongsToUser($paymentMethod->company_id, $request->user()), 403);

        return view($request->ajax() ? 'payment-methods.partials.show' : 'payment-methods.show', compact('paymentMethod'));
    }

    public function edit(Request $request, PaymentMethod $paymentMethod): View
    {
        abort_unless(auth()->user()?->can('payment-methods.update'), 403);
        abort_unless(CompanyContext::belongsToUser($paymentMethod->company_id, $request->user()), 403);

        return view($request->ajax() ? 'payment-methods.partials.edit-form' : 'payment-methods.edit', compact('paymentMethod'));
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): JsonResponse|RedirectResponse
    {
        abort_unless(CompanyContext::belongsToUser($paymentMethod->company_id, $request->user()), 403);

        $paymentMethod->update(CompanyContext::applyToData($request->validated(), $request->user()));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Metodo de pago actualizado correctamente.',
                'data' => ['id' => $paymentMethod->id],
            ]);
        }

        return redirect()->route('payment-methods.index')->with('success', 'Metodo de pago actualizado correctamente.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        abort_unless(auth()->user()?->can('payment-methods.delete'), 403);
        abort_unless(CompanyContext::belongsToUser($paymentMethod->company_id, auth()->user()), 403);

        abort_if($paymentMethod->salePayments()->exists(), 422, 'No se puede eliminar un metodo de pago con ventas asociadas.');

        $paymentMethod->delete();

        return redirect()->route('payment-methods.index')->with('success', 'Metodo de pago eliminado correctamente.');
    }
}
