@extends('user.layouts.master')

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
    // javascript code
</script>
@endpush