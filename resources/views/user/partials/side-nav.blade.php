
<div class="sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-menu-inner-wrapper">
            <div class="sidebar-logo">
                <a href="{{ setRoute('index') }}" class="sidebar-main-logo">
                    <img src="{{ get_logo($basic_settings) }}" data-white_img="{{ get_logo($basic_settings,"dark") }}"
                    data-dark_img="{{ get_logo($basic_settings) }}" alt="logo">
                </a>
                <button class="sidebar-menu-bar">
                    <i class="fas fa-exchange-alt"></i>
                </button>
            </div>
            <div class="sidebar-menu-wrapper">
                <ul class="sidebar-menu">
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.dashboard') }}">
                            <i class="menu-icon fas fa-th-large"></i>
                            <span class="menu-title">{{ __("Dashboard") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.send.money.index') }}">
                            <i class="menu-icon fas fa-paper-plane"></i>
                            <span class="menu-title">{{ __("Send Money") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.receive.money.index') }}">
                            <i class="menu-icon fas fa-receipt"></i>
                            <span class="menu-title">{{__("Receive Money")}}</span>

                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.remittance.index') }}">
                            <i class="menu-icon fas fa-coins"></i>
                            <span class="menu-title">{{ __("Remittance") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.add.money.index') }}">
                            <i class="menu-icon fas fa-plus-circle"></i>
                            <span class="menu-title">{{ __("Add Money") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.money.out.index') }}">
                            <i class="menu-icon fas fa-arrow-alt-circle-right"></i>
                            <span class="menu-title">{{ __("Withdraw") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.make.payment.index') }}">
                            <i class="menu-icon fas fa-arrow-alt-circle-left"></i>
                            <span class="menu-title">{{ __("Make Payment") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.virtual.card.index') }}">
                            <i class="menu-icon fas fa-credit-card"></i>
                            <span class="menu-title">{{ __("Virtual Card") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.bill.pay.index') }}">
                            <i class="menu-icon fas fa-shopping-bag"></i>
                            <span class="menu-title">{{ __("Bill Pay") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.mobile.topup.index') }}">
                            <i class="menu-icon fas fa-mobile"></i>
                            <span class="menu-title">{{ __("Mobile TopUp") }}</span>
                        </a>
                    </li>
                    {{-- <li class="sidebar-menu-item">
                        <a href="my-giftcard.html">
                            <i class="menu-icon fas fa-gift"></i>
                            <span class="menu-title">{{__("My Giftcard")}}</span>
                        </a>
                    </li> --}}
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.transactions.index') }}">
                            <i class="menu-icon fas fa-arrows-alt-h"></i>
                            <span class="menu-title">{{ __("Transactions") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.receipient.index') }}">
                            <i class="menu-icon fas fa-user-check"></i>
                            <span class="menu-title">{{__("Saved Recipient")}}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="{{ setRoute('user.security.google.2fa') }}">
                            <i class="menu-icon fas fa-qrcode"></i>
                            <span class="menu-title">{{ __("2FA Security") }}</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="javascript:void(0)" class="logout-btn">
                            <i class="menu-icon fas fa-sign-out-alt"></i>
                            <span class="menu-title">{{ __("Logout") }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="sidebar-doc-box bg_img" data-background="{{ asset('public/frontend/') }}/images/element/support.jpg">
            <div class="sidebar-doc-icon">
                <i class="las la-question-circle"></i>
            </div>
            <div class="sidebar-doc-content">
                <h4 class="title">{{ __("Need Help?") }}</h4>
                <p>{{ __("Please check our docs") }}</p>
                <div class="sidebar-doc-btn">
                    <a href="{{ setRoute('user.support.ticket.index') }}" class="btn--base w-100">{{ __("Get Support") }}</a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
    <script>
        $(".logout-btn").click(function(){
            var actionRoute =  "{{ setRoute('user.logout') }}";
            var target      = 1;
            var message     = `Are you sure to <strong>Logout</strong>?`;

            openAlertModal(actionRoute,target,message,"Logout","POST");
        });
    </script>
@endpush
