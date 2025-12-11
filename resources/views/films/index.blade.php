@extends('layouts.app')
<link rel="stylesheet" href="{{ asset('css/media.css') }}">

@section('content')
@php($routePrefix = 'films')

<div class="media-page">
    <div class="mb-3">
        <a href="{{ route($routePrefix.'.export.csv') }}" class="btn btn-secondary">CSV export</a>
        <a href="{{ route($routePrefix.'.export.pdf') }}" class="btn btn-secondary">PDF export</a>
    </div>

    <h1 class="media-title">Filmek</h1>

    {{-- Üzenetek --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Új film gomb --}}
    @if($isAuthenticated)
        <a href="{{ route('films.create') }}" class="btn btn-primary mb-3">Új film hozzáadása</a>
    @endif

    {{-- Kereső --}}
    <form action="{{ route('films.index') }}" method="GET" class="mb-4">
        <input type="text" name="needle" placeholder="Keresés..." value="{{ request('needle') }}">
        <button type="submit" class="btn btn-secondary">Keresés</button>
    </form>

    {{-- Film lista --}}
    @if(count($entities) > 0)
        <div class="media-grid">

            @foreach($entities as $film)

                <div class="media-card">

                    <h3>{{ $film['title'] ?? 'N/A' }}</h3>

                    <p><strong>Rendező:</strong> {{ $film['director'] ?? 'N/A' }}</p>
                    <p><strong>Megjelenés:</strong> {{ $film['release_date'] ?? 'N/A' }}</p>
                    <p><strong>Hossz:</strong> {{ $film['length'] ?? 'N/A' }} perc</p>

                    {{-- Admin műveletek --}}
                    @if($isAuthenticated)
                        <div style="margin-top: 12px;">
                            <a href="{{ route('films.edit', $film['id']) }}" class="btn btn-warning btn-sm">
                                Szerkesztés
                            </a>

                            <form action="{{ route('films.destroy', $film['id']) }}"
                                  method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm"
                                        onclick="return confirm('Biztos törlöd?')">
                                    Törlés
                                </button>
                            </form>
                        </div>
                    @endif

                </div>

            @endforeach
        </div>
    @else
        <p class="media-empty">Nincsenek filmek</p>
    @endif

</div>
@endsection
