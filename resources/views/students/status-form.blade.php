@extends('layouts.admin')
@section('title', 'Cambiar estado de matrícula')
@section('page-title', 'Cambiar estado de matrícula')
@section('content')
<x-ui.card title="Estado de matrícula"><div class="card-body">@include('students.partials.status-form')</div></x-ui.card>
@endsection
