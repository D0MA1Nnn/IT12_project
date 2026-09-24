@extends('layouts.app')
@section('title','Activity Logs')
@section('content')
<div class="top"><div><h1>Activity Logs</h1><div class="muted">Review important user and transaction activities recorded by the system.</div></div><div class="who">Owner</div></div>
<div class="card"><form method="GET" class="toolbar" style="justify-content:flex-start"><select name="module" style="max-width:180px"><option value="">All Modules</option>@foreach(['AUTH','PRODUCT','CATEGORY','SUPPLIER','PURCHASE','SALE','USER','INVENTORY','BACKUP','UNIT'] as $m)<option value="{{$m}}" @selected(request('module')===$m)>{{$m}}</option>@endforeach</select><input class="input" style="max-width:180px" type="date" name="date" value="{{request('date')}}"><button class="btn primary">Filter</button><a class="btn light" href="{{route('activity.index')}}">Clear</a></form><table class="table"><thead><tr><th>DATE & TIME</th><th>USER</th><th>MODULE</th><th>ACTION</th><th>DESCRIPTION</th></tr></thead><tbody>@forelse($logs as $l)<tr><td>{{$l->created_at?->format('M d, Y h:i A')}}</td><td>{{$l->user->username ?? 'Unknown'}}</td><td><span class="badge">{{$l->module}}</span></td><td>{{$l->action}}</td><td>{{$l->description}}</td></tr>@empty<tr><td colspan="5">No activity found.</td></tr>@endforelse</tbody></table>@if ($logs->hasPages())
<div style="margin-top:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
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
<div class="muted" style="margin-top:16px;">
    Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} results
</div>
@endif</div>
@endsection
