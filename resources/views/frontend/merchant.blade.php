@extends('frontend.layouts.master')

@php
    $lang = selectedLang();
    $merchant_slug = Illuminate\Support\Str::slug(App\Constants\SiteSectionConst::MERCHANT_SECTION);
    $merchant = App\Models\Admin\SiteSections::getData( $merchant_slug)->first();
@endphp

@section('content')

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Banner
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="banner-section bg_img" data-background="{{ asset('public/frontend/') }}/images/banner/bg-3.jpg">
    <div class="container home-container">
        <div class="row mb-30-none">
            <div class="col-lg-6 col-md-6 mb-30">
                <div class="banner-thumb-area text-center">
                    <img src="{{ get_image(@$merchant->value->images->banner_image,'site-section') }}" alt="banner">
                </div>
            </div>
            <div class="col-lg-6 col-md-6 mb-30">
                <div class="banner-content">
                    <span class="banner-sub-titel"><i class="fas fa-qrcode"></i> {{ __(@$merchant->value->language->$lang->heading) }}</span>
                    <h1 class="banner-title">{{ __(@$merchant->value->language->$lang->sub_heading) }}</h1>
                    <p class="mb-2">{{ __(@$merchant->value->language->$lang->details) }}</p>
                    <div class="banner-btn">
                        <a href="{{ setRoute('merchant.register') }}" class="btn--base"><i class="las la-user-plus me-1"></i>{{ __("Register") }}</a>
                        <a href="{{ setRoute('merchant.login') }}" class="btn--base active"><i class="las la-key me-1"></i>{{ __("Login") }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Banner
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start service section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@include('frontend.partials.service')
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End service section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@endsection


@push("script")

@endpush
