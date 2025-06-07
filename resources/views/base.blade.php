<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Responsive Admin Dashboard Template">
    <meta name="keywords" content="admin,dashboard">
    <meta name="author" content="stacks">
    <title>Kiaboo</title>

    <!-- Fonts and Styles -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,700,800&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/font-awesome/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/perfectscroll/perfect-scrollbar.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/apexcharts/apexcharts.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/DataTables/datatables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/main.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/loading.css') }}" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">

    <style>
        .jqstooltip {
            position: absolute;
            left: 0;
            top: 0;
            visibility: hidden;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            font: 10px arial, sans-serif;
            text-align: left;
            white-space: nowrap;
            padding: 5px;
            border: 1px solid white;
            z-index: 10000;
        }
        .jqsfield {
            color: white;
            font: 10px arial, sans-serif;
            text-align: left;
        }
        .active-page > a,
        a.active-page {
            background-color: #f0f0f0;
            color: #000;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="page-container">
    @include('partials.header')
    <div class="page-sidebar">
        <ul class="list-unstyled accordion-menu">
            <li class="sidebar-title">Main</li>
            @if(Auth::user()->hasRole(['super-admin', 'Administrateur', 'Distributeur', 'Front-office', 'Back-office']))
                <li class="{{ request()->routeIs('dashboard') ? 'active-page' : '' }}">
                    <a href="{{ route('dashboard') }}"><i data-feather="home"></i>Dashboard</a>
                </li>
            @endif

            @if(Auth::user()->hasRole(['super-admin', 'Distributeur', 'Front-office', 'Back-office']))
                <li class="sidebar-title">Transactions</li>
                <li class="{{ request()->routeIs('listTransactions') ? 'active-page' : '' }}">
                    <a href="{{ route('listTransactions') }}"><i data-feather="list"></i>Transactions</a>
                </li>
            @endif

            @if(Auth::user()->hasRole(['super-admin', 'administrateur', 'comptable']))
                <li class="{{ request()->routeIs('listAgentCommissions', 'listDistributeurCommissions') ? 'active-page' : '' }}">
                    <a href="#"><i data-feather="gift"></i>Commissions <i class="fa fa-chevron-right dropdown-icon"></i></a>
                    <ul style="{{ request()->routeIs('listAgentCommissions', 'listDistributeurCommissions') ? 'display: block;' : '' }}">
                        <li><a class="{{ request()->routeIs('listAgentCommissions') ? 'active-page' : '' }}" href="{{ route('listAgentCommissions') }}"><i class="fa fa-star"></i>Agents</a></li>
                        <li><a class="{{ request()->routeIs('listDistributeurCommissions') ? 'active-page' : '' }}" href="{{ route('listDistributeurCommissions') }}"><i class="fa fa-star"></i>Distributeurs</a></li>
                    </ul>
                </li>
            @endif

            @if(Auth::user()->hasRole(['super-admin', 'back-office']))
                <li class="{{ request()->routeIs('transactionEnattente') ? 'active-page' : '' }}">
                    <a href="#"><i data-feather="alert-circle"></i>Réconciliation <i class="fa fa-chevron-right dropdown-icon"></i></a>
                    <ul style="{{ request()->routeIs('transactionEnattente') ? 'display: block;' : '' }}">
                        <li><a class="{{ request()->routeIs('transactionEnattente') ? 'active-page' : '' }}" href="{{ route('transactionEnattente') }}"><i class="fa fa-history"></i>Trans. en attente</a></li>
                    </ul>
                </li>
            @endif

            @if(Auth::user()->hasRole(['super-admin', 'front-office', 'back-office']))
                <li class="sidebar-title">Opérations</li>
                <li class="{{ request()->routeIs('topupAgent') ? 'active-page' : '' }}">
                    <a href="{{ route('topupAgent') }}"><i data-feather="bell"></i>Recharge agent</a>
                </li>
                <li class="{{ request()->routeIs('getApproDistributor') ? 'active-page' : '' }}">
                    <a href="{{ route('getApproDistributor') }}"><i data-feather="credit-card"></i>Recharge distributeur</a>
                </li>
            @endif

            @if(Auth::user()->hasRole(['super-admin', 'administrateur']))
                <li class="sidebar-title">Management</li>
                <li class="{{ request()->routeIs('listDistributeur', 'listAgent', 'listProspect') ? 'active-page' : '' }}">
                    <a href="#"><i data-feather="smile"></i>Partenaires <i class="fa fa-chevron-right dropdown-icon"></i></a>
                    <ul style="{{ request()->routeIs('listDistributeur', 'listAgent', 'listProspect') ? 'display: block;' : '' }}">
                        <li><a class="{{ request()->routeIs('listDistributeur') ? 'active-page' : '' }}" href="{{ route('listDistributeur') }}"><i class="fa fa-handshake"></i>Distributeurs</a></li>
                        <li><a class="{{ request()->routeIs('listAgent') ? 'active-page' : '' }}" href="{{ route('listAgent') }}"><i class="fa fa-user-cog"></i>Agents</a></li>
                        <li><a class="{{ request()->routeIs('listProspect') ? 'active-page' : '' }}" href="{{ route('listProspect') }}"><i class="fa fa-users"></i>Prospects</a></li>
                    </ul>
                </li>
            @endif

            @if(Auth::user()->hasRole(['super-admin', 'it']))
                <li class="{{ request()->routeIs('grilleCommission') ? 'active-page' : '' }}">
                    <a href="#"><i data-feather="settings"></i>Paramètres <i class="fa fa-chevron-right dropdown-icon"></i></a>
                    <ul style="{{ request()->routeIs('grilleCommission') ? 'display: block;' : '' }}">
                        <li><a href="#"><i class="fa fa-box"></i>Partenaires</a></li>
                        <li><a href="#"><i class="fa fa-wind"></i>Services</a></li>
                        <li><a class="{{ request()->routeIs('grilleCommission') ? 'active-page' : '' }}" href="{{ route('grilleCommission') }}"><i class="fa fa-star"></i>Grille commission</a></li>
                    </ul>
                </li>
            @endif

            @if(Auth::user()->hasRole(['super-admin', 'administrateur']))
                <li class="{{ request()->routeIs('listUtilisateurs') ? 'active-page' : '' }}">
                    <a href="{{ route('listUtilisateurs') }}"><i data-feather="users"></i>Utilisateurs</a>
                </li>
            @endif

            @if(Auth::user()->hasRole(['super-admin', 'auditeur']))
                <li class="sidebar-title">Contrôle</li>
                <li class="{{ request()->routeIs('log-viewer.index') ? 'active-page' : '' }}">
                    <a href="#"><i data-feather="globe"></i>Journal <i class="fa fa-chevron-right dropdown-icon"></i></a>
                    <ul style="{{ request()->routeIs('log-viewer.index') ? 'display: block;' : '' }}">
                        <li><a class="{{ request()->routeIs('log-viewer.index') ? 'active-page' : '' }}" href="{{ route('log-viewer.index') }}"><i class="fa fa-folder"></i>Log</a></li>
                        <li><a href="#"><i class="fa fa-calendar"></i>Activités</a></li>
                    </ul>
                </li>
            @endif
        </ul>
    </div>
    <main>
        @yield('content')
    </main>
</div>

<!-- Scripts -->
<script src="{{ asset('assets/plugins/jquery/jquery-3.4.1.min.js') }}"></script>
<script src="{{ asset('assets/js/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/feather.min.js') }}"></script>
<script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/plugins/perfectscroll/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assets/plugins/DataTables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/js/main.min.js') }}"></script>
<script src="{{ asset('assets/js/pages/datatables.js') }}"></script>
</body>
</html>
