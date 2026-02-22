@extends('admin.layouts.app', [
    'title' => $title,
    'subtitle' => $subtitle,
])

@section('content')
    <section class="placeholder">
        <p class="m0-strong">Module en cours de construction</p>
        <p class="mt-8-0-0">{{ $message }}</p>
    </section>
@endsection
