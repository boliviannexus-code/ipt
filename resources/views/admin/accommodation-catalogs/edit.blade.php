@extends('layouts.admin')

@section('title', 'Editar registro | '.config('app.name', 'Base Admin'))
@section('page-title', 'Editar '.$metadata['singular'])
@section('page-subtitle', $metadata['label'])

@section('content')
    <x-ui.card :title="$metadata['singular']">
        <div class="card-body">
            @include('admin.accommodation-catalogs.partials.edit-form')
        </div>
    </x-ui.card>
@endsection
