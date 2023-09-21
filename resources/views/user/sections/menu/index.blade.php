@extends('user.layouts.master')

@push('css')
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => "menu",
            ],
        ],
        'active' => __('Dashboard'),
    ])
@endsection

@section('content')
<div class="body-wrapper">
    <h1 style="color: royalblue">Menu du jour</h1>
</div>
@endsection