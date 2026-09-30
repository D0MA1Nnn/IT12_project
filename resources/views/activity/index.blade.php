@extends('layouts.app')
@section('title','Activity Logs')
@section('content')
<div class="card activity-log-card">
    <form method="GET" class="toolbar activity-log-filter">
        <select name="module" class="activity-log-select">
            <option value="">All Modules</option>
            @foreach(['AUTH','PRODUCT','CATEGORY','SUPPLIER','PURCHASE','SALE','USER','INVENTORY','BACKUP','UNIT'] as $m)
                <option value="{{$m}}" @selected(request('module')===$m)>{{$m}}</option>
            @endforeach
        </select>

        <input class="input activity-log-date" type="date" name="date" value="{{request('date')}}">

        <button class="btn primary">Filter</button>
        <a class="btn light" href="{{route('activity.index')}}">Clear</a>
    </form>

    <div class="activity-log-table-scroll">
        <table class="table activity-log-table">
            <thead>
                <tr>
                    <th>DATE & TIME</th>
                    <th>USER</th>
                    <th>MODULE</th>
                    <th>ACTION</th>
                    <th>DESCRIPTION</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $l)
                    <tr>
                        <td>{{$l->created_at?->format('M d, Y h:i A')}}</td>
                        <td>{{$l->user->username ?? 'Unknown'}}</td>
                        <td><span class="badge">{{$l->module}}</span></td>
                        <td>{{$l->action}}</td>
                        <td>{{$l->description}}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No activity found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@if ($logs->hasPages())
<div class="activity-log-pagination">
    <div class="muted">
        Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} results
    </div>

    <div style="display:flex; align-items:center; gap:6px;">
        @if ($logs->onFirstPage())
            <span class="btn light" style="opacity:.55; cursor:not-allowed;">&lsaquo; Previous</span>
        @else
            <a class="btn light" href="{{ $logs->previousPageUrl() }}">&lsaquo; Previous</a>
        @endif

        @foreach ($logs->getUrlRange(1, $logs->lastPage()) as $page => $url)
            @if ($page == $logs->currentPage())
                <span class="btn primary">{{ $page }}</span>
            @else
                <a class="btn light" href="{{ $url }}">{{ $page }}</a>
            @endif
        @endforeach

        @if ($logs->hasMorePages())
            <a class="btn light" href="{{ $logs->nextPageUrl() }}">Next &rsaquo;</a>
        @else
            <span class="btn light" style="opacity:.55; cursor:not-allowed;">Next &rsaquo;</span>
        @endif
    </div>
</div>
@elseif ($logs->total() > 0)
<div class="muted activity-log-results">
    Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} results
</div>
@endif
</div>

<style>
html,
body {
    overflow: hidden;
}

.main {
    display: flex;
    flex-direction: column;
    height: 100vh;
    min-height: 0;
    overflow: hidden;
}

.activity-log-card {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}

.activity-log-filter {
    flex: 0 0 auto;
    justify-content: flex-start;
    margin-bottom: 22px;
}

.activity-log-select,
.activity-log-date {
    max-width: 180px;
}

.activity-log-table-scroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
}

.activity-log-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
}

.activity-log-pagination,
.activity-log-results {
    flex: 0 0 auto;
    margin-top: 16px;
}

.activity-log-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
</style>
@endsection
