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

                    <div id="app">
                        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
                            <div class="container">
                                <a class="navbar-brand" href="{{ url('/') }}">
                                    {{ config('app.name', 'Laravel') }}
                                </a>
                                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                                    <span class="navbar-toggler-icon"></span>
                                </button>

                                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                                    <!-- Left Side Of Navbar -->
                                    <ul class="navbar-nav mr-auto">

                                    </ul>

                                    <!-- Right Side Of Navbar -->
                                    <ul class="navbar-nav ml-auto">
                                        <!-- Authentication Links -->
                                        @guest
                                            <li class="nav-item">
                                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                            </li>
                                            @if (Route::has('register'))
                                                <li class="nav-item">
                                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                                </li>
                                            @endif
                                        @else
                                            <li class="nav-item dropdown">
                                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                                    {{ Auth::user()->name }} <span class="caret"></span>
                                                </a>

                                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                                    <a class="dropdown-item" href="{{ route('2fa.activate') }}">
                                                        {{ __('Set up 2FA') }}
                                                    </a>

                                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                                        {{ __('Logout') }}
                                                    </a>

                                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                                        @csrf
                                                    </form>
                                                </div>
                                            </li>
                                        @endguest
                                    </ul>
                                </div>
                            </div>
                        </nav>

                        <main class="py-4">
                            @yield('content')
                        </main>
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

</body>

</html>

