@extends('layouts.admin')

@section('title', 'Evento significativo | '.config('app.name', 'Base Admin'))
@section('page-title', 'Registrar evento significativo')
@section('page-subtitle', $invoice ? 'Contingencia del intento de factura Nro. '.$invoice->attempted_invoice_number : 'Contingencia detectada en el punto de venta seleccionado')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-9">
            <div class="alert alert-warning" role="alert">
                <h2 class="h5 mb-1">Factura no enviada por falta de conexion con el SIN</h2>
                <p class="mb-0">
                    Sucursal {{ $pointOfSale->branch->branch_code }}, punto de venta {{ $pointOfSale->point_of_sale_code }}.
                    Registra el periodo real de la contingencia; esta operacion usa el servicio de Operaciones del SIAT.
                </p>
            </div>

            @if ($events->isEmpty())
                <div class="alert alert-danger" role="alert">
                    No existen eventos significativos sincronizados. Sincroniza primero el catalogo
                    <strong>Eventos significativos</strong> desde Parametricas SIAT.
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">Datos del evento</h3></div>
                <form method="POST" action="{{ $invoice ? route('billing.significant-events.store', $invoice) : route('billing.significant-events.point-of-sale.store') }}">
                    @csrf
                    @unless($invoice)
                        <input type="hidden" name="sin_point_of_sale_id" value="{{ $pointOfSale->id }}">
                    @endunless
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="event-code">Evento significativo</label>
                            <select class="form-select @error('event_code') is-invalid @enderror" id="event-code" name="event_code" required @disabled($events->isEmpty())>
                                <option value="">Seleccionar evento</option>
                                @foreach ($events as $event)
                                    <option value="{{ $event->classifier_code }}" @selected(old('event_code') == $event->classifier_code)>
                                        {{ $event->classifier_code }} - {{ $event->description }}
                                    </option>
                                @endforeach
                            </select>
                            @error('event_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="event-description">Descripcion de la contingencia</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="event-description" name="description" maxlength="500" rows="3" required>{{ old('description', 'Falla de comunicacion con los servicios del SIN durante la emision de factura.') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="event-started-at">Inicio del evento</label>
                                <input class="form-control @error('started_at') is-invalid @enderror" id="event-started-at" name="started_at" type="datetime-local" value="{{ old('started_at', $invoice?->issued_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}" required>
                                @error('started_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="event-ended-at">Fin del evento</label>
                                <input class="form-control @error('ended_at') is-invalid @enderror" id="event-ended-at" name="ended_at" type="datetime-local" value="{{ old('ended_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required>
                                @error('ended_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a class="btn btn-outline-secondary" href="{{ route('billing.invoices.issue.show', 1) }}">Volver a facturacion</a>
                        <button class="btn btn-primary" type="submit" @disabled($events->isEmpty() || ($invoice && $registeredEvents->contains('transaccion', true)))>
                            Registrar en el SIN
                        </button>
                    </div>
                </form>
            </div>

            @if ($registeredEvents->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Intentos de registro</h3></div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter">
                            <thead><tr><th>Evento</th><th>Periodo</th><th>Estado</th><th>Codigo recepcion</th><th>Mensaje</th></tr></thead>
                            <tbody>
                                @foreach ($registeredEvents as $registeredEvent)
                                    <tr>
                                        <td>{{ $registeredEvent->event_code }} - {{ $registeredEvent->event_description }}</td>
                                        <td>{{ $registeredEvent->started_at?->format('d/m/Y H:i') }}<br>{{ $registeredEvent->ended_at?->format('d/m/Y H:i') }}</td>
                                        <td>{{ $registeredEvent->status_label }}</td>
                                        <td>{{ $registeredEvent->reception_code ?: '-' }}</td>
                                        <td>{{ $registeredEvent->message }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
