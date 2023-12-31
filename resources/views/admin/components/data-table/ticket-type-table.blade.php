<table class="custom-table ticket-search-table">
    <thead>
        <tr>

            <th>Ticket Name</th>
            <th>Ticket Price</th>
            <th>Created Time</th>
            <th>status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>

        @forelse ($allTicket ?? [] as $item)
            <tr data-item="{{ $item->editData }}">

                <td>{{ $item->label }}</td>
                <td>{{ get_amount($item->price) }} {{ get_default_currency_symbol() }}</td>
                <td>{{ $item->created_at->format('d-m-y h:i:s A') }}</td>
                <td>
                    @include('admin.components.form.switcher', [
                        'name' => 'ticket_status',
                        'value' => $item->status,
                        'options' => ['Enable' => 1, 'Disable' => 0],
                        'onload' => true,
                        'data_target' => $item->id,
                        'permission' => 'admin.ticket.pay.ticket.status.update',
                    ])
                </td>
                <td>
                    @include('admin.components.link.edit-default', [
                        'href' => 'javascript:void(0)',
                        'class' => 'edit-modal-button',
                        'permission' => 'admin.ticket.pay.ticket.update',
                    ])

                    @include('admin.components.link.delete-default', [
                        'href' => 'javascript:void(0)',
                        'class' => 'delete-modal-button',
                        'permission' => 'admin.ticket.pay.ticket.delete',
                    ])

                </td>
            </tr>
        @empty
            @include('admin.components.alerts.empty', ['colspan' => 7])
        @endforelse
    </tbody>
</table>

@push('script')
    <script>
        $(document).ready(function() {
            // Switcher
            switcherAjax("{{ setRoute('admin.ticket.pay.ticket.status.update') }}");
        })
    </script>
@endpush
