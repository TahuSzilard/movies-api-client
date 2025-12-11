@extends('layouts.app')
<link rel="stylesheet" href="{{ asset('css/media.css') }}">

@section('content')
@php($routePrefix = 'directors')

<div class="media-page">
    <div class="mb-3">
        <a href="{{ route($routePrefix.'.export.csv') }}" class="btn btn-secondary">CSV export</a>
        <a href="{{ route($routePrefix.'.export.pdf') }}" class="btn btn-secondary">PDF export</a>
    </div>

    <h1 class="media-title">Rendezők</h1>

    {{-- Üzenetek --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Új hozzáadása --}}
    @if($isAuthenticated)
        <a href="{{ route('directors.create') }}" class="btn btn-primary mb-3">Új rendező hozzáadása</a>
    @endif

    {{-- Kereső --}}
    <form action="{{ route('directors.index') }}" method="GET" class="mb-4">
        <input type="text" name="needle" placeholder="Keresés..." value="{{ request('needle') }}">
        <button type="submit" class="btn btn-secondary">Keresés</button>
    </form>

    {{-- Rendezők listája --}}
    @if(count($entities) > 0)
        <div class="media-grid">

            @foreach($entities as $director)

                <div class="media-card">

                    <h3>{{ $director['name'] ?? 'N/A' }}</h3>

                    <p><strong>Létrehozva:</strong> {{ $director['created_at'] ?? 'N/A' }}</p>
                    <p><strong>Frissítve:</strong> {{ $director['updated_at'] ?? 'N/A' }}</p>

                    {{-- Admin műveletek --}}
                    @if($isAuthenticated)
                        <div style="margin-top: 12px;">
                            <a href="{{ route('directors.edit', $director['id']) }}" class="btn btn-warning btn-sm">
                                Szerkesztés
                            </a>

                            <form action="{{ route('directors.destroy', $director['id']) }}"
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
        <p class="media-empty">Nincsenek rendezők</p>
    @endif

</div>
@endsection
