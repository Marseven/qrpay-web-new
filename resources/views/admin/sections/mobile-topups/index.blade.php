@extends('admin.layouts.master')

@push('css')

@endpush

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __("Mobile Topup Logs")])
@endsection

@section('content')
<div class="table-area">
    <div class="table-wrapper">
        <div class="table-header">
            <h5 class="title">{{ $page_title }}</h5>
        </div>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>{{ __("TRX") }}</th>
                        <th>{{ __("Fullname") }}</th>
                        <th>{{ __("User Type") }}</th>
                        <th>{{ __("Topup Type") }}</th>
                        <th>{{ __("Mobile Number") }}</th>
                        <th>{{ __("Topup Amount") }}</th>
                        <th>{{ __(("Status")) }}</th>
                        <th>{{ __("Time") }}</th>
                        <th>{{ __("Action") }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions  as $key => $item)

                        <tr>
                            <td>{{ $item->trx_id }}</td>

                            <td>
                                @if($item->user_id != null)
                                <a href="{{ setRoute('admin.users.details',$item->creator->username) }}">{{ $item->creator->fullname }}</a>
                                @elseif($item->agent_id != null)
                                <a href="{{ setRoute('admin.agents.details',$item->creator->username) }}">{{ $item->creator->fullname }}</a>
                                @endif
                            </td>
                            <td>
                                @if($item->user_id != null)
                                     {{ __("USER") }}
                                @elseif($item->agent_id != null)
                                     {{ __("AGENT") }}
                                @elseif($item->merchant_id != null)
                                     {{ __("MERCHANT") }}
                                @endif

                            </td>



                            <td ><span class="fw-bold">{{ @$item->details->topup_type_name }}</span></td>
                            <td ><span class="fw-bold">{{ @$item->details->mobile_number }}</span></td>
                            <td>{{ number_format($item->request_amount,2) }} {{ get_default_currency_code() }}</td>
                            <td>
                                <span class="{{ $item->stringStatus->class }}">{{ $item->stringStatus->value }}</span>
                            </td>
                            <td>{{ $item->created_at->format('d-m-y h:i:s A') }}</td>
                            <td>
                                @include('admin.components.link.info-default',[
                                    'href'          => setRoute('admin.mobile.topup.details', $item->id),
                                    'permission'    => "admin.mobile.topup.details",
                                ])

                            </td>
                        </tr>
                    @empty
                        <div class="alert alert-primary">{{ __('No data found!') }}</div>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ get_paginate($transactions) }}
    </div>
</div>
@endsection

@push('script')

@endpush
