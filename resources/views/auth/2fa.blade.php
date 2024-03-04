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

    {{--    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>--}}
    {{--    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>--}}

    <![endif]-->
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
                    <div class="card-body">
                        <div class="row justify-content-center">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">{{ __('Two Factor Authentication') }}</div>

                                    <div class="card-body">
                                        <p>{{ __('Please enter your one-time password to complete your login.') }}</p>

                                        <form method="POST" action="{{ route('2fa.verify') }}">
                                            @csrf

                                            <div class="form-group row">
                                                <label for="one_time_password" class="col-md-4 col-form-label text-md-right">{{ __('One Time Password') }}</label>

                                                <div class="col-md-6">
                                                    <input id="one_time_password" type="text" class="form-control @error('one_time_password') is-invalid @enderror" name="one_time_password" required autofocus>

                                                    @error('one_time_password')
                                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-group row mb-0">
                                                <div class="col-md-8 offset-md-4">
                                                    <button type="submit" class="btn btn-primary">
                                                        {{ __('Verify') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr/>
                    <div class="text-center opacity-8 mt-3">Copyright © Kiaboo 2023</div>
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

</script>

</body>

</html>