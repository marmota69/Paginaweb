@props(['title' => null, 'description' => null])

@php
    $site = \App\Models\SiteSetting::current();
    $pageTitle = filled($title) ? $title.' — '.$site->profile_name : ($site->t('seo_title') ?: $site->profile_name);
    $pageDescription = $description ?? $site->t('seo_description');
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>{{ $pageTitle }}</title>

@if (filled($pageDescription))
    <meta name="description" content="{{ $pageDescription }}">
@endif

<meta property="og:type" content="website">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta name="twitter:card" content="{{ $site->seoImageUrl() ? 'summary_large_image' : 'summary' }}">

@if (filled($pageDescription))
    <meta property="og:description" content="{{ $pageDescription }}">
@endif

@if ($site->seoImageUrl())
    <meta property="og:image" content="{{ $site->seoImageUrl() }}">
@endif

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

{{--
    Required, not optional: Flux's runtime ships with the public site too, and
    when `Flux.applyAppearance` is missing its Alpine effect falls back to
    *deleting* `flux.appearance` from localStorage — which silently wiped the
    visitor's theme on every load. This directive defines that function.
--}}
@fluxAppearance

@include('partials.theme')

@vite(['resources/css/portfolio.css', 'resources/js/portfolio.js'])
