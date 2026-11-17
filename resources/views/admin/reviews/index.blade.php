@extends('admin.layouts.admin')
@section('title', 'Reviews')
@section('content')
<div class="mb-4 flex gap-2">@foreach([null => 'All', 'pending' => 'Pending (' . $pending . ')', 'approved' => 'Approved'] as $k => $l)<a href="{{ route('admin.reviews.index', array_filter(['status' => $k])) }}" class="rounded-full border px-4 py-1.5 text-sm {{ request('status') == $k ? 'border-navy bg-navy text-white' : 'border-line bg-white' }}">{{ $l }}</a>@endforeach</div>
<div class="space-y-3">@forelse($reviews as $r)
    <div class="card p-4"><div class="flex flex-wrap items-center justify-between gap-2"><div class="flex items-center gap-3"><x-stars :rating="$r->rating"/><b>{{ $r->name }}</b> @if($r->is_verified)<span class="badge bg-green-50 text-green-700">Verified purchase</span>@endif<span class="badge {{ $r->is_approved ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">{{ $r->is_approved ? 'Approved' : 'Pending' }}</span></div><span class="text-xs text-muted">{{ $r->created_at->format('d M Y') }} · {{ $r->product?->name }}</span></div>
        @if($r->title)<p class="mt-2 font-semibold">{{ $r->title }}</p>@endif<p class="mt-1 text-sm">{{ $r->body }}</p>@if($r->image)<img src="{{ img($r->image) }}" class="mt-2 h-20 rounded-lg" alt="">@endif
        <div class="mt-3 flex gap-2"><form method="post" action="{{ route('admin.reviews.update', $r) }}">@csrf @method('PUT')<input type="hidden" name="approve" value="{{ $r->is_approved ? 0 : 1 }}"><button class="btn btn-outline btn-sm">{{ $r->is_approved ? 'Unapprove' : 'Approve' }}</button></form><form method="post" action="{{ route('admin.reviews.destroy', $r) }}" onsubmit="return confirm('Delete review?')">@csrf @method('DELETE')<button class="btn btn-outline btn-sm !text-red-600">Delete</button></form></div></div>
@empty<div class="card p-12 text-center text-muted">No reviews yet.</div>@endforelse</div><div class="mt-4">{{ $reviews->links() }}</div>
@endsection
