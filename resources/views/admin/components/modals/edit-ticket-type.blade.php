@if (admin_permission_by_name('admin.ticket.pay.ticket.update'))
    <div id="edit-ticket" class="mfp-hide large">
        <div class="modal-data">
            <div class="modal-header px-0">
                <h5 class="modal-title">{{ __('Edit Ticket') }}</h5>
            </div>
            <div class="modal-form-data">
                <form class="modal-form" method="POST" action="{{ setRoute('admin.ticket.pay.ticket.update') }}">
                    @csrf
                    @method('PUT')
                    @include('admin.components.form.hidden-input', [
                        'name' => 'target',
                        'value' => old('target'),
                    ])
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
                            <button type="submit" class="btn btn--base">{{ __('Update') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            $(document).ready(function() {
                openModalWhenError("edit-ticket", "#edit-ticket");
                $(document).on("click", ".edit-modal-button", function() {
                    var oldData = JSON.parse($(this).parents("tr").attr("data-item"));
                    var editModal = $("#edit-ticket");
                    editModal.find("form").first().find("input[name=target]").val(oldData.id);
                    editModal.find("input[name=name]").val(oldData.name)
                    openModalBySelector("#edit-ticket");

                });
            });
        </script>
    @endpush
@endif
