@extends('layouts.app')
<link rel="stylesheet" href="{{ asset('css/media.css') }}">

@section('content')
@php($routePrefix = 'actors')

<div class="media-page">
    <div class="mb-3">
        <a href="{{ route($routePrefix.'.export.csv') }}" class="btn btn-secondary">CSV export</a>
        <a href="{{ route($routePrefix.'.export.pdf') }}" class="btn btn-secondary">PDF export</a>
    </div>
    <h1 class="media-title">Színészek</h1>

    {{-- Üzenetek --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif


    {{-- Új színész (ha belépett) --}}
    @if($isAuthenticated)
        <a href="{{ route('actors.create') }}" class="btn btn-primary mb-3">Új színész</a>
    @endif

    {{-- Keresés --}}
    <form action="{{ route('actors.index') }}" method="GET" class="mb-4">
        <input type="text" name="needle" placeholder="Keresés..." value="{{ request('needle') }}">
        <button type="submit" class="btn btn-secondary">Keresés</button>
    </form>
    {{-- Színészek listája --}}
    @if(count($entities) > 0)
        <div class="media-grid">
            @foreach($entities as $actor)

                <div class="media-card">

                    {{-- Kép (ha van) --}}
                    @if(!empty($actor['image']))
                        <img src="{{ $actor['image'] }}" alt="{{ $actor['name'] }}"
                             style="width: 100%; border-radius: 8px; margin-bottom: 12px;">
                    @endif

                    {{-- Név --}}
                    <h3>{{ $actor['name'] ?? 'N/A' }}</h3>

                    {{-- Admin műveletek --}}
                    @if($isAuthenticated)
                        <div style="margin-top: 12px;">
                            <a href="{{ route('actors.edit', $actor['id']) }}" class="btn btn-warning btn-sm">
                                Szerkesztés
                            </a>

                            <form action="{{ route('actors.destroy', $actor['id']) }}"
                                  method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Biztosan törlöd a színészt?')">
                                    Törlés
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="media-empty">Nincsenek színészek</p>
    @endif

</div>
@endsection
