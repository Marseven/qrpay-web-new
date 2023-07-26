
@php
    $token = (object)session()->get('remittance_token');
    $country= App\Models\Admin\ReceiverCounty::where('id',@$token->receiver_country)->first();
@endphp
<div class="trx-input" style="display: none;">
    <div class="row">
        <div class="col-xl-4 col-lg-4 col-md-6 form-group">
            <label>{{ __("Country") }}<span>*</span></label>
            <select name="country" class="form--control country-select select2-basic " data-minimum-results-for-search="Infinity">
                <option selected disabled>Select Country</option>
                @foreach ($countries as $item)
                 @if(get_default_currency_code() == $item->code)
                    <option value="{{ $item->id }}" {{   @$country->id == $item->id?'selected':'' }} data-country-code="{{ $item->code }}" data-mobile-code="{{ $item->mobile_code }}"  data-id="{{ $item->id }}">{{ $item->country }} ({{ $item->code }})</option>
                 @endif
                @endforeach
            </select>
        </div>
        <div class="col-xl-4 col-lg-4 col-md-6 form-group">

            <label>{{ __("Phone Number") }}<span>*</span></label>
            <div class="input-group">
              <div class="input-group-text phone-code">+{{ @$country->mobile_code }}</div>
              <input class="phone-code" type="hidden" name="mobile_code" value="{{  @$country->mobile_code }}" />
              <input type="text" class="form--control mobile" placeholder="Enter Mobile" name="mobile">
            </div>
        </div>
        <div class="col-xl-4 col-lg-4 col-md-6 form-group">
            <label>{{ __("Email Address") }}<span>*</span></label>
              <div class="input-group">
                <input type="email" class="form--control email" placeholder="Enter Email Address..." name="email">
              </div>
         </div>
        <div class="col-xl-4 col-lg-4 col-md-6 form-group">
            @include('admin.components.form.input',[
                'name'          => "firstname",
                'label'         => "First Name",
                'label_after'   => "<span>*</span>",
                'placeholder'   => "First Name...",
                'attribute'     => "readonly",
            ])
        </div>

        <div class="col-xl-4 col-lg-4 col-md-6 form-group">
            @include('admin.components.form.input',[
                'label'         => "Last Name",
                'label_after'   => "<span>*</span>",
                'name'          => "lastname",
                'placeholder'   => "Last Name...",
                'attribute'     => "readonly",
            ])
        </div>


        <div class="col-xl-4 col-lg-4 col-md-6 form-group state-select-wrp">
            @include('admin.components.form.input',[
                'label'         => "Address",
                'label_after'   => "<span>*</span>",
                'name'          => "address",
                'placeholder'   => "Address",
                'attribute'     => "readonly id=place-input autocomplete=none",
            ])
        </div>
        <div class="col-xl-4 col-lg-4 col-md-6 form-group state-select-wrp">
            @include('admin.components.form.input',[
                'label'         => "State",
                'name'          => "state",
                'placeholder'   => "State",
                'attribute'     => "readonly",
            ])
        </div>
        <div class="col-xl-4 col-lg-4 col-md-6 form-group city-select-wrp">
            @include('admin.components.form.input',[
                'label'         => "City",
                'label_after'   => "<span>*</span>",
                'name'          => "city",
                'placeholder'   => "City",
                'attribute'     => "readonly",
            ])
        </div>
        <div class="col-xl-4 col-lg-4 col-md-6 form-group">
            @include('admin.components.form.input',[
                'label'         => "Zip Code",
                'label_after'   => "<span>*</span>",
                'name'          => "zip",
                'type'          => "text",
                'placeholder'   => "Zip Code",
                'attribute'     => "readonly",
            ])
        </div>

    </div>
</div>
