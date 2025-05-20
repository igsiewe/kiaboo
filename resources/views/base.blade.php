<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Responsive Admin Dashboard Template">
    <meta name="keywords" content="admin,dashboard">
    <meta name="author" content="stacks">
    <!-- Title -->
    <title>Kiaboo</title>


    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,700,800&amp;display=swap" rel="stylesheet">
    <link href="{{asset("assets/plugins/bootstrap/css/bootstrap.min.css")}}" rel="stylesheet">

    <link href="{{asset("assets/plugins/font-awesome/css/all.min.css")}}" rel="stylesheet">
    <link href="{{asset("assets/plugins/perfectscroll/perfect-scrollbar.css")}}" rel="stylesheet">
    <link href="{{asset("assets/plugins/apexcharts/apexcharts.css")}}" rel="stylesheet">

    <!-- Theme Styles -->
    <link href="{{asset("assets/css/main.min.css")}}" rel="stylesheet">
    <link href="{{asset("assets/css/custom.css")}}" rel="stylesheet">

    <link rel="shortcut icon" type="image/x-icon" href="{{asset("assets/images/favicon.ico")}}">
    <link href="{{asset("assets/plugins/DataTables/datatables.min.css")}}" rel="stylesheet">
    <link href="{{asset("assets/css/loading.css")}}" rel="stylesheet">
    <style type="text/css">
        .jqstooltip {
            position: absolute;
            left: 0px;
            top: 0px;
            visibility: hidden;
            background: rgb(0, 0, 0) transparent;
            background-color: rgba(0,0,0,0.6);
        -ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000)";
            filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000);
            color: white;font: 10px arial, san serif;
            text-align: left;
            white-space: nowrap;
            padding: 5px;
            border: 1px solid white;
            z-index: 10000;}.jqsfield {
                                        color: white;
                                        font: 10px arial, san serif;
                                        text-align: left;
                                    }
    </style>
</head>
<body>

<div class="page-container">
    <div class="page-header">
        <nav class="navbar navbar-expand-lg d-flex justify-content-between">
            <div class="" id="navbarNav">
                <ul class="navbar-nav" id="leftNav">
                    <li class="nav-item">
                        <a class="nav-link" id="sidebar-toggle" href="#"><i data-feather="arrow-left"></i></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="https://www.kiaboo.net">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Help</a>
                    </li>
                </ul>
            </div>
            <div class="logo">
                <a class="navbar-brand" href="#"></a>
            </div>
            <div class="" id="headerNav">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link profile-dropdown" href="#" id="profileDropDown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><img src="{{asset("assets/images/avatars/profile-image-1.png")}}" alt=""></a>
                        <div class="dropdown-menu dropdown-menu-end profile-drop-menu" aria-labelledby="profileDropDown">
                            <a class="dropdown-item" href="#"><i data-feather="key"></i>Password</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('fermer') }}"><i data-feather="log-out"></i>Logout</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                    <div class="widget-content-left  ml-3 header-user-info">
                        <div class="widget-heading"> {{Auth::user()->surname." ".Auth::user()->name}} </div>
                        <div class="widget-subheading"> {{\App\Models\TypeUser::where("id",Auth::user()->type_user_id)->get()->first()->name_type_user}} </div>
                    </div>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
    <div class="page-sidebar">
        <ul class="list-unstyled accordion-menu">
            <li class="sidebar-title">
                Main
            </li>
            <li>
                <a href="{{route("dashboard")}}"><i data-feather="home"></i>Dashboard</a>
            </li>
             <li class="sidebar-title">
                Transactions
            </li>
            <li>
                <a href="{{route("listTransactions")}}"><i data-feather="list"></i>Transactions</a>
            </li>
            @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::BACKOFFICE->value)
            <li>
                <a href="#"><i data-feather="gift"></i>Commissions <i class="fa fa-chevron-right dropdown-icon"></i></a>
                <ul class="">
                    <li><a href="{{route("listAgentCommissions")}}"><i class="fa fa-star"></i>Agents</a></li>
                    <li><a href="{{route("listDistributeurCommissions")}}"><i class="fa fa-star"></i>Distributeurs</a></li>
                </ul>
            </li>
            @endif
            @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::AUDIT->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::BACKOFFICE->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ACCOUNTABLE->value)
            <li>
                <a href="#"><i data-feather="alert-circle"></i>Réconciliation <i class="fa fa-chevron-right dropdown-icon"></i></a>
                <ul class="">
                    <li><a href="{{route("transactionEnattente")}}" title="Transactions en attente"><i class="fa fa-history"></i>Trans. en attente</a></li>
                </ul>
            </li>
            @endif
            <li class="sidebar-title">
                Opérations
            </li>
            @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
            <li><a href="{{route("topupAgent")}}"><i data-feather="bell"></i>Recharge agent</a></li>
            @endif
            @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::BACKOFFICE->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::FRONTOFFICE->value)
            <li><a href="{{route("getApproDistributor")}}"><i data-feather="credit-card"></i>Recharge distributeur</a>
            @endif
            </li>
             <li class="sidebar-title">
                Management
            </li>

            <li>
                <a href="index.html"><i data-feather="smile"></i>Partenaires <i class="fa fa-chevron-right dropdown-icon"></i></a>
                <ul class="">
                    @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::BACKOFFICE->value)
                    <li><a href="{{route("listDistributeur")}}"><i class="fa fa-handshake"></i>Distributeurs</a></li>
                    @endif
                    @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                    <li><a href="{{route("listAgent")}}"><i class="fa fa-user-cog"></i>Agents</a></li>
                    @endif
                    @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::BACKOFFICE->value)
                        <li><a href="{{route("listProspect")}}"><i class="fa fa-users"></i>Prospects</a></li>
                    @endif
                </ul>
            </li>
            @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::BACKOFFICE->value)
                <li>
                    <a href="#"><i data-feather="settings"></i>Paramètres <i class="fa fa-chevron-right dropdown-icon"></i></a>
                    <ul class="">
                        <li><a href="#"><i class="fa fa-box"></i>Partenaires</a></li>
                        <li><a href="#"><i class="fa fa-wind"></i>Services</a></li>
                        <li><a href="{{route("grilleCommission")}}"><i class="fa fa-star"></i>Grille commission</a></li>
                    </ul>
                </li>
            @endif
            @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::IT->value)
                <li><a href="{{route("listUtilisateurs")}}"><i data-feather="users"></i>Utilisateurs</a></li>
            @endif

            @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value)
                <li class="sidebar-title">
                    Contrôle
                </li>

                <li>
                    <a href="index.html"><i data-feather="globe"></i>Journal <i class="fa fa-chevron-right dropdown-icon"></i></a>
                    <ul class="">
                        <li><a href="{{route("log-viewer.index")}}"><i class="fa fa-folder"></i>Log</a></li>
                        <li><a href=""><i class="fa fa-calendar"></i>Activités</a></li>
                    </ul>
                </li>
            @endif
        </ul>
    </div>
    @yield('content')

</div>

</body>
<script src="{{asset("assets/plugins/jquery/jquery-3.4.1.min.js")}}"></script>
{{--<script src="https://unpkg.com/@popperjs/core@2"></script>--}}
{{--<script src="https://unpkg.com/feather-icons"></script>--}}
<script src="{{asset("assets/js/popper.min.js")}}"></script>
<script src="{{asset("assets/js/feather.min.js")}}"></script>
<script src="{{asset("assets/plugins/bootstrap/js/bootstrap.min.js")}}"></script>

<script src="{{asset("assets/plugins/perfectscroll/perfect-scrollbar.min.js")}}"></script>
<script src="{{asset("assets/plugins/DataTables/datatables.min.js")}}"></script>
<script src="{{asset("assets/js/main.min.js")}}"></script>
<script src="{{asset("assets/js/pages/datatables.js")}}"></script>

<!-- Javascripts -->

{{--<script src="{{asset("assets/plugins/apexcharts/apexcharts.min.js")}}"></script>--}}


<script type="text/javascript">

    jQuery(function ($) {
        $("ul a")
            .click(function(e) {
                var link = $(this);

                var item = link.parent("li");

                if (item.hasClass("active-page")) {
                    item.removeClass("active-page").children("a").removeClass("active-page");
                } else {
                    item.addClass("active-page").children("a").addClass("active-page");
                }

                if (item.children("ul").length > 0) {
                    var href = link.attr("href");
                    link.attr("href", "#");
                    setTimeout(function () {
                        link.attr("href", href);
                    }, 300);
                    e.preventDefault();
                }
            })
            .each(function() {
                var link = $(this);
                if (link.get(0).href === location.href) {
                    link.addClass("active-page").parents("li").addClass("active-page");
                    return false;
                }
            });
    });

 </script>
</html>
