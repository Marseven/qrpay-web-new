@extends('user.layouts.master')

@push('css')
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('user.dashboard'),
            ],
        ],
        'active' => __('menu'),
    ])
@endsection

@section('content')
<div class="body-wrapper">
    
</div>
@endsection

@push('script')
    <script>
        alert("hello")
    </script>
@endpush