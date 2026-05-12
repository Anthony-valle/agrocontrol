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
                            <h5 class="card-title fs-4 fw-bold text-success mt-2 mb-0">Recuperar contraseña</h5>
                            <p class="small text-muted mb-0">Ingresa tu usuario para generar un enlace temporal.</p>
                        </div>

                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if(session('reset_link'))
                            <div class="alert alert-warning">
                                <div class="fw-semibold mb-1">Enlace temporal generado:</div>
                                <a href="{{ session('reset_link') }}" class="small" style="word-break: break-all;">{{ session('reset_link') }}</a>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label for="usuario" class="form-label fw-semibold">Usuario</label>
                                <input type="text" name="usuario" id="usuario" class="form-control" value="{{ old('usuario') }}" required>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-success w-100 fw-bold" style="background-color: #2d6a4f; border: none; border-radius: 10px;">
                                    Generar enlace temporal
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
