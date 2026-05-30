@extends('layouts.admin')

@section('title', 'Nuevo registro | '.config('app.name', 'Base Admin'))
@section('page-title', 'Nuevo '.$metadata['singular'])
@section('page-subtitle', $metadata['label'])

@section('content')
    <x-ui.card :title="$metadata['singular']">
        <div class="card-body">
            @include('admin.accommodation-catalogs.partials.create-form')
        </div>
    </x-ui.card>
@endsection
