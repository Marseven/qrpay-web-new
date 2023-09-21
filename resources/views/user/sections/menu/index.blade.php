@extends('user.layouts.master')

@push('css')
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('menu.index'),
            ],
        ],
        'active' => __('Dashboard'),
    ])
@endsection

@section('content')
<div class="body-wrapper">
    <h1 style="color: royalblue">Menu du jour {{setRoute('menu.index')}}</h1>
</div>
@endsection