<div class="ops-nav mb-3">
    <ul class="nav nav-pills ops-tabs gap-2 flex-wrap">
        @foreach($tabs as $tab)
            <li class="nav-item">
                <a href="{{ route($tab['route']) }}"
                   class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs($tab['match']) ? 'active' : '' }}"
                   title="{{ $tab['count'] }} {{ $tab['hint'] }}">
                    <i class="bi {{ $tab['icon'] }}"></i>
                    <span>{{ $tab['label'] }}</span>
                    @if($tab['count'] > 0)
                        <span class="badge rounded-pill bg-{{ $tab['tone'] }} {{ in_array($tab['tone'], ['warning', 'info']) ? 'text-dark' : '' }}">{{ $tab['count'] }}</span>
                    @endif
                </a>
            </li>
        @endforeach
        @if(($low = $lowStockCount()) > 0)
            <li class="nav-item ms-lg-auto">
                <a href="{{ route('admin.stock_receipts.index') }}" class="nav-link bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $low }} sản phẩm sắp hết hàng
                </a>
            </li>
        @endif
    </ul>
</div>
