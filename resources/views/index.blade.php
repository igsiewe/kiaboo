<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<!-- Mirrored from polygons.space/circl/theme/templates/admin/login.html by HTTrack Website Copier/3.x [XR&CO'2014], Thu, 17 Aug 2023 14:19:17 GMT -->
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Responsive Admin Dashboard Template">
    <meta name="keywords" content="admin,dashboard">
    <meta name="author" content="stacks">
    <!-- The above 6 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <!-- Title -->
    <title>Kiaboo</title>

    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,700,800&amp;display=swap" rel="stylesheet">
    <link href="{{asset("assets/plugins/bootstrap/css/bootstrap.min.css")}}" rel="stylesheet">
    <link href="{{asset("assets/plugins/font-awesome/css/all.min.css")}}" rel="stylesheet">
    <link href="{{asset("assets/plugins/perfectscroll/perfect-scrollbar.css")}}" rel="stylesheet">

    <link rel="shortcut icon" type="image/x-icon" href="{{asset("assets/images/favicon.ico")}}">
    <!-- Theme Styles -->
    <link href="{{asset("assets/css/main.min.css")}}" rel="stylesheet">
    <link href="{{asset("assets/css/custom.css")}}" rel="stylesheet">
    <!-- PWA  -->
    <meta name="theme-color" content="#6777ef"/>
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">
    <link rel="manifest" href="{{ asset('/manifest.json') }}">

</head>
<body class="login-page">
<div class='loader'>
    <div class='spinner-grow text-danger' role='status'>
        <span class='sr-only'>Loading...</span>
    </div>
</div>
<div class="container">
    <div class="row justify-content-md-center">
        <div class="col-md-12 col-lg-6">
            <div class="card login-box-container">
                <div class="card-body">
                    <div class="authent-logo">
                        <img src="{{asset("assets/images/logo%402x.png")}}" alt="">
                    </div>
                    <div class="authent-text">
                        <p>KIABOO</p>

                    </div>

                    <form method="post" action="{{route('login')}}" id="formConnexion" name="formConnexion">
                        @csrf
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="login" name="login" placeholder="name@example.com" required value="{{ (Cookie::get('email') !== null) ? Cookie::get('login') : old('login') }}" autofocus>
                                <label for="floatingInput">Email address</label>
                            </div>
                        </div>
                        @error('login')
                        <div class="col-md-12">
                            <div class="position-relative">
                                <span class="invalid-feedback" role="alert">
                                   <strong>{{ $message }}</strong>
                                 </span>
                            </div>
                        </div>
                        @enderror
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" value="{{ (Cookie::get('password') !== null) ? Cookie::get('password') : null }}">
                                <label for="floatingPassword">Password</label>
                            </div>
                        </div>
                        @error('password')
                        <div class="col-md-12">
                            <div class="position-relative">
                               <span class="invalid-feedback" role="alert">
                                  <strong>{{ $message }}</strong>
                               </span>
                            </div>
                        </div>
                        @enderror
                        @if($errors->any())
                            <div class="position-relative form-group">
                                <div class="input-group">
                                    <div class="col-lg-12 alert alert-danger alert-dismissable">
                                        <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
                                        <span class="text-danger text-center">{{$errors->first()}}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="form-group mt-4 mb-4">
                            <div class="captcha">
                                <span>{!! captcha_img() !!}</span>
                                <button type="button" class="btn btn-danger" class="reload" id="reload">
                                    &#x21bb;
                                </button>
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <input id="captcha" type="text" class="form-control" placeholder="Enter Captcha" name="captcha">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger m-b-xs">Se connecter</button>
                        </div>
                    </form>
{{--                    <div class="authent-reg">--}}
{{--                        <a href="register.html">Mot de passe oublié ?</a></p>--}}
{{--                    </div>--}}
                    <hr/>
                    <div class="text-center opacity-8 mt-3">Copyright © Kiaboo {{\Carbon\Carbon::now()->format('Y')}}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Javascripts -->
<script src="{{asset("assets/plugins/jquery/jquery-3.4.1.min.js")}}"></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="{{asset("assets/plugins/bootstrap/js/bootstrap.min.js")}}"></script>
<script src="https://unpkg.com/feather-icons"></script>
<script src="{{asset("assets/plugins/perfectscroll/perfect-scrollbar.min.js")}}"></script>
<script src="{{asset("assets/js/main.min.js")}}"></script>

<script type="text/javascript">
    $('#reload').click(function () {
        $.ajax({
            type: 'GET',
            url: 'reload-captcha',
            success: function (data) {
                $(".captcha span").html(data.captcha);
            }
        });
    });
</script>
<script src="{{ asset('/sw.js') }}"></script>
<script>
    if ("serviceWorker" in navigator) {
        // Register a service worker hosted at the root of the
        // site using the default scope.
        navigator.serviceWorker.register("/sw.js").then(
            (registration) => {
                console.log("Service worker registration succeeded:", registration);
            },
            (error) => {
                console.error(`Service worker registration failed: ${error}`);
            },
        );
    } else {
        console.error("Service workers are not supported.");
    }
</script>
</body>

</html>
