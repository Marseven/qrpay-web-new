<table class="custom-table merchant-search-table">
    <thead>
        <tr>
            <th></th>
            <th>Username</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Email Verification</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($merchants ?? [] as $key => $item)
            <tr>
                <td>
                    <ul class="user-list">
                        <li><img src="{{ $item->userImage }}" alt="user"></li>
                    </ul>
                </td>
                <td><span>{{ $item->username }}</span></td>
                <td>{{ $item->email }}</td>
                <td>{{ @$item->full_mobile }}</td>
                <td>
                    <span class="{{ $item->emailStatus->class }}">{{ $item->emailStatus->value }}</span>
                </td>
                <td>
                    @if (Route::currentRouteName() == "admin.merchants.kyc.unverified")
                        <span class="{{ $item->kycStringStatus->class }}">{{ $item->kycStringStatus->value }}</span>
                    @else
                        <span class="{{ $item->stringStatus->class }}">{{ $item->stringStatus->value }}</span>
                    @endif
                </td>
                <td>
                    @if (Route::currentRouteName() == "admin.merchants.kyc.unverified")
                        @include('admin.components.link.info-default',[
                            'href'          => setRoute('admin.merchants.kyc.details', $item->username),
                            'permission'    => "admin.merchants.kyc.details",
                        ])
                    @else
                        @include('admin.components.link.info-default',[
                            'href'          => setRoute('admin.merchants.details', $item->username),
                            'permission'    => "admin.merchants.details",
                        ])
                    @endif
                </td>
            </tr>
        @empty
            @include('admin.components.alerts.empty',['colspan' => 7])
        @endforelse
    </tbody>
</table>
