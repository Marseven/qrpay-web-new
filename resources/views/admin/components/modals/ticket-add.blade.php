@if (admin_permission_by_name('admin.ticket.pay.ticket.store'))
    <div id="ticket-add" class="mfp-hide large">
        <div class="modal-data">
            <div class="modal-header px-0">
                <h5 class="modal-title">{{ __('Add New Ticket Type') }}</h5>
            </div>
            <div class="modal-form-data">
                <form class="modal-form" method="POST" action="{{ setRoute('admin.ticket.pay.ticket.store') }}">
                    @csrf
                    <div class="row mb-10-none">

                        <div class="col-xl-12 col-lg-12 form-group mt-2">
                            @include('admin.components.form.input', [
                                'label' => 'Ticket Name*',
                                'name' => 'label',
                                'value' => old('label'),
                            ])
                        </div>

                        <div class="col-xl-12 col-lg-12 form-group mt-2">
                            @include('admin.components.form.input-amount', [
                                'label' => 'Ticket Price*',
                                'name' => 'price',
                                'value' => old('price'),
                            ])
                        </div>

                        <div
                            class="col-xl-12 col-lg-12 form-group d-flex align-items-center justify-content-between mt-4">
                            <button type="button" class="btn btn--danger modal-close">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn--base">{{ __('Add') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            openModalWhenError("ticket-add", "#ticket-add");
        </script>
    @endpush
@endif
