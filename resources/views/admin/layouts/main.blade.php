<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<title>@yield('title','') | {{ config('app.name') }} — {{ config('app.tagline') }}</title>
	
	@include('admin.includes.head')
	<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="min-h-screen bg-body font-sans text-[15px] text-[#4a5361] antialiased"
      x-data="{ sidebarOpen: false, chatOpen: false }"
      :class="{ 'overflow-hidden': sidebarOpen }">

	<a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[200] focus:rounded-lg focus:bg-primary-600 focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white">{{ __('Skip to content') }}</a>

	<!-- Mobile overlay -->
	<div x-show="sidebarOpen" x-transition.opacity
	     @click="sidebarOpen = false"
	     class="fixed inset-0 z-30 bg-black/50 lg:hidden" style="display:none"></div>

	<!-- Sidebar -->
	@include('admin.includes.sidebar')

	<!-- Header -->
	@include('admin.includes.header')

	<!-- Main content -->
	<main id="content" class="min-h-screen pt-16 lg:pl-60">
		<div class="p-4 sm:p-6">
			@yield('content')
		</div>
		@include('admin.includes.footer')
	</main>

	<!-- Right chat drawer -->
	@include('admin.includes.chat')

	<!-- App launcher modal -->
	@include('admin.includes.modalmenu')

	<!-- Theme customizer drawer -->
{{-- <x-theme-customizer /> --}}

	<!-- Global overlays + feedback singletons -->
	{{-- <x-command-palette /> --}}
	{{-- <x-toast />--}}
	{{--<x-confirm-modal />--}}

	@include('admin.includes.script')
</body>
</html>
