@extends('layouts.login')

@section('contenido')
<main style="background-color: #dbf5e1; min-height: 100vh; display: flex; align-items: center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
                    <div class="card-body bg-white p-4 p-lg-5">
                        <div class="text-center mb-3">
                            <img src="{{ asset('NiceAdmin/assets/img/agrocontrol.png') }}" alt="Logo AgroControl" style="max-width: 140px; height: auto;">
                            <h5 class="card-title fs-4 fw-bold text-success mt-2 mb-0">Nueva contraseña</h5>
                            <p class="small text-muted mb-0">Define una nueva contraseña para el usuario {{ $usuario }}.</p>
                        </div>

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.reset') }}" class="row g-3">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="usuario" value="{{ $usuario }}">

                            <div class="col-12">
                                <label for="password" class="form-label fw-semibold">Nueva contraseña</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <label for="password_confirmation" class="form-label fw-semibold">Confirmar contraseña</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-success w-100 fw-bold" style="background-color: #2d6a4f; border: none; border-radius: 10px;">
                                    Guardar nueva contraseña
                                </button>
                            </div>

                            <div class="col-12 text-center">
                                <a href="{{ route('login') }}" class="small text-decoration-none">Volver al inicio de sesión</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
