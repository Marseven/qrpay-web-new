@extends('user.layouts.master')

@push('css')

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
        <div class="dashboard-header-wrapper">
            <h3 class="title">{{__(@$page_title)}}</h3>
        </div>
    </div>
    <div class="row mb-30-none">
        <div class="col-xl-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __("Recharge") }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <form class="card-form" action="{{ setRoute('user.mobile.topup.confirm') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 form-group text-center">
                                    <div class="exchange-area">
                                        <code class="d-block text-center"><span class="fees-show">--</span> <span class="limit-show">--</span></code>
                                    </div>
                                </div>
                                <div class="col-xl-6 col-lg-6  form-group">
                                    <label>{{ __("Mobile TopUp") }} <span class="text--base">*</span></label>
                                    <select class="form--control" name="topup_type">
                                        @foreach ($topupType ??[] as $type)
                                        <option value="{{ $type->id }}" data-name="{{ $type->name }}">{{ $type->name }}</option>
                                        @endforeach

                                    </select>
                                </div>
                                <div class="col-xl-6 col-lg-6  form-group">
                                    <label>Mobile Number <span class="text--base">*</span></label>
                                    <input type="number" class="form--control" name="mobile_number" placeholder="Enter Mobile Number" value="{{ old('mobile_number') }}">

                                </div>

                                <div class="col-xxl-12 col-xl-12 col-lg-12  form-group">
                                    <label>{{ __("Amount") }}<span>*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form--control" required placeholder="Enter Amount" name="amount" value="{{ old("amount") }}">
                                        <select class="form--control nice-select currency" name="currency">
                                            <option value="{{ get_default_currency_code() }}">{{ get_default_currency_code() }}</option>
                                        </select>
                                    </div>

                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                    <div class="note-area">
                                        <code class="d-block fw-bold">{{ __("Available Balance") }}: {{ authWalletBalance() }} {{ get_default_currency_code() }}</code>
                                    </div>
                                </div>

                                <div class="col-xl-12 col-lg-12">
                                    <button type="submit" class="btn--base w-100 btn-loading mobileTopupBtn">{{ __("Recharge Now") }} <i class="fas fa-mobile ms-1"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __("Preview") }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <div class="preview-list-wrapper">
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-plug"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("TopUp Type") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="topup-type">--</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-phone-volume"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Mobile Number") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="mobile-number"></span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-funnel-dollar"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Amount") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="request-amount">--</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-battery-half"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Total Charge") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="fees">--</span>
                                </div>
                            </div>

                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-money-check-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Total Payable") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--base last payable-total">--</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="dashboard-list-area mt-20">
        <div class="dashboard-header-wrapper">
            <h4 class="title ">{{__("Mobile Topup Log")}}</h4>
            <div class="dashboard-btn-wrapper">
                <div class="dashboard-btn mb-2">
                    <a href="{{ setRoute('user.transactions.index','mobile-topup') }}" class="btn--base">{{__("View More")}}</a>
                </div>
            </div>
        </div>
        <div class="dashboard-list-wrapper">
            @include('user.components.transaction-log',compact("transactions"))
        </div>
    </div>
</div>
<div class="modal fade" id="scanModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
            <div class="modal-body text-center">
                <video id="preview" class="p-1 border" style="width:300px;"></video>
            </div>
            <div class="modal-footer justify-content-center">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">@lang('close')</button>
            </div>
      </div>
    </div>
</div>
@endsection

@push('script')
<script>
    var defualCurrency = "{{ get_default_currency_code() }}";
    var defualCurrencyRate = "{{ get_default_currency_rate() }}";

   $(document).ready(function(){
           getLimit();
           getFees();
           getPreview();
       });
       $("input[name=amount]").keyup(function(){
            getFees();
            getPreview();
       });
       $("input[name=amount]").focusout(function(){
            enterLimit();
       });
       $("input[name=mobile_number]").keyup(function(){
            getFees();
            getPreview();
       });
       $("select[name=topup_type]").change(function(){
            getFees();
            getPreview();
       });
       function getLimit() {
           var currencyCode = acceptVar().currencyCode;
           var currencyRate = acceptVar().currencyRate;

           var min_limit = acceptVar().currencyMinAmount;
           var max_limit =acceptVar().currencyMaxAmount;
           if($.isNumeric(min_limit) || $.isNumeric(max_limit)) {
               var min_limit_calc = parseFloat(min_limit/currencyRate).toFixed(2);
               var max_limit_clac = parseFloat(max_limit/currencyRate).toFixed(2);
               $('.limit-show').html("Limit " + min_limit_calc + " " + currencyCode + " - " + max_limit_clac + " " + currencyCode);

               return {
                   minLimit:min_limit_calc,
                   maxLimit:max_limit_clac,
               };
           }else {
               $('.limit-show').html("--");
               return {
                   minLimit:0,
                   maxLimit:0,
               };
           }
       }
       function acceptVar() {
           var selectedVal = $("select[name=currency] :selected");
           var currencyCode = $("select[name=currency] :selected").val();
           var currencyRate = defualCurrencyRate;
           var currencyMinAmount ="{{getAmount($topupCharge->min_limit)}}";
           var currencyMaxAmount = "{{getAmount($topupCharge->max_limit)}}";
           var currencyFixedCharge = "{{getAmount($topupCharge->fixed_charge)}}";
           var currencyPercentCharge = "{{getAmount($topupCharge->percent_charge)}}";
           var topUp = $("select[name=topup_type] :selected");
           var topUpname = $("select[name=topup_type] :selected").data("name");
           var mobileNumber = $("input[name=mobile_number]").val();

           return {
               currencyCode:currencyCode,
               currencyRate:currencyRate,
               currencyMinAmount:currencyMinAmount,
               currencyMaxAmount:currencyMaxAmount,
               currencyFixedCharge:currencyFixedCharge,
               currencyPercentCharge:currencyPercentCharge,
               topUpname:topUpname,
               mobileNumber:mobileNumber,
               topUp:topUp,
               selectedVal:selectedVal,

           };
       }
       function feesCalculation() {
           var currencyCode = acceptVar().currencyCode;
           var currencyRate = acceptVar().currencyRate;
           var sender_amount = $("input[name=amount]").val();
           sender_amount == "" ? (sender_amount = 0) : (sender_amount = sender_amount);

           var fixed_charge = acceptVar().currencyFixedCharge;
           var percent_charge = acceptVar().currencyPercentCharge;
           if ($.isNumeric(percent_charge) && $.isNumeric(fixed_charge) && $.isNumeric(sender_amount)) {
               // Process Calculation
               var fixed_charge_calc = parseFloat(currencyRate * fixed_charge);
               var percent_charge_calc = parseFloat(currencyRate)*(parseFloat(sender_amount) / 100) * parseFloat(percent_charge);
               var total_charge = parseFloat(fixed_charge_calc) + parseFloat(percent_charge_calc);
               total_charge = parseFloat(total_charge).toFixed(2);
               // return total_charge;
               return {
                   total: total_charge,
                   fixed: fixed_charge_calc,
                   percent: percent_charge,
               };
           } else {
               // return "--";
               return false;
           }
       }

       function getFees() {
           var currencyCode = acceptVar().currencyCode;
           var percent = acceptVar().currencyPercentCharge;
           var charges = feesCalculation();
           if (charges == false) {
               return false;
           }
           $(".fees-show").html("TopUp Fee: " + parseFloat(charges.fixed).toFixed(2) + " " + currencyCode + " + " + parseFloat(charges.percent).toFixed(2) + "%  ");
       }
       function getPreview() {
               var senderAmount = $("input[name=amount]").val();
               var sender_currency = acceptVar().currencyCode;
               var sender_currency_rate = acceptVar().currencyRate;
               var topup_type = acceptVar().topUpname;
               var mobile_number = acceptVar().mobileNumber;
               senderAmount == "" ? senderAmount = 0 : senderAmount = senderAmount;
               // Sending Amount
               $('.request-amount').text(senderAmount + " " + defualCurrency);
                //bill type
                $('.topup-type').text(topup_type);
               // Fees
                //bill number
                if(mobile_number == '' || mobile_number == 0){
                    $('.mobile-number').text("Ex: 1234567891");
                }else{
                    $('.mobile-number').text(mobile_number);
                }

               // Fees
               var charges = feesCalculation();
               var total_charge = 0;
               if(senderAmount == 0){
                   total_charge = 0;
               }else{
                   total_charge = charges.total;
               }

               $('.fees').text(total_charge + " " + sender_currency);

                // Pay In Total
               var totalPay = parseFloat(senderAmount) * parseFloat(sender_currency_rate)
               var pay_in_total = 0;
               if(senderAmount == 0){
                    pay_in_total = 0;
               }else{
                    pay_in_total =  parseFloat(totalPay) + parseFloat(charges.total);
               }
               $('.payable-total').text(parseFloat(pay_in_total).toFixed(2) + " " + sender_currency);

       }
       function enterLimit(){
        var min_limit = parseFloat("{{getAmount($topupCharge->min_limit)}}");
        var max_limit =parseFloat("{{getAmount($topupCharge->max_limit)}}");
        var currencyRate = acceptVar().currencyRate;
        var sender_amount = parseFloat($("input[name=amount]").val());

        if( sender_amount < min_limit ){
            throwMessage('error',["Please follow the mimimum limit"]);
            $('.mobileTopupBtn').attr('disabled',true)
        }else if(sender_amount > max_limit){
            throwMessage('error',["Please follow the maximum limit"]);
            $('.mobileTopupBtn').attr('disabled',true)
        }else{
            $('.mobileTopupBtn').attr('disabled',false)
        }

       }

</script>

@endpush
