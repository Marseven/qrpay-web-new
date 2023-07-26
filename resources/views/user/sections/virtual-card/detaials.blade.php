@extends('user.layouts.master')

@push('css')
<style>
.btn-ring {
    position: absolute;
   top: 0px;
    right: 0px;
}
.toggle-container .switch-toggles {
    position: relative;
    width: 235px;
    height: 35px;
    border-radius: 15px;
    background-color: #ffffff;
}
</style>
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __(@$page_title)])
@endsection

@section('content')
<div class="body-wrapper">
    <div class="dashboard-area mt-10">
        {{-- <div class="dashboard-header-wrapper">
            <h3 class="title">{{ __(@$page_title) }}</h3>
        </div> --}}
    </div>
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __(@$page_title) }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <div class="exchange-area-wrapper text-center">
                            <div class="exchange-area mb-20">
                                <code class="d-block text-center"><span>{{ __("Current Balance") }}</span> {{ getAmount(@$myCard->amount,2) }} {{ get_default_currency_code() }}</code>
                            </div>
                        </div>
                        <div class="preview-list-wrapper">
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-hourglass-end"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>Card Type</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--warning">{{ __((ucwords(@$myCard->card_type))) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-credit-card"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>Card Id</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ @$myCard->card_id }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-hand-holding-heart"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>Account Id</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ @$myCard->account_id }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-user-tag"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>Card Pan</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    @php
                                    $card_pan = str_split($myCard->card_pan, 4);
                                   @endphp
                                       @foreach($card_pan as $key => $value)
                                       <span>{{ @$value }}</span>
                                       @endforeach
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-truck-loading"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>Card Masked</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ @$myCard->masked_card }}</span>
                                </div>
                            </div>

                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-hourglass-start"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>Cvv</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ __(@$myCard->cvv) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-business-time"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>Validity</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{date("m/Y",strtotime(@$myCard->expiration)) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-business-time"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>Card Color</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ @$myCard->bg }}</span>
                                </div>
                            </div>

                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-business-time"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{__("Expiration")}}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{date("m/Y",strtotime(@$myCard->expiration)) }}</span>
                                </div>
                            </div>

                            {{-- <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-coins"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>Referance Id</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--base last">{{ @$myCard->ref_id }}</span>
                                </div>
                            </div> --}}

                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-city"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("City") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ __($myCard->city) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-city"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("State") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ __($myCard->state) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-city"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{__("Address")}}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ __($myCard->address) }}</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-file-archive"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{__("Zip Code")}}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span>{{ __($myCard->zip_code) }}</span>
                                </div>
                            </div>

                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-battery-half"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>Status</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <div class="toggle-container">
                                        {{-- <div class="switch-toggles btn-load two {{ @$myCard->is_active == 1 ?'active':'' }}">
                                            <input type="hidden" name="is_active" value="{{ @$myCard->is_active }}" data-target="{{ @$myCard->id }}">
                                            <span class="switch btn-loading event-ready" data-value="1">Unblock</span>
                                            <span class="switch btn-loading event-ready" data-value="0">Block</span>

                                        </div> --}}
                                        @include('admin.components.form.switcher',[

                                            'name'          => 'is_active',
                                            'value'         => old('is_active',@$myCard->is_active ),
                                            'options'       => ['Unblock' => 1,'Block' => 0],
                                            'onload'        => true,
                                            'data_target'   =>@$myCard->id,
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
     $(document).ready(function(){
        switcherAjax("{{ setRoute('user.virtual.card.change.status') }}");
    })

</script>
@endpush
