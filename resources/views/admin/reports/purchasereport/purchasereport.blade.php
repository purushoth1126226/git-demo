@extends('components.admin.layouts.adminapp')
@section('headSection')
@endsection

@section('adminnavbar')
    <x-admin.layouts.adminnavbar modulename="{{ __('REPORTS') }}" />
@endsection

@section('main-content')
    <x-admin.layouts.adminbreadcrumb>
        <li class="breadcrumb-item"><a class="text-decoration-none" href="{{ route('adminreports') }}">{{ __('Reports') }}</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Purchase Reports') }}</li>
    </x-admin.layouts.adminbreadcrumb>


    @include('admin.reports.purchasereport.partial', ['name' => 'purchasereport', 'col' => 'col-sm-6'])

    @livewire('admin.reports.purchasereport.purchasereportlivewire')
@endsection
@section('footerSection')
    @include('helper.sidenavhelper.sidenavactive', [
        'type' => 1,
        'nameone' => '#adminreports_sidenav',
    ])
@endsection