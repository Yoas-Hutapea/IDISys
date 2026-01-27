@extends('layouts.account')

@section('title', 'Log in')

@section('content')
<!-- Responsive Login Form Container -->
<div class="container-fluid h-100 position-relative">
    <div class="row h-100">
        <!-- Login Form Wrapper - Menimpa di atas background -->
        <div class="col-12 col-md-7 col-lg-5 col-xl-4 d-flex align-items-center justify-content-start">
            <div class="login-wrapper w-100">
                <div class="login-form-container">
                    <!-- Logo Section -->
                    <div class="text-center mb-4">
                        <img src="{{ asset('assets/media/logos/idi-logos-lightmode.png') }}" alt="IDEANET Logo" class="logo img-fluid" style="max-height: 60px;">
                    </div>

                    <!-- Login Form -->
                    <form id="loginForm" method="post" action="{{ url('/login') }}" class="needs-validation" novalidate>
                        @csrf

                        <!-- Alert Messages -->
                        @if (session('expired'))
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Sesi login Anda telah berakhir. Silakan login kembali.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- User ID Input -->
                        <div class="mb-3">
                            <label for="UserId" class="form-label fw-semibold text-dark">
                                <i class="fas fa-user me-2 text-primary"></i>User ID
                            </label>
                            <input id="UserId" name="UserId" class="form-control form-control-lg"
                                   autocomplete="username" aria-required="true"
                                   placeholder="Masukkan User ID Anda"
                                   value="{{ old('UserId') }}"
                                   required />
                            @error('UserId')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Password Input -->
                        <div class="mb-3 position-relative">
                            <label for="Password" class="form-label fw-semibold text-dark">
                                <i class="fas fa-lock me-2 text-primary"></i>Password
                            </label>
                            <div class="input-group">
                                <input id="Password" name="Password" type="password" class="form-control form-control-lg"
                                       autocomplete="current-password" aria-required="true"
                                       placeholder="Masukkan Password Anda"
                                       required />
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-left: none; z-index: 10;">
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                            </div>
                            @error('Password')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Validation Summary -->
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                @foreach ($errors->all() as $message)
                                    <div>{{ $message }}</div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button id="login-submit" type="submit" class="btn btn-primary btn-lg fw-semibold">
                                <i class="fas fa-sign-in-alt me-2"></i>Log In
                            </button>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-building me-1"></i>
                            PT. Tower Bersama Infrastructure
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('lib/jquery-validation/dist/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('lib/jquery-validation-unobtrusive/jquery.validate.unobtrusive.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.querySelector('input[type="password"]');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (passwordInput) {
                        const isPassword = passwordInput.type === 'password';
                        passwordInput.type = isPassword ? 'text' : 'password';

                        // Toggle the icon
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.classList.toggle('fa-eye');
                            icon.classList.toggle('fa-eye-slash');
                        }
                    }
                });
            }

            // Form validation
            const form = document.getElementById('loginForm');
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            }
        });
    </script>
@endsection
