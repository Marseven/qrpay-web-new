@extends('user.layouts.master')

@push('css')

@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Profile")])
@endsection

@section('content')

    <div class="body-wrapper">
        <div class="row mb-20-none">
            <div class="col-xl-6 col-lg-6 mb-20">
                <div class="custom-card mt-10">
                    <div class="dashboard-header-wrapper">
                        <h4 class="title">{{__("Profile Settings")}}</h4>
                        <a href="javascript:void(0)" class="btn--base btn btn-sm delete-btn">{{ __("Delete Account") }}</a>
                    </div>
                    <div class="card-body profile-body-wrapper">
                        <form class="card-form" method="POST" action="{{ setRoute('user.profile.update') }} " enctype="multipart/form-data">
                            @csrf
                            @method("PUT")
                            <div class="profile-settings-wrapper">
                                <div class="preview-thumb profile-wallpaper">
                                    <div class="avatar-preview">
                                        <div class="profilePicPreview bg_img" data-background="{{ asset('public/frontend/') }}/images/element/virtual-card.png"></div>
                                    </div>
                                </div>
                                <div class="profile-thumb-content">
                                    <div class="preview-thumb profile-thumb">
                                        <div class="avatar-preview">
                                            <div class="profilePicPreview bg_img" data-background="{{ auth()->user()->userImage }}"></div>
                                        </div>
                                        <div class="avatar-edit">
                                            <input type='file' class="profilePicUpload" name="image" id="profilePicUpload2"
                                                accept=".png, .jpg, .jpeg" />
                                            <label for="profilePicUpload2"><i class="las la-upload"></i></label>
                                        </div>
                                    </div>
                                    <div class="profile-content">
                                        <h6 class="username">{{ auth()->user()->username }}</h6>
                                        <ul class="user-info-list mt-md-2">
                                            <li><i class="las la-envelope"></i>{{ auth()->user()->email }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="profile-form-area">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        @include('admin.components.form.input',[
                                            'label'         => "First Name<span>*</span>",
                                            'name'          => "firstname",
                                            'placeholder'   => "Enter First Name...",
                                            'value'         => old('firstname',auth()->user()->firstname)
                                        ])
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        @include('admin.components.form.input',[
                                            'label'         => "Last Name<span>*</span>",
                                            'name'          => "lastname",
                                            'placeholder'   => "Enter Last Name...",
                                            'value'         => old('lastname',auth()->user()->lastname)
                                        ])
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Country") }}<span>*</span></label>
                                        <select name="country" class="form--control select2-auto-tokenize country-select" data-placeholder="Select Country" data-old="{{ old('country',auth()->user()->address->country ?? "") }}"></select>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        <label>{{ __("Phone") }}<span>*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-text phone-code">+{{ auth()->user()->mobile_code }}</div>
                                            <input class="phone-code" type="hidden" name="phone_code" value="{{ auth()->user()->mobile_code }}" />
                                            <input type="text" class="form--control" placeholder="Enter Phone ..." name="phone" value="{{ old('phone',auth()->user()->mobile) }}">
                                        </div>

                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        @include('admin.components.form.input',[
                                            'label'         => "Address",
                                            'name'          => "address",
                                            'placeholder'   => "Enter Address...",
                                            'value'         => old('address',auth()->user()->address->address ?? "")
                                        ])
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        @php
                                        $old_city = old('city',auth()->user()->address->city ?? "");
                                        @endphp
                                        @include('admin.components.form.input',[
                                            'label'         => "City",
                                            'name'          => "city",
                                            'placeholder'   => "Enter City...",
                                            'value'         => old('city', $old_city)
                                        ])

                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        @php
                                        $old_state = old('state',auth()->user()->address->state ?? "");
                                    @endphp

                                    @include('admin.components.form.input',[
                                        'label'         => "State",
                                        'name'          => "state",
                                        'placeholder'   => "Enter State...",
                                        'value'         => old('state', $old_state)
                                    ])
                                    </div>
                                    <div class="col-xl-6 col-lg-6 form-group">
                                        @include('admin.components.form.input',[
                                            'label'         => "Zip Code",
                                            'name'          => "zip_code",
                                            'placeholder'   => "Enter Zip...",
                                            'value'         => old('zip_code',auth()->user()->address->zip ?? "")
                                        ])
                                    </div>
                                </div>
                                <div class="col-xl-12 col-lg-12">
                                    <button type="submit" class="btn--base w-100 btn-loading">Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 mb-20">
                <div class="custom-card mt-10">
                    <div class="dashboard-header-wrapper">
                        <h4 class="title">{{ __("Change Password") }}</h4>
                    </div>
                    <div class="card-body">
                        <form class="card-form" action="{{ setRoute('user.profile.password.update') }}" method="POST">
                            @csrf
                            @method("PUT")
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 form-group show_hide_password">
                                    @include('admin.components.form.input',[
                                    'label'     => "Current Password<span>*</span>",
                                    'name'      => "current_password",
                                    'type'      => "password",
                                    'placeholder'   => "Enter Password...",
                                ])
                                <a href="javascript:void(0)" class="show-pass profile"><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group show_hide_password-2">
                                    @include('admin.components.form.input',[
                                    'label'     => "New Password<span>*</span>",
                                    'name'      => "password",
                                    'type'      => "password",
                                    'placeholder'   => "Enter Password...",
                                ])
                                <a href="javascript:void(0)" class="show-pass profile"><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group show_hide_password-3">
                                    @include('admin.components.form.input',[
                                        'label'     => "Confirm Password<span>*</span>",
                                        'name'      => "password_confirmation",
                                        'type'      => "password",
                                        'placeholder'   => "Enter Password...",
                                    ])
                                    <a href="javascript:void(0)" class="show-pass profile"><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-12">
                                <button type="submit" class="btn--base w-100 btn-loading">Change</button>
                            </div>
                        </form>
                    </div>
                </div>
                @include('user.components.profile.kyc',compact("kyc_data"))
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        getAllCountries("{{ setRoute('global.countries') }}");
        $(document).ready(function(){
            $("select[name=country]").change(function(){
                var phoneCode = $("select[name=country] :selected").attr("data-mobile-code");
                placePhoneCode(phoneCode);
            });

            countrySelect(".country-select",$(".country-select").siblings(".select2"));
            stateSelect(".state-select",$(".state-select").siblings(".select2"));

        });
        $(".delete-btn").click(function(){
            var actionRoute =  "{{ setRoute('user.delete.account') }}";
            var target      = 1;
            var btnText = "Delete Account";
            var projectName = "{{ @$basic_settings->site_name }}";
            var name = $(this).data('name');
            var message     = `Are you sure to delete <strong>your account</strong>?<br>If you do not think you will use “<strong>${projectName}</strong>”  again and like your account deleted, we can take card of this for you. Keep in mind you will not be able to reactivate your account or retrieve any of the content or information you have added. If you would still like your account deleted, click “Delete Account”.?`;
            openAlertModal(actionRoute,target,message,btnText,"DELETE");
        });
    </script>
@endpush
