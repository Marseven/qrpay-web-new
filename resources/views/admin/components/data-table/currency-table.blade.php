<table class="custom-table currency-search-table">
    <thead>
        <tr>
            <th>Flag</th>
            <th></th>
            <th>Name | Code</th>
            <th>Symbol</th>
            {{-- <th>Type | Rate</th> --}}
            {{-- <th>Role</th>
            <th>Status</th> --}}
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($currencies ?? [] as $item)
            <tr data-item="{{ $item->editData }}">
                <td>
                    <ul class="user-list">
                        <li><img src="{{ get_image($item->flag,'currency-flag') }}" alt="flag"></li>
                    </ul>
                </td>
                <td></td>
                <td>{{ $item->name }}
                    @if ($item->default)
                        <span class="badge badge--success ms-1">{{ __("Default") }}</span>
                    @endif
                    <br> <span>{{ $item->code }}</span></td>
                <td>{{ $item->symbol }}</td>
                {{-- <td><span class="text--info">{{ $item->type }}</span> <br> 1 {{ get_default_currency_code($default_currency) }} = {{ get_amount($item->rate,$item->code) }}</td> --}}
                {{-- <td>
                    @if ($item->both)
                        <span class="badge badge--success">{{ __("Sender") }}</span>
                        <span class="badge badge--danger">{{ __("Receiver") }}</span>
                    @elseif ($item->senderCurrency)
                        <span class="badge badge--success">{{ __("Sender") }}</span>
                    @elseif ($item->receiverCurrency)
                        <span class="badge badge--danger">{{ __("Receiver") }}</span>
                    @endif
                </td> --}}
                {{-- <td>
                    @include('admin.components.form.switcher',[
                        'name'          => 'currency_status',
                        'value'         => $item->status,
                        'options'       => ['Enable' => 1,'Disable' => 0],
                        'onload'        => true,
                        'data_target'   => $item->code,
                        'permission'    => "admin.currency.status.update",
                    ])
                </td> --}}
                <td>
                    @include('admin.components.link.edit-default',[
                        'href'          => "javascript:void(0)",
                        'class'         => "edit-modal-button",
                        'permission'    => "admin.currency.update",
                    ])
                    @if (!$item->isDefault())
                        @include('admin.components.link.delete-default',[
                            'href'          => "javascript:void(0)",
                            'class'         => "delete-modal-button",
                            'permission'    => "admin.currency.delete",
                        ])
                    @endif
                </td>
            </tr>
        @empty
            @include('admin.components.alerts.empty',['colspan' => 7])
        @endforelse
    </tbody>
</table>

@push("script")
    <script>
        $(document).ready(function(){
            // Switcher
            switcherAjax("{{ setRoute('admin.currency.status.update') }}");
        })
    </script>
@endpush
