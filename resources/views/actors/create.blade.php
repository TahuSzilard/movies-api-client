@extends('layouts.app')
<link rel="stylesheet" href="{{ asset('css/media.css') }}">

@section('content')
<div class="media-page">

    <h1 class="media-title">Új színész létrehozása</h1>

    {{-- Hibák --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('actors.store') }}" method="POST" class="media-card" style="max-width: 500px;">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label"><strong>Név</strong></label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary mt-2">Mentés</button>
        <a href="{{ route('actors.index') }}" class="btn btn-secondary mt-2">Mégse</a>
    </form>
</div>
@endsection
