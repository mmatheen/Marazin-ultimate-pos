{{-- <x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout> --}}


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>ARB - Distribution</title>
    <link rel="shortcut icon" href="{{ URL::to('assets/img/ARB Logo.png') }}">
    <link rel="stylesheet" href="{{ URL::to('assets/plugins/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ URL::to('assets/plugins/feather/feather.css') }}">
    <link rel="stylesheet" href="{{ URL::to('assets/plugins/icons/flags/flags.css') }}">
    <link rel="stylesheet" href="{{ URL::to('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ URL::to('assets/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="assets/plugins/toastr/toatr.css">
    <link rel="stylesheet" href="{{ URL::to('assets/css/style.css') }}">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>
    <style>
        .invalid-feedback {
            font-size: 14px;
        }

    </style>

    {{-- for sound --}}
    <audio class="successSound" src="{{ URL::to('assets/sounds/success.mp3') }}"></audio>
    <audio class="errorSound" src="{{ URL::to('assets/sounds/error.mp3') }}"></audio>


    <div class="main-wrapper login-body">
        <div class="login-wrapper">

            <div class="container">
                <div class="loginbox">
                    <div class="login-left">
                        <img class="img-fluid" src="{{'/assets/images/ARB Logo.png' }}" alt="ARB login image">
                    </div>

                    <div class="login-right">
                        <div class="login-right-wrap">
                            <div class="d-flex justify-content-center mb-3">
                                <a href="https://marazin.lk/" target="_blank"><img class="img-fluid" width="100px" height="100px" src="{{ URL::to('assets/img/ARB Logo.png') }}" alt="Logo"></a>
                            </div>
                            <h1 class="mb-4 text-center">ARB Distribution</h1>
                            {{-- <p class="account-subtitle">Need an account? <a href="{{ route('register') }}">Sign Up</a></p> --}}
                            <form action="{{ route('login') }}" method="POST">
                                @csrf
                                {{-- <div class="form-group">
                                    <label>Email<span class="login-danger">*</span></label>
                                    <input type="email" id="email" class="form-control @error('email') is-invalid @enderror" autofocus autocomplete="username" name="email">
                                    <span class="profile-views"><i class="fas fa-envelope"></i></span>
                                    @if ($errors->has('email'))
                                    <span class="text-danger mt-2">{{ $errors->first('email') }}</span>
                                    @endif

                                </div> --}}
                                <div class="form-group">
                                    <label for="login">Username or Email<span class="login-danger">*</span></label>
                                    <input type="text" id="login" class="form-control @error('login') is-invalid @enderror" autofocus autocomplete="username" name="login">
                                    <span class="profile-views"><i class="fas fa-user"></i></span>

                                    @if ($errors->has('login'))
                                        <span class="text-danger mt-2">{{ $errors->first('login') }}</span>
                                    @endif
                                </div>


                                <div class="form-group">
                                    <label>Password<span class="login-danger">*</span></label>
                                    <input type="password" class="form-control pass-input1 @error('password') is-invalid @enderror" name="password" autocomplete="current-password">
                                    <span class="profile-views feather-eye-off toggle-password1"></span>
                                    @if ($errors->has('password'))
                                    <span class="text-danger mt-2">{{ $errors->first('password') }}</span>
                                    @endif
                                </div>

                                <div class="forgotpass">
                                    <div class="remember-me">
                                        <input class="form-check-input" id="remember_me" type="checkbox" name="remember">
                                        <label class="form-check-label mt-1" for="remember_me">
                                         Remember me
                                        </label>
                                    </div>
                                    <div>

                                    </div>
                                    {{-- <a href="{{ route('password.request') }}">Forgot Password?</a> --}}

                                </div>
                                {{-- <div class="g-recaptcha" data-sitekey="6Ld6hFEqAAAAAMr2FkeEfhEzC8zTgUxfyRUJq1OJ"></div> --}}
                                <div class="form-group">
                                    <button class="btn btn-primary btn-block mt-3" type="submit">Login</button>
                                </div>
                            </form>
                            {{-- <div class="login-or">
                                <span class="or-line"></span>
                                <span class="span-or">or</span>
                            </div> --}}
                            {{-- <div class="social-login">
                                <a href="#"><i class="fab fa-google-plus-g"></i></a>
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            </div> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ URL::to('assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ URL::to('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ URL::to('assets/js/feather.min.js') }}"></script>
    <script src="assets/plugins/toastr/toastr.min.js"></script>
    <script src="assets/plugins/toastr/toastr.js"></script>
    <script src="{{ URL::to('assets/js/script.js') }}"></script>

    <script>
        $(document).ready(function() {
            var successSound = document.querySelector('.successSound');
            var errorSound = document.querySelector('.errorSound');

            @if(Session::has('toastr-success'))
            toastr.success("{{ Session::get('toastr-success') }}");
            successSound.play();
            @endif

            @if(Session::has('toastr-error'))
            toastr.error("{{ Session::get('toastr-error') }}");
            errorSound.play();
            @endif

            @if(Session::has('toastr-warning'))
            toastr.warning("{{ Session::get('toastr-warning') }}");
            @endif

            @if(Session::has('toastr-info'))
            toastr.info("{{ Session::get('toastr-info') }}");
            @endif
        });

    </script>


 {{-- it will remove the localStorage details, when logout and go to login page script start --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Clear specific items from local storage
        localStorage.removeItem('selectedLocationId');
        localStorage.removeItem('selectedLocationName');
    });
</script>
{{-- it will remove the localStorage details, when logout and go to login page script end --}}


</body>

</html>
