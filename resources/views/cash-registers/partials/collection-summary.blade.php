<div class="mb-3">
    <x-ui.table-card title="Resumen de cobros realizados">
        <table class="table align-middle mb-0">
            <thead><tr><th>Método</th><th class="text-end">Cobros</th><th class="text-end">Dinero recibido</th><th class="text-end">Aplicado al cobrar</th><th class="text-end">Anticipos recibidos</th></tr></thead>
            <tbody>
                @foreach ($paymentMethodLabels as $code => $label)
                    @php
                        $payments = $register->accountPayments->where('payment_method_code', $code);
                        $received = $payments->sum(fn ($payment) => (float) $payment->amount);
                        $advanced = $payments->sum(fn ($payment) => (float) $payment->credit_amount);
                    @endphp
                    <tr><td>{{ $label }}</td><td class="text-end">{{ $payments->count() }}</td><td class="text-end">Bs {{ money_format_decimal($received) }}</td><td class="text-end">Bs {{ money_format_decimal($received - $advanced) }}</td><td class="text-end">Bs {{ money_format_decimal($advanced) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr><th>Total</th><th class="text-end">{{ $register->accountPayments->count() }}</th><th class="text-end">Bs {{ money_format_decimal($register->accountPayments->sum('amount')) }}</th><th class="text-end">Bs {{ money_format_decimal($register->accountPayments->sum('amount') - $register->accountPayments->sum('credit_amount')) }}</th><th class="text-end">Bs {{ money_format_decimal($register->accountPayments->sum('credit_amount')) }}</th></tr></tfoot>
        </table>
        <x-slot:footer>Saldo a favor utilizado en esta caja: <strong>Bs {{ money_format_decimal($register->creditApplications->sum('amount')) }}</strong>. Este uso no se suma al dinero recibido.</x-slot:footer>
    </x-ui.table-card>
</div>
