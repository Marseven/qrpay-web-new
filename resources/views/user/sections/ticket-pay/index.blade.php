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
        'active' => __(@$page_title),
    ])
@endsection

@section('content')
    <div class="body-wrapper">
        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{ __(@$page_title) }}</h3>
            </div>
        </div>
        <div class="row mb-30-none">
            <div class="col-xl-6 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area">
                            <span class="dash-payment-badge">!</span>
                            <h5 class="title">{{ __('Ticket Pay Form') }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <form class="card-form" action="{{ setRoute('user.ticket.pay.confirm') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-xl-12 col-lg-12 form-group text-center">
                                        <div class="exchange-area">
                                            <code class="d-block text-center"><span class="fees-show">--</span> <span
                                                    class="limit-show">--</span></code>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6  form-group">
                                        <label>{{ __('Ticket Type') }} <span class="text--base">*</span></label>
                                        <select class="form--control" name="ticket_type">
                                            @foreach ($ticketType ?? [] as $type)
                                                <option value="{{ $type->id }}" data-name="{{ $type->label }}">
                                                    {{ $type->label }}</option>
                                            @endforeach

                                        </select>
                                        @foreach ($ticketType ?? [] as $type)
                                            <input type="hidden" name="ticket_price_{{ $type->id }}"
                                                value="{{ $type->price }}" disabled>
                                        @endforeach

                                    </div>

                                    <div class="col-xl-6 col-lg-6  form-group">
                                        <label>Nombre de Ticket <span class="text--base">*</span></label>
                                        <input type="number" class="form--control" required name="ticket_number"
                                            placeholder="Enter le nombre de ticket" value="{{ old('ticket_number') }}">

                                    </div>

                                    <div class="col-xxl-12 col-xl-12 col-lg-12  form-group" style="display: none">
                                        <label>{{ __('Amount') }}<span>*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form--control" placeholder="0" name="amount"
                                                value="{{ old('amount') }}">
                                            <select class="form--control nice-select currency" name="currency">
                                                <option value="{{ get_default_currency_code() }}">
                                                    {{ get_default_currency_code() }}</option>
                                            </select>
                                        </div>

                                    </div>

                                    <div class="col-xl-12 col-lg-12 form-group">
                                        <div class="note-area">
                                            <code class="d-block fw-bold">{{ __('Available Balance') }}:
                                                {{ authWalletBalance() }} {{ get_default_currency_code() }}</code>
                                        </div>
                                    </div>

                                    <div class="col-xl-12 col-lg-12">
                                        <button type="submit"
                                            class="btn--base w-100 btn-loading ticketPayBtn">{{ __('Pay Ticket') }} <i
                                                class="fas fa-coins ms-1"></i></button>
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
                            <h5 class="title">{{ __('Preview') }}</h5>
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
                                                <span>{{ __('Ticket Pay') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="ticket-type">--</span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-list-ol"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Ticket Number') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="preview-list-right">
                                        <span class="ticket-number"></span>
                                    </div>
                                </div>
                                <div class="preview-list-item">
                                    <div class="preview-list-left">
                                        <div class="preview-list-user-wrapper">
                                            <div class="preview-list-user-icon">
                                                <i class="las la-funnel-dollar"></i>
                                            </div>
                                            <div class="preview-list-user-content">
                                                <span>{{ __('Amount') }}</span>
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
                                                <span>{{ __('Total Charge') }}</span>
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
                                                <span>{{ __('Total Payable') }}</span>
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
                <h4 class="title ">{{ __('Ticket Pay Log') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn mb-2">
                        <a href="{{ setRoute('user.transactions.index', 'ticket-pay') }}"
                            class="btn--base">{{ __('View More') }}</a>
                    </div>
                </div>
            </div>
            <div class="dashboard-list-wrapper">
                @include('user.components.transaction-log', compact('transactions'))
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
                    <button type="button" class="btn btn-secondary btn-sm"
                        data-bs-dismiss="modal">@lang('close')</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        var defualCurrency = "{{ get_default_currency_code() }}";
        var defualCurrencyRate = "{{ get_default_currency_rate() }}";

        $(document).ready(function() {
            getLimit();
            getFees();
            getPreview();
        });

        $("input[name=ticket_number]").keyup(function() {
            getFees();
            getPreview();
            enterLimit();
        });
        $("select[name=ticket_type]").change(function() {
            getFees();
            getPreview();
        });

        function getLimit() {
            var currencyCode = acceptVar().currencyCode;
            var currencyRate = acceptVar().currencyRate;

            var min_limit = acceptVar().currencyMinAmount;
            var max_limit = acceptVar().currencyMaxAmount;
            if ($.isNumeric(min_limit) || $.isNumeric(max_limit)) {
                var min_limit_calc = parseFloat(min_limit / currencyRate).toFixed(2);
                var max_limit_clac = parseFloat(max_limit / currencyRate).toFixed(2);
                $('.limit-show').html("Limit " + min_limit_calc + " " + currencyCode + " - " + max_limit_clac + " " +
                    currencyCode);

                return {
                    minLimit: min_limit_calc,
                    maxLimit: max_limit_clac,
                };
            } else {
                $('.limit-show').html("--");
                return {
                    minLimit: 0,
                    maxLimit: 0,
                };
            }
        }

        function acceptVar() {
            var selectedVal = $("select[name=currency] :selected");
            var currencyCode = $("select[name=currency] :selected").val();
            var currencyRate = defualCurrencyRate;
            var currencyMinAmount = "{{ getAmount($ticketPayCharge->min_limit) }}";
            var currencyMaxAmount = "{{ getAmount($ticketPayCharge->max_limit) }}";
            var currencyFixedCharge = "{{ getAmount($ticketPayCharge->fixed_charge) }}";
            var currencyPercentCharge = "{{ getAmount($ticketPayCharge->percent_charge) }}";
            var ticketType = $("select[name=ticket_type] :selected");
            var ticketName = $("select[name=ticket_type] :selected").data("name");
            var ticketNumber = $("input[name=ticket_number]").val();

            return {
                currencyCode: currencyCode,
                currencyRate: currencyRate,
                currencyMinAmount: currencyMinAmount,
                currencyMaxAmount: currencyMaxAmount,
                currencyFixedCharge: currencyFixedCharge,
                currencyPercentCharge: currencyPercentCharge,
                ticketName: ticketName,
                ticketNumber: ticketNumber,
                ticketType: ticketType,
                selectedVal: selectedVal,

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
                var percent_charge_calc = parseFloat(currencyRate) * (parseFloat(sender_amount) / 100) * parseFloat(
                    percent_charge);
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
            $(".fees-show").html("Ticket Pay Fees: " + parseFloat(charges.fixed).toFixed(2) + " " + currencyCode + " + " +
                parseFloat(charges.percent).toFixed(2) + "%  ");
        }

        function getPreview() {
            var senderAmount = $("input[name=amount]").val();
            var ticketType = acceptVar().ticketType.val();
            var priceTicket = $("input[name=ticket_price_" + ticketType + "]").val();
            var sender_currency = acceptVar().currencyCode;
            var sender_currency_rate = acceptVar().currencyRate;
            var ticketName = acceptVar().ticketName;
            var ticketNumber = acceptVar().ticketNumber;
            senderAmount == "" ? senderAmount = 0 : senderAmount = ticketNumber * priceTicket;
            // Sending Amount
            $('.request-amount').text(senderAmount + " " + defualCurrency);
            //ticket type
            $('.ticket-type').text(ticketName);
            // Fees
            //ticket number
            if (ticketNumber == '' || ticketNumber == 0) {
                $('.ticket-number').text("0");
            } else {
                $('.ticket-number').text(ticketNumber);
            }

            var totalPay = parseFloat(senderAmount) * parseFloat(sender_currency_rate)
            $("input[name=amount]").val(totalPay);

            // Fees
            var charges = feesCalculation();
            var total_charge = 0;
            if (senderAmount == 0) {
                total_charge = 0;
            } else {
                total_charge = charges.total;
            }

            $('.fees').text(total_charge + " " + sender_currency);

            // Pay In Total

            var pay_in_total = 0;
            if (senderAmount == 0) {
                pay_in_total = 0;
            } else {
                pay_in_total = parseFloat(totalPay) + parseFloat(charges.total);
            }
            $('.payable-total').text(parseFloat(pay_in_total).toFixed(2) + " " + sender_currency);

        }

        function enterLimit() {
            var min_limit = parseFloat("{{ getAmount($ticketPayCharge->min_limit) }}");
            var max_limit = parseFloat("{{ getAmount($ticketPayCharge->max_limit) }}");
            var currencyRate = acceptVar().currencyRate;
            var sender_amount = parseFloat($("input[name=amount]").val());

            if (sender_amount < min_limit) {
                throwMessage('error', ["Please follow the mimimum limit"]);
                $('.ticketPayBtn').attr('disabled', true)
            } else if (sender_amount > max_limit) {
                throwMessage('error', ["Please follow the maximum limit"]);
                $('.ticketPayBtn').attr('disabled', true)
            } else {
                $('.ticketPayBtn').attr('disabled', false)
            }

        }
    </script>
@endpush
