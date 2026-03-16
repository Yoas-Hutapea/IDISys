@extends('layouts.account')

@section('title', 'Change Password')

@section('content')
<div class="container-fluid h-100 position-relative">
    <div class="row h-100">
        <div class="col-12 col-md-7 col-lg-5 col-xl-4 d-flex align-items-center justify-content-start">
            <div class="login-wrapper w-100">
                <div class="login-form-container">
                    <div class="text-center mb-4">
                        <img src="{{ asset('assets/media/logos/idi-logos-lightmode.png') }}" alt="IDEANET Logo" class="logo img-fluid" style="max-height: 60px;">
                    </div>

                    <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
                        <i class="fas fa-key me-2"></i>
                        <strong>Default password detected.</strong> For security, you must change your password to one of your choice before continuing.
                    </div>

                    <form method="post" action="{{ route('account.password.change.submit') }}" class="needs-validation" novalidate id="changePasswordForm">
                        @csrf

                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold text-dark">
                                <i class="fas fa-lock me-2 text-primary"></i>New Password
                            </label>
                            <div class="input-group">
                                <input id="new_password" name="new_password" type="password" class="form-control form-control-lg"
                                       autocomplete="new-password" placeholder="Min. 8 characters, letters and numbers"
                                       required minlength="8" />
                                <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword" style="border-left: none; z-index: 10;">
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                            </div>
                            @error('new_password')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label fw-semibold text-dark">
                                <i class="fas fa-lock me-2 text-primary"></i>Confirm New Password
                            </label>
                            <div class="input-group">
                                <input id="new_password_confirmation" name="new_password_confirmation" type="password" class="form-control form-control-lg"
                                       autocomplete="new-password" placeholder="Repeat new password"
                                       required minlength="8" />
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword" style="border-left: none; z-index: 10;">
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                @foreach ($errors->all() as $message)
                                    <div>{{ $message }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                                <i class="fas fa-check me-2"></i>Save New Password
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-building me-1"></i>
                            PT. Infrastruktur Digital Indonesia
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupToggle(inputId, btnId) {
        const btn = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        if (btn && input) {
            btn.addEventListener('click', function() {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                }
            });
        }
    }
    setupToggle('new_password', 'toggleNewPassword');
    setupToggle('new_password_confirmation', 'toggleConfirmPassword');

    const form = document.getElementById('changePasswordForm');
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
