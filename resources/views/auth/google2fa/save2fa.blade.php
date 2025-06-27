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
        <div class="col-md-6 col-lg-6">
            <div class="card login-box-container">
                <div class="card-body">
                    <div class="authent-logo">
                        <img src="{{asset("assets/images/logo%402x.png")}}" alt="">
                    </div>
                    <div class="authent-text">

                         <p><div class="card-header">{{ __('Authentification à Double Facteur') }}</div></p><br/>
                         <p>Saisissez le code généré par votre application Authenticator.</p>
                    </div>


                    <form method="POST" action="{{ route('2fa.assign') }}">
                        @csrf
                        <div class="mb-3">
                            <div class="form-floating">
                                <input id="one_time_password" type="text" class="form-control @error('one_time_password') is-invalid @enderror" name="one_time_password" required autofocus>
                                <label for="one_time_password">Code OTP</label>
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

                        @error('one_time_password')
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
                        <br/><br/>
                        <div class="d-grid">

                            <button type="submit" class="btn btn-danger m-b-xs">
                                {{ __('Verify') }}
                            </button>
                        </div>
                    </form>

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
