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
    ], 'active' => __("Bill Pay Logs")])
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
                        <th>{{ __("Sender") }}</th>
                        <th>{{ __("Receiver") }}</th>
                        <th>{{ __("Remittance Type") }}</th>
                        <th>{{ __(("Send Amount")) }}</th>
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
                                @if($item->attribute == "SEND")
                                <a href="{{ setRoute('admin.users.details',$item->user->username) }}">{{ $item->user->fullname }}</a>
                                @else
                                <span>{{ $item->details->sender->fullname }}</span>
                                @endif

                            </td>
                            <td>
                                @if($item->attribute == "RECEIVED")
                                <a href="{{ setRoute('admin.users.details',$item->user->username) }}">{{ $item->user->fullname }}</a>
                                @else
                                <span>{{ @$item->details->receiver->firstname }} {{ @$item->details->receiver->lastname }}</span>
                                @endif

                            </td>
                            <td >
                                @if( @$item->details->remitance_type == "wallet-to-wallet-transfer")
                                    <span class="fw-bold"> {{@$basic_settings->site_name}} {{__("Wallet")}} </span>
                                    @else
                                    <span class="fw-bold"> {{ ucwords(str_replace('-', ' ', @$item->details->remitance_type))}} </span>

                                @endif
                               </td>
                            {{-- <td ><span class="fw-bold">{{ @$item->details->bill_number }}</span></td> --}}
                            <td>{{ number_format($item->request_amount,2) }} {{ get_default_currency_code() }}</td>
                            <td>
                                <span class="{{ $item->stringStatus->class }}">{{ $item->stringStatus->value }}</span>
                            </td>
                            <td>{{ $item->created_at->format('d-m-y h:i:s A') }}</td>
                            <td>
                                @include('admin.components.link.info-default',[
                                    'href'          => setRoute('admin.remitance.details', $item->id),
                                    'permission'    => "admin.remitance.details",
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
