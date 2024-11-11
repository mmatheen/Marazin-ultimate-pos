{{-- <x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
</div>

<!-- Session Status -->
<x-auth-session-status class="mb-4" :status="session('status')" />

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <!-- Email Address -->
    <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div class="flex items-center justify-end mt-4">
        <x-primary-button>
            {{ __('Email Password Reset Link') }}
        </x-primary-button>
    </div>
</form>
</x-guest-layout> --}}


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Certificate Verify</title>
    <link rel="shortcut icon" href="{{ URL::to('assets/img/favicon.png') }}">
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
                        <img class="img-fluid" src="{{'/assets/images/1728739712.jpg' }}" alt="student login image">
                    </div>

                    <div class="login-right">
                        <div class="login-right-wrap">
                            <div class="d-flex justify-content-center mb-3">
                                <a href="https://marazin.lk/" target="_blank"><img class="img-fluid" width="100px" height="100px" src="{{ URL::to('assets/img/logo-small.png') }}" alt="Logo"></a>
                            </div>
                            <h1 class="mb-4">Certificate Verification to verify.marazin.lk</h1>
                                <div class="mb-3">
                                    @if (session('status'))
                                    <span class="text-success mb-4">{{ session('status') }}</span>
                                @endif
                                </div>

                            <form action="{{ route('password.email') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Email<span class="login-danger">*</span></label>
                                    <input type="email" id="email" class="form-control @error('email') is-invalid @enderror" autofocus autocomplete="username" name="email">
                                    <span class="profile-views"><i class="fas fa-envelope"></i></span>
                                    @if ($errors->has('email'))
                                    <span class="text-danger mt-2">{{ $errors->first('email') }}</span>
                                    @endif

                                </div>
                                <div class="form-group">
                                    <button class="btn btn-primary btn-block" type="submit">Email Password Reset Link</button>
                                </div>
                                <div class="form-group mb-0">

                                    <a href="{{ route('login') }}" class="btn btn-primary primary-reset btn-block" type="button">Back to Login</a>
                                </div>


                            </form>

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

</body>

</html>
