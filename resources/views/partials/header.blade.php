<div class="page-header">
    <nav class="navbar navbar-expand-lg d-flex justify-content-between">
        <div id="navbarNav">
            <ul class="navbar-nav" id="leftNav">
                <li class="nav-item">
                    <a class="nav-link" id="sidebar-toggle" href="#"><i data-feather="arrow-left"></i></a>
                </li>
                <li class="nav-item"><a class="nav-link" href="https://www.kiaboo.net">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Settings</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Help</a></li>
            </ul>
        </div>

        <div id="headerNav">
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link profile-dropdown" href="#" id="profileDropDown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ asset('assets/images/avatars/profile-image-1.png') }}" alt="Profile">
                    </a>
                    <div class="dropdown-menu dropdown-menu-end profile-drop-menu">
                        <a class="dropdown-item" href="#"><i data-feather="key"></i> Password</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('fermer') }}"><i data-feather="log-out"></i> Logout</a>
                    </div>
                </li>
                <li class="nav-item">
                    <div class="widget-content-left ml-3 header-user-info">
                        <div class="widget-heading">{{ Auth::user()->surname }} {{ Auth::user()->name }}</div>
                        <div class="widget-subheading">
                            {{ \App\Models\TypeUser::find(Auth::user()->type_user_id)->name_type_user }}
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</div>
