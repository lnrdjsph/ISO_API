@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @include('forms.sales-order.partials.header')
            @include('forms.sales-order.partials.alerts')

            <form method="POST" action="{{ route('sales-order.store') }}" id="order-form" class="space-y-6">
                @csrf
                @include('forms.sales-order.partials.order-details')
                @include('forms.sales-order.partials.customer-info')
                @include('forms.sales-order.partials.payment-info')
                @include('forms.sales-order.partials.dispatch-details')
                @include('forms.sales-order.partials.order-items')
                @include('forms.sales-order.partials.submit-section')
            </form>
        </div>
    </div>

    <script>
        window.routeUrls = {
            searchProducts: "{{ route('sales-order.search') }}",
            getCardInfo: "{{ route('sales-order.card-info') }}",
            submitOrder: "{{ route('sales-order.store') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>

    @vite(['resources/views/forms/sales-order/js/sales-order.js'])
    @vite(['resources/views/forms/sales-order/css/sales-order.css'])
@endsection
