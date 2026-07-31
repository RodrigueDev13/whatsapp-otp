@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
    <div class="card" style="max-width: 380px; margin: 2rem auto;">
        <h2 style="margin-top:0;">Connexion admin</h2>

        @if ($errors->any())
            <p class="error">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <p>
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </p>
            <p>
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </p>
            <button type="submit">Se connecter</button>
        </form>
    </div>
@endsection
