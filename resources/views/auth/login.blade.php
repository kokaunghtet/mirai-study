<x-portal-layout mode="login" :portal="!session()->has('ban_appeal')">
    @include('auth.portal', ['mode' => 'login'])
</x-portal-layout>
