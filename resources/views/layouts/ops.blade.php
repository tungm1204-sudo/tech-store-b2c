@extends('layouts.admin')

@push('styles')
<style>
    .ops-tabs .nav-link { background: #fff; color: #334155; border: 1px solid #e2e8f0; border-radius: 10px; font-weight: 600; font-size: .92rem; }
    .ops-tabs .nav-link:hover { border-color: #93c5fd; }
    .ops-tabs .nav-link.active { background: #2563eb; border-color: #2563eb; color: #fff; }
    .ops-heading { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .ops-heading h1 { font-size: 1.25rem; font-weight: 700; margin: 0; }
    .ops-card { border: 0; border-radius: 14px; box-shadow: 0 4px 14px rgba(15, 23, 42, .05); background: #fff; }
    .ops-table th { font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; color: #64748b; }
</style>
@endpush

@section('content')
    <x-ops-nav />

    @hasSection('ops_heading')
        <div class="ops-heading">
            <h1>@yield('ops_heading')</h1>
            <div class="d-flex gap-2">@yield('ops_actions')</div>
        </div>
    @endif

    @yield('ops_content')
@endsection
