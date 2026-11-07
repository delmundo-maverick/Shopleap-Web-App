@props(['name'])
@php($path = \App\Support\NavigationIcons::path($name))
@if ($path)<svg {{ $attributes->class(['size-5 shrink-0']) }} aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $path }}" /></svg>@endif
