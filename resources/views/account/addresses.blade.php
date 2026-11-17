@extends('account._shell')
@section('body')
@if($errors->any())<div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
<div class="grid gap-4 sm:grid-cols-2">
    @foreach($addresses as $a)
        <div class="card p-4 text-sm" x-data="{ edit: false }">
            <p class="font-semibold">{{ $a->label ?: 'Address' }} @if($a->is_default)<span class="badge bg-navy-50 text-navy">Default</span>@endif</p>
            <p class="mt-1 text-muted">{{ $a->name }}<br>{{ $a->address }}, {{ $a->city }} {{ $a->postal_code }}<br>{{ $a->phone }}</p>
            <div class="mt-3 flex gap-3"><button class="text-brand underline" @click="edit = !edit">Edit</button>
                <form method="post" action="{{ route('account.addresses.delete', $a->id) }}" onsubmit="return confirm('Delete this address?')">@csrf @method('DELETE')<button class="text-red-600 underline">Delete</button></form></div>
            <form x-show="edit" x-cloak method="post" action="{{ route('account.addresses.update', $a->id) }}" class="mt-3 space-y-2">@csrf @method('PUT') @include('account._address_fields', ['a' => $a])<button class="btn btn-primary btn-sm">Save</button></form>
        </div>
    @endforeach
</div>
<form method="post" action="{{ route('account.addresses.store') }}" class="card mt-6 space-y-3 p-5"><h2 class="text-lg">Add new address</h2>@csrf @include('account._address_fields', ['a' => null])<button class="btn btn-primary">Save address</button></form>
@endsection
