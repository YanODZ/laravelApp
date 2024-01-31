<!-- resources/views/auth/login.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login</title>

    <!-- Add Bootstrap CDN link -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/enterprise.js" async defer></script>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-10 col-sm-10 mt-1">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>
                <div class="card-body">
                    <form id="loginForm" method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="correo" class="col-md-4 col-form-label text-md-right">{{ __('Correo') }}</label>

                            <div class="col-md-6">
                                <input id="correo" type="email" class="form-control @error('correo') is-invalid @enderror" name="correo" value="{{ old('correo') }}" maxlength="60" required autocomplete="correo" autofocus onpaste="return false;">

                                @error('correo')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="contraseña" class="col-md-4 col-form-label text-md-right">{{ __('Contraseña') }}</label>

                            <div class="col-md-6">
                                <input id="contraseña" type="password" class="form-control @error('contraseña') is-invalid @enderror" name="contraseña" maxlength="60" required autocomplete="current-password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="La contraseña debe contener al menos un número, una letra minúscula, una letra mayúscula y tener al menos 8 caracteres de longitud." onpaste="return false;">

                                @error('contraseña')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="google2fa_code" class="col-md-4 col-form-label text-md-right">{{ __('Tengo un código') }}</label>

                            <div class="col-md-6">
                                <input id="google2fa_code" type="text" class="form-control @error('google2fa_code') is-invalid @enderror" name="google2fa_code" maxlength="6" autofocus onpaste="return false;">

                                @error('google2fa_code')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-4">
                            <div class="g-recaptcha" name="captchaInput" data-sitekey="6LcehVspAAAAALZ6f5Qzq_K4wAZmbdeKz4zwY_9w"></div>
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <a class="btn btn-link" href="{{ route('register') }}">
                                    {{ __('No tienes una cuenta? Registrate') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
    $('#loginForm').submit(function (e) {
        e.preventDefault();
        var captchaValue = $('#g-recaptcha-response').val();
        if(captchaValue != ''){
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function (response) {
                    var token = response.access_token;
                    if (token) {
                        alert('Login exitoso!');
                        window.location.href = '/welcome?token=' + token;
                    }
                },
                error: function (xhr, status, error) {
                    alert(JSON.parse(xhr.responseText).error);
                }
            });
        }else{
            alert('Verifica el captcha!');
        }
    });
});

</script>

</body>
</html>
