<div class="trx-input">
    <div class="row">
        <div class="col-xl-4 col-lg-4 col-md-6 form-group">
            @include('admin.components.form.input',[
                'name'          => "firstname",
                'label'         => "First Name",
                'label_after'   => "<span>*</span>",
                'placeholder'   => "First Name...",
                'value'     => @$data->firstname,
            ])
        </div>

        <div class="col-xl-4 col-lg-4 col-md-6 form-group">
            @include('admin.components.form.input',[
                'label'         => "Last Name",
                'label_after'   => "<span>*</span>",
                'name'          => "lastname",
                'placeholder'   => "Last Name...",
                'value'     => @$data->lastname,
            ])
        </div>
        <div class="col-xl-4 col-lg-4 col-md-6 form-group">
            <label>{{ __("Country") }}<span>*</span></label>
            <select name="country" class="form--control country-select select2-basic">
                <option selected disabled>Select Country</option>
                @foreach ($countries as $item)
                <option value="{{ $item->id }}" {{ @$data->country == $item->id ?'selected':''}} data-country-code="{{ $item->code }}" data-mobile-code="{{ $item->mobile_code }}"  data-id="{{ $item->id }}">{{ $item->country }} ({{ $item->code }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-xl-4 col-lg-4 col-md-6 form-group">
            @include('admin.components.form.input',[
                'label'         => "Address",
                'label_after'   => "<span>*</span>",
                'name'          => "address",
                'type'          => "text",
                'placeholder'   => "Search Address",
                'required'      => true,
                'attribute'     => "id=place-input autocomplete=none",
                'value'     => @$data->address,

            ])
        </div>
        <div class="col-xl-4 col-lg-4 col-md-6 form-group">
            @include('admin.components.form.input',[
                'label'         => "State",
                'name'          => "state",
                'type'          => "text",
                'placeholder'   => "Enter State",
                'value'     => @$data->state,
            ])
        </div>
        <div class="col-xl-4 col-lg-4 col-md-6 form-group">
            @include('admin.components.form.input',[
                'label'         => "City",
                'label_after'   => "<span>*</span>",
                'name'          => "city",
                'type'          => "text",
                'placeholder'   => "Enter City",
                'value'     => @$data->city,
            ])
        </div>

        <div class="col-xl-3 col-lg-3 col-md-6 form-group">
            @include('admin.components.form.input',[
                'label'         => "Zip Code",
                'label_after'   => "<span>*</span>",
                'name'          => "zip",
                'type'          => "text",
                'placeholder'   => "Zip Code",
                'value'     => @$data->zip_code,
            ])
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 form-group">
        <label>{{ __("Phone Number") }}<span>*</span></label>
          <div class="input-group">
            <div class="input-group-text phone-code">+{{ @$data->mobile_code }}</div>
            <input class="phone-code" type="hidden" name="mobile_code" value="{{ @$data->mobile_code }}"/>
            <input type="text" class="form--control" placeholder="Enter Mobile..." name="mobile" value="{{ @$data->mobile }}">
          </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 form-group">
            <label>{{ __("Email Address") }}<span>*</span></label>
              <div class="input-group">
                <input type="email" class="form--control" placeholder="Enter Email Address..." name="email" value="{{ @$data->email }}">
              </div>
        </div>

        <div class="col-xl-3 col-lg-3 col-md-6 form-group">
            <label>{{ __("Pickup Point") }} <span>*</span></label>
            <select name="cash_pickup" class="form--control select2-basic" data-placeholder="Select Pickup Point">
                <option selected disabled>Select Pickup Point</option>
                @foreach ($pickup_points as $item)
                    <option value="{{ $item->alias }}"  {{ @$data->alias == $item->alias ? "selected":'' }}>{{  $item->name  }}</option>
                @endforeach
            </select>
        </div>

    </div>
</div>
