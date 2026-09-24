@props(['title' => null, 'description' => null, 'image' => null, 'type' => 'website', 'post' => null, 'author' => null, 'collection' => null, 'canonical' => null, 'robots' => null, 'seo' => null])
@php($meta = $seo ?? new \App\Domain\Seo\SeoData($title, $description, $image, $type, $post, $author, $collection, $canonical, $robots))
<title>{{ $meta->title }} — {{ config('app.name', 'Folkscript') }}</title>
<meta name="description" content="{{ $meta->description }}">
<meta name="robots" content="{{ $meta->robots }}">
<link rel="canonical" href="{{ $meta->canonicalUrl }}">
<meta property="og:type" content="{{ $meta->type }}">
<meta property="og:title" content="{{ $meta->title }}">
<meta property="og:description" content="{{ $meta->description }}">
<meta property="og:image" content="{{ $meta->image }}">
<meta property="og:url" content="{{ $meta->canonicalUrl }}">
<meta property="og:site_name" content="{{ config('app.name', 'Folkscript') }}">
@if($post && $post->status === 'published')
<meta property="article:published_time" content="{{ $post->published_at?->toIso8601String() }}">
<meta property="article:modified_time" content="{{ $post->updated_at?->toIso8601String() }}">
<meta property="article:author" content="{{ url('/@'.$post->author->username) }}">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $meta->title }}">
<meta name="twitter:description" content="{{ $meta->description }}">
<meta name="twitter:image" content="{{ $meta->image }}">
<meta name="theme-color" content="#1E2A47">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}" sizes="180x180">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="alternate" type="application/rss+xml" title="Folkscript stories" href="{{ url('/feed.xml') }}">
@if(config('services.google.site_verification'))
<meta name="google-site-verification" content="{{ config('services.google.site_verification') }}">
@endif
@if(config('services.bing.site_verification'))
<meta name="msvalidate.01" content="{{ config('services.bing.site_verification') }}">
@endif
<script type="application/ld+json">{!! json_encode($meta->schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
