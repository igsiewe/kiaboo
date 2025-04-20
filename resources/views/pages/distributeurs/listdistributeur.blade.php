@extends('base')
@section('content')
    <div class='loader'>
        <div class='spinner-grow text-primary' role='status'>
            <span class='sr-only'>Loading...</span>
        </div>
    </div>
    <div class="page-content">
        <div class="main-wrapper">
            <div class="row">

                <div class="col-md-12 col-xl-12">
                    <div class="card-header stat-widget card-shadow-danger border-danger">
                            <span class="d-inline-block pr-2">
                            <i class="fa fa-handshake fa-2x"></i>
                            </span>
                        <span class="d-inline-block"><h4><b>&nbsp;&nbsp;GESTION DES DISTRIBUTEURS</b></h4></span>
                    </div>

                </div>
            </div><p/>

            @if($errors->any())
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class='alert alert-danger alert-dismissible'>
                            <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close" title="Close">x</button>
                            <span><h6><i class='icon fa fa-ban'></i> Erreur! {{$errors->first()}}</h6></span>
                        </div>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class='alert alert-danger alert-dismissible'>
                            <button type='button' class='close' data-bs-dismiss='alert' aria-hidden='true' title="Close">×</button>
                            <span><h6><i class='icon fa fa-ban'></i> Erreur! {{session('error')}}</h6></span>
                        </div>
                    </div>
                </div>
            @endif
            @if(session('success'))
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class='alert alert-success alert-dismissible'>
                            <button type='button' class='close' data-bs-dismiss='alert' aria-hidden='true' title="Close">×</button>
                            <span><h6><i class='icon fa fa-user-check'></i> {{session('success')}}</h6></span>
                        </div>
                    </div>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    @if(\Illuminate\Support\Facades\Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::BACKOFFICE->value || \Illuminate\Support\Facades\Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value)
                    <div class="card-header">
                        <div class="card-group">
                            <div class="btn-actions-pane-right">
                                <div role="group" class="btn-group-sm btn-group-lg">
                                    <form action="#" method="POST" name="exportform" enctype="multipart/form-data">
                                        @csrf
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                            <i class="fa fa-plus"></i>  Ajouter un distributeur
                                        </button>

                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="card-header">
                        <div class="card table-widget">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="add-row" class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid">
                                        <thead>
                                        <tr>
                                            <th scope="col"></th>
                                            <th scope="col">Distributeur</th>
                                            <th scope="col">Contact</th>
                                            <th scope="col">Téléphone</th>
                                            <th scope="col">Region</th>
                                            <th scope="col">Date de création</th>
                                            <th scope="col">Date mise à jour</th>
                                            <th scope="col">Source</th>
                                            <th scope="col">Statut</th>

                                            <th scope="col"></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @isset($distributeurs)
                                            @if($distributeurs->isNotEmpty())
                                                @foreach($distributeurs as $c)
                                                    <tr>
                                                        <td align="right">{{$loop->iteration}}</td>
                                                        <td nowrap>{{$c->name_distributeur}}</td>
                                                        <td nowrap>{{$c->name_contact." ".$c->surname_contact}}</td>
                                                        <td nowrap>{{$c->phone}}</td>
                                                        <td nowrap>{{$c->region->name_region}}</td>
                                                        <td nowrap align="center">{{$c->created_at}}</td>
                                                        <td nowrap align="center">{{$c->updated_at}}</td>
                                                        <td nowrap align="center">{{$c->application==1?"Web":"API"}}</td>
                                                        @if($c->status==0)
                                                            <td align="center" title="Distributeur bloqué">
                                                                <form action="{{route("debloqueDistributeur",[$c->id])}}" id="frmDebloque{{$c->id}}" name="frmDebloque{{$c->id}}">
                                                                    @csrf
                                                                    <a type="button" class="btn" style="border: none;" title="Cliquez pour débloquer" onclick="javascript:document.getElementById('frmDebloque{{$c->id}}').submit();"><i class="fa fa-thumbs-down" style="color: black"></i></a>
                                                                </form>
                                                            </td>
                                                        @else
                                                            <td align="center" title="Actif">
                                                                <form action="{{route("bloqueDistributeur",[$c->id])}}" id="frmBloque{{$c->id}}" name="frmBloque{{$c->id}}">
                                                                    @csrf
                                                                    <a type="button" class="btn" style="border: none;" onclick="javascript:document.getElementById('frmBloque{{$c->id}}').submit();"  title="Cliquez pour bloquer"><i class="fa fa-thumbs-up" style="color:red;"></i></a>
                                                                </form>
                                                            </td>
                                                        @endif

                                                        <td align="center" nowrap>
                                                            <a type="button" class="btn" style="border: none; color: red" data-bs-toggle="modal" data-bs-target="#staticBackdropEdit" title="Editer le distributeur" onclick="getUpdateDistributeur({{$c->id}})" >
                                                                <i class="fa fa-pen"></i>
                                                            </a> <a type="button" class="btn" style="border: none; color: red" data-bs-toggle="modal" data-bs-target="#staticBackdropEdit" title="Liste des agents" onclick="getListAgent({{$c->id}})" >
                                                                <i class="fa fa-users"></i>
                                                            </a>
                                                            </a>
                                                            <a href="{{route("deleteDistributeur",[$c->id])}}" type="button" class="btn" style="border: none; color: red" title="Supprimer le distributeur"  >
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endisset

                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal -->
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Créer un distributeur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route("setNewDistributeur")}}" id="frmNewDistributeur" name="frmNewDistributeur">
                    @csrf
                    <div class="modal-body">

                        <div class="row">
                            <div class="col-md-12 col-lg-12">
                                <div class="form-group">
                                    <label class="form-label" for="name_distributeur">Distributeur</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-handshake"></i></span>
                                    <input type="text" id="name_distributeur" name="name_distributeur" class="form-control" placeholder="Distributeur *" aria-label="Distributeur" required>
                                </div>
                                </div>
                            </div>

                        </div><p/>
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="name_contact">Nom contact</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                                    <input type="text" id="name_contact" name="name_contact" class="form-control" placeholder="Nom contact *" aria-label="Nom contact" required>
                                </div>
                                </div>
                            </div>

                            <div class="col-md-12 col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="surname_contact">Prénom contact</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                                    <input type="text" id="surname_contact" name="surname_contact" class="form-control" placeholder="Prénom contact *" aria-label="Prénom contact" required>
                                </div>
                                </div>
                            </div>
                        </div><p/>
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="telephone">Téléphone</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                    <input type="number" id="telephone" name="telephone" class="form-control" placeholder="Téléphone *" aria-label="Téléphone" required>
                                </div>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <div class="form-group">
                                    <label class="form-label" for="email">Email</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                    <input type="text" id="email" name="email" class="form-control" placeholder="Email *" aria-label="email" required>
                                </div>
                                </div>
                            </div>
                        </div><p/>

                        <div class="row">
                            <div class="col-md-12 col-lg-12">
                                <div class="form-group">
                                    <label class="form-label" for="region">Région</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text" id="basic-addon1"><i class="fa fa-flag"></i></span>
                                    <select class="form-select" name="region" id="region" onchange="" required onclick="">
                                        @isset($regions)
                                            @if($regions)
                                                <option  value="" >Sélectionner une region</option>
                                                @foreach($regions as $s)
                                                    <option  value="{{ $s->id }}" >{{$s->name_region}}</option>
                                                @endforeach
                                            @endif
                                        @endisset
                                    </select>
                                </div>
                                </div>
                            </div>

                        </div>
                        <p/>
                        <div class="row">
                            <div class="col-md-12 col-lg-12">
                                <div class="form-group">
                                    <label class="form-label" for="adresse">Adresse</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-address-card"></i></span>
                                    <textarea id="adresse" name="adresse" class="form-control" maxlength="250"  placeholder="Adresse *" aria-label="adresse" required></textarea>
                                </div>
                                </div>
                            </div>
                        </div>
                        <p/>
                        <div class="row">
                            <div class="col-md-12 col-lg-12">
                                <div class="form-group">
                                    <label class="form-label" for="plafond_alerte">Plafond alerte</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-money-bill"></i></span>
                                    <input type="number" id="plafond_alerte" name="plafond_alerte" class="form-control" placeholder="Plafond alerte *" aria-label="Plafond alerte" required>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="modal-footer">
                        <button type="button" title="Fermer" class="btn btn-outline-danger" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" title="Enregistrer" class="btn btn-danger">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="staticBackdropEdit" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div id="updateDistributeur">

                </div>
            </div>
        </div>
    </div>

    <script>
        function getUpdateDistributeur(id) {
            document.getElementById("updateDistributeur").innerHTML = "";
            $.ajax({
                url: "/approvisionnement/distributeur/edit/donnees/"+id,
                type: "GET",
                success: function (data) {
                    $("#updateDistributeur").html(data);
                }
            });
        }
        function getListAgent(id) {
            document.getElementById("updateDistributeur").innerHTML = "";
            $.ajax({
                url: "/approvisionnement/distributeur/agent/list/"+id,
                type: "GET",
                success: function (data) {
                    $("#updateDistributeur").html(data);
                }
            });
        }
    </script>
@endsection
