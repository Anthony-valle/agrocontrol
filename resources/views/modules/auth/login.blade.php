@extends('layouts.login')

@section('contenido')
<main style="background-color: #dbf5e1; min-height: 100vh; display: flex; align-items: center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
                    <div class="row g-0">
                        
                        <div class="col-md-6 d-none d-md-flex align-items-center justify-content-center bg-white p-4 border-end">
                            <img src="{{ asset('NiceAdmin/assets/img/agrocontrol.png') }}" 
                                 alt="Logo AgroControl" 
                                 style="max-width: 90%; height: auto; filter: drop-shadow(0px 10px 15px rgba(0,0,0,0.1));">
                        </div>

                        <div class="col-md-6 bg-white p-4 p-lg-5">
                            <div class="d-flex d-md-none justify-content-center mb-3">
                                <img src="{{ asset('NiceAdmin/assets/img/agrocontrol.png') }}"
                                     alt="Logo AgroControl"
                                     style="max-width: 170px; height: auto; filter: drop-shadow(0px 10px 15px rgba(0,0,0,0.1));">
                            </div>

                            <div class="pt-2 pb-2">
                                <h5 class="card-title text-center pb-0 fs-4 fw-bold text-success">AgroControl</h5>
                                <p class="text-center small text-muted">Inicie sesión para acceder al sistema</p>
                            </div>

                            <form class="row g-3 needs-validation" novalidate method="POST" action="{{ route('logear') }}">
                                @csrf
                                
                                <div class="col-12">
                                    <label for="usuario" class="form-label fw-semibold">Usuario</label>
                                    <div class="input-group shadow-sm">
                                        <span class="input-group-text bg-light border-end-0 text-muted">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <input type="text" name="usuario" class="form-control border-start-0 bg-light" id="usuario" required placeholder="Ingrese su usuario" value="{{ old('usuario') }}">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="password" class="form-label fw-semibold">Contraseña</label>
                                    <div class="input-group shadow-sm">
                                        <span class="input-group-text bg-light border-end-0 text-muted">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" name="password" class="form-control border-start-0 bg-light" id="password" required placeholder="••••••••">
                                        <button class="btn btn-light border border-start-0" type="button" id="togglePassword">
                                            <i class="bi bi-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <button class="btn btn-success w-100 fw-bold py-2 shadow-sm" type="submit" style="background-color: #2d6a4f; border: none; border-radius: 10px;">
                                        INGRESAR AL SISTEMA
                                    </button>
                                </div>

                                <div class="col-12 text-center mt-1">
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="small text-decoration-none">¿Olvidaste tu contraseña?</a>
                                    @else
                                        <span class="small text-primary">¿Olvidaste tu contraseña?</span>
                                    @endif
                                </div>

                                <div class="col-12 text-center mt-2">
                                    <small class="text-muted">
                                        Si no puedes ingresar, usa la opción de recuperación para generar un enlace temporal.
                                    </small>
                                </div>
                            </form>

                            <div class="mt-3">
                                @if(session('success'))
                                    <div class="alert alert-success border-0 shadow-sm mb-2" style="border-radius: 10px;">
                                        {{ session('success') }}
                                    </div>
                                @endif

                                @if (isset($errors) && $errors->any())
                                    <div class="alert alert-danger border-0 shadow-sm mb-0" style="border-radius: 10px;">
                                        <ul class="mb-0 small ps-3">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <div class="credits mt-4 text-center small pt-3 border-top">
                                <span class="text-muted">Diseñado por</span> 
                                <a href="#" class="text-success fw-bold text-decoration-none">Anthony Valle</a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('#togglePassword');
        const passwordField = document.querySelector('#password');
        const toggleIcon = document.querySelector('#toggleIcon');

        togglePassword.addEventListener('click', function () {
            // Cambiar el tipo de input
            const isPassword = passwordField.getAttribute('type') === 'password';
            passwordField.setAttribute('type', isPassword ? 'text' : 'password');
            
            // Cambiar el icono (Usando clases de Bootstrap Icons para mayor consistencia)
            toggleIcon.classList.toggle('bi-eye');
            toggleIcon.classList.toggle('bi-eye-slash');
        });
    });
</script>
@endsection