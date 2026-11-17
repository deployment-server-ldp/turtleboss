@extends('account._shell')
@section('body')<h2 class="mb-3 text-lg">My Orders</h2>@include('account._orders')<div>{{ $orders->links() }}</div>@endsection
