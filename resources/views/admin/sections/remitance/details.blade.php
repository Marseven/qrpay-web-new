@extends('admin.layouts.master')

@push('css')
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __("Remittance Details")])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
        ],
        'active' => __('Remittance  Details'),
    ])
@endsection

@section('content')

<div class="custom-card">
    <div class="card-header">
        <h6 class="title">{{ __($page_title) }}</h6>
    </div>
    <div class="card-body">
        <form class="card-form">
            <div class="row align-items-center mb-10-none">
                <div class="col-xl-4 col-lg-4 form-group">
                    <ul class="user-profile-list-two">
                        <li class="one">Date: <span>{{ @$data->created_at->format('d-m-y h:i:s A') }}</span></li>
                        <li class="two">TRX ID: <span>{{ @$data->trx_id }}</span></li>
                        <li class="three">Sender:
                            @if($data->attribute == "SEND")
                            <a href="{{ setRoute('admin.users.details',$data->user->username) }}">{{ $data->user->fullname }} ({{ @$data->user->full_mobile }})</a>
                            @else
                            <span>{{ $data->details->sender->fullname }} ({{ @$data->user->full_mobile }})</span>
                            @endif
                        </li>
                        <li class="four">Receiver:
                            @if($data->attribute == "RECEIVED")
                            <a href="{{ setRoute('admin.users.details',$data->user->username) }}">{{ $data->user->fullname }}({{ @$data->user->full_mobile }})</a>
                            @else
                            <span>{{ @$data->details->receiver->firstname }} {{ @$data->details->receiver->lastname }}({{@$data->details->receiver->mobile_code.@$data->details->receiver->mobile }})</span>
                            @endif
                        </li>
                        <li class="five">Sending Country: <span class="fw-bold">{{ @$data->details->form_country }}</span></li>
                        <li class="five">Receiving Country: <span class="fw-bold">{{ @$data->details->to_country->country }}</span></li>
                        <li class="one">Remittance Type:
                            @if( @$data->details->remitance_type == "wallet-to-wallet-transfer")
                                <span class="fw-bold"> {{@$basic_settings->site_name}} {{__("Wallet")}} </span>
                                @else
                                <span class="fw-bold"> {{ ucwords(str_replace('-', ' ', @$data->details->remitance_type))}} </span>

                            @endif

                            </li>

                    </ul>
                </div>

                <div class="col-xl-4 col-lg-4 form-group">
                    <div class="user-profile-thumb">
                        <img src="{{  @$data->user->userImage }}" alt="payment">
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 form-group">
                    <ul class="user-profile-list two">

                        @if( @$data->details->remitance_type == "bank-transfer")
                        <li class="one">Bank Name:
                            <span class="text-base"> {{ ucwords(str_replace('-', ' ', @$data->details->receiver->alias))}}</span>
                        </li>
                        @endif
                        @if( @$data->details->remitance_type == "cash-pickup")
                        <li class="one">Pickup Point:
                            <span class="text-base"> {{ ucwords(str_replace('-', ' ', @$data->details->receiver->alias))}}</span>
                        </li>
                        @endif
                        <li class="three">Exchange Rate:
                            <span>1 {{ get_default_currency_code() }} = {{ get_amount($data->details->to_country->rate,$data->details->to_country->code) }}
                        </li>
                        <li class="four">Send Amount: <span>{{ get_amount($data->request_amount,get_default_currency_code()) }}</span></li>
                        <li class="two">Charge: <span>{{ get_amount($data->charge->total_charge,get_default_currency_code()) }}</span></li>
                        <li class="three">Payable Amount: <span>{{ get_amount($data->payable,get_default_currency_code()) }}</span></li>
                        <li class="three">Receipient Get: <span>{{ number_format(@$data->details->recipient_amount,2)}} {{ $data->details->to_country->code }}</span></li>
                        <li class="four">Status:  <span class="{{ @$data->stringStatus->class }}">{{ @$data->stringStatus->value }}</span></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

@if(@$data->status == 2)
<div class="custom-card mt-15">
    <div class="card-body">
        <div class="product-sales-btn">
            <button type="button" class="btn btn--base approvedBtn">Approve</button>
            <button type="button" class="btn btn--danger rejectBtn" >Reject</button>
        </div>
    </div>
</div>

<div class="modal fade" id="approvedModal" tabindex="-1" >
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header p-3" id="approvedModalLabel">
                <h5 class="modal-title">Approved Confirmation ( <span class="fw-bold text-danger">{{ number_format(@$data->request_amount,2) }} {{ get_default_currency_code() }}</span> )</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="modal-form" action="{{ setRoute('admin.remitance.approved') }}" method="POST">

                    @csrf
                    @method("PUT")
                    <div class="row mb-10-none">
                        <div class="col-xl-12 col-lg-12 form-group">
                            <input type="hidden" name="id" value={{ @$data->id }}>
                           <p>Are you sure to approved this request?</p>
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn--base btn-loading ">Approved</button>
            </div>
        </form>
        </div>
    </div>
</div>
<div class="modal fade" id="rejectModal" tabindex="-1" >
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header p-3" id="rejectModalLabel">
                <h5 class="modal-title">Rejection Confirmation ( <span class="fw-bold text-danger">{{ number_format(@$data->request_amount,2) }} {{ get_default_currency_code() }}</span> )</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="modal-form" action="{{ setRoute('admin.remitance.rejected') }}" method="POST">
                    @csrf
                    @method("PUT")
                    <div class="row mb-10-none">
                        <div class="col-xl-12 col-lg-12 form-group">
                            <input type="hidden" name="id" value={{ @$data->id }}>
                            @include('admin.components.form.textarea',[
                                'label'         => 'Explain Rejection Reason*',
                                'name'          => 'reject_reason',
                                'value'         => old('reject_reason')
                            ])
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--danger" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn--base">Confirm</button>
            </div>
        </form>
        </div>
    </div>
</div>
@endif


@endsection


@push('script')
<script>
    $(document).ready(function(){
        @if($errors->any())
        var modal = $('#rejectModal');
        modal.modal('show');
        @endif
    });
</script>
<script>
     (function ($) {
        "use strict";
        $('.approvedBtn').on('click', function () {
            var modal = $('#approvedModal');
            modal.modal('show');
        });
        $('.rejectBtn').on('click', function () {
            var modal = $('#rejectModal');
            modal.modal('show');
        });
    })(jQuery);





</script>
@endpush
