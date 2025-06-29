@extends('base')
@section('content')
    <div class='loader'>
        <div class='spinner-grow text-danger' role='status'>
            <span class='sr-only'>Loading...</span>
        </div>
    </div>
    <div class="page-content">
        <div class="main-wrapper">
            <div class="row">

                <div class="col-md-12 col-xl-12">
                    <div class="card-header stat-widget card-shadow-danger border-danger">
                            <span class="d-inline-block pr-2">
                            <i class="fa fa-user-cog fa-2x"></i>
                            </span>
                        <span class="d-inline-block"><h4><b>&nbsp;&nbsp;GESTION DES AGENTS</b></h4></span>
                    </div>

                </div>
            </div><p/>

            @if($errors->any())
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class='alert alert-danger alert-dismissible'>
                            <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close" title="Close">x</button>
                            <span><h6><i class='icon fa fa-ban'></i>{{$errors->first()}}</h6></span>
                        </div>
                    </div>
                </div>
            @endif
            @if(session('error'))
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class='alert alert-danger alert-dismissible'>
                            <button type='button' class='close' data-bs-dismiss='alert' aria-hidden='true' title="Close">×</button>
                            <span><h6><i class='icon fa fa-ban'></i>{{session('error')}}</h6></span>
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
                    <div class="card-header">
                        <div class="card-group">
                            <div class="btn-actions-pane-right">
                                <div role="group" class="btn-group-sm btn-group-lg">
                                    @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                    <form action="#" method="POST" name="exportform" enctype="multipart/form-data">
                                        @csrf
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                            <i class="fa fa-plus"></i>  Ajouter un agent
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-header">
                        <div class="card table-widget">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="add-row" class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid">
                                        <thead>
                                        <tr>
                                            <th scope="col"></th>
                                            <th scope="col">Nom</th>
                                            <th scope="col">Prénom</th>
                                            <th scope="col">Téléphone</th>
{{--                                            <th scope="col">Ville</th>--}}
                                            <th scope="col">Distributeur</th>
                                            <th scope="col">Date de création</th>
                                            <th scope="col">Date de mise à jour</th>
                                            <th scope="col">Dernière connexion</th>
                                            <th scope="col">Source</th>
                                            <th scope="col">Statut</th>
                                            @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                            <th scope="col"></th>
                                            @endif
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @isset($agents)
                                            @if($agents->isNotEmpty())
                                                @foreach($agents as $c)
                                                    <tr>
                                                        <td>{{$loop->iteration}}</td>
                                                        <td>{{$c->name}}</td>
                                                        <td nowrap>{{$c->surname}}</td>
                                                        <td nowrap>{{$c->telephone}}</td>
{{--                                                        <td nowrap>{{$c->ville !=null?$c->ville->name_ville:""}}</td>--}}
                                                        <td nowrap>{{$c->distributeur !=null?$c->distributeur->name_distributeur:""}}</td>
                                                        <td nowrap align="center">{{$c->created_at}}</td>
                                                        <td nowrap align="center">{{$c->updated_at}}</td>
                                                        <td nowrap align="center">{{$c->last_connexion}}</td>
                                                        <td nowrap align="center">{{$c->application==1?"WEB":"API"}}</td>
                                                        @if($c->status==0)
                                                            <td align="center" title="Utilisateur bloqué">
                                                                <form action="{{route("debloqueAgent",[$c->id])}}" id="frmDebloque{{$c->id}}" name="frmDebloque{{$c->id}}">
                                                                    @csrf
                                                                    <a type="button" class="btn" style="border: none;" title="Cliquez pour débloquer" onclick="javascript:document.getElementById('frmDebloque{{$c->id}}').submit();"><i class="fa fa-user-alt-slash" style="color: black"></i></a>
                                                                </form>
                                                            </td>
                                                        @else
                                                            <td align="center" title="Actif">
                                                                <form action="{{route("bloqueAgent",[$c->id])}}" id="frmBloque{{$c->id}}" name="frmBloque{{$c->id}}">
                                                                    @csrf
                                                                    <a type="button" class="btn" style="border: none;" onclick="javascript:document.getElementById('frmBloque{{$c->id}}').submit();"  title="Cliquez pour bloquer"><i class="fa fa-user" style="color:red;"></i></a>
                                                                </form>
                                                            </td>
                                                        @endif
                                                        @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::SUPADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::ADMIN->value || Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                                        <td align="center" nowrap>
                                                            <a type="button" class="btn" style="border: none; color: red" data-bs-toggle="modal" data-bs-target="#staticBackdropEdit" title="Editer l'utilisateur" onclick="getUpdateUser({{$c->id}})" >
                                                                <i class="fa fa-pen"></i>
                                                            </a>
                                                            <a href="{{route("deleteAgent",[$c->id])}}" type="button" class="btn" style="border: none; color: red" title="Supprimer l'agent" >
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                        </td>
                                                        @endif
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
                    <h5 class="modal-title" id="staticBackdropLabel">Créer un nouvel agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route("setNewAgent")}}" id="frmNewagent" name="frmNewagent">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Nom *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                                    <input type="text" id="name" name="name" class="form-control" placeholder="Nom *" aria-label="Name" required>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Prénom *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                                    <input type="text" id="surname" name="surname" class="form-control" placeholder="Prénom *" aria-label="surname" required>
                                </div>
                            </div>
                        </div><p/>
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Téléphone *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                    <input type="number" id="telephone" name="telephone" class="form-control" placeholder="Téléphone *" aria-label="Téléphone" required>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Email *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                    <input type="text" id="email" name="email" class="form-control" placeholder="Email *" aria-label="email" required>
                                </div>
                            </div>
                        </div><p/>
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Numéro CNI *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-id-card"></i></span>
                                    <input type="texte" id="numcni" name="numcni" class="form-control" placeholder="Numéro CNI *" aria-label="Téléphone" required>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Date de fin de validité CNI *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                    <input type="date" id="datecni" min="<?php echo date('Y-m-d') ?>" name="datecni" class="form-control" placeholder="Date fin validité CNI *" aria-label="datecni" title="Date fin de validité CNI" required>
                                </div>
                            </div>
                        </div><p/>
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Ville *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text" id="basic-addon1"><i class="fa fa-flag"></i></span>
                                    <select class="form-select" name="ville" id="ville" onchange="" required onclick="">
                                        @isset($ville)
                                            @if($ville)
                                                <option  value="" >Sélectionner une ville</option>
                                                @foreach($ville as $s)
                                                    <option  value="{{ $s->id }}" >{{$s->name_ville}}</option>
                                                @endforeach
                                            @endif
                                        @endisset
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Quartier *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-city"></i></span>
                                    <input type="text" id="quartier" name="quartier" class="form-control" placeholder="Quartier *" aria-label="quartier" required>
                                </div>
                            </div>
                        </div>
                        <p/>
                        <div class="row">
                            <div class="col-md-12 col-lg-12">
                                <label class="form-label">Adresse *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-address-card"></i></span>
                                    <textarea type="text" id="adresse" maxlength="150" name="adresse" class="form-control" placeholder="Adresse *" aria-label="adresse" required></textarea>
                                </div>
                            </div>
                        </div>
                        <hr style="color: red">
                        <div class="row">
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Distributeur *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-hand-holding"></i></span>
                                    <select class="form-select" name="mondistributeur" id="mondistributeur" required>
                                        @isset($mesdistributeurs)
                                            @if($mesdistributeurs)
                                                <option  value="" >Sélectionner une distributeur</option>
                                                @foreach($mesdistributeurs as $s)
                                                    <option  value="{{ $s->id }}" >{{$s->name_distributeur}}</option>
                                                @endforeach
                                            @endif
                                        @endisset
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-6">
                                <label class="form-label">Seuil d'alerte *</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-money-bill"></i></span>
                                    <input type="number" id="seuil" name="seuil" class="form-control" placeholder="Seuil d'alerte" aria-label="seuil" required>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-danger">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="staticBackdropEdit" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Editer l'agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div id="updateUser">

                </div>
            </div>
        </div>
    </div>

    <script>
        function getUpdateUser(id) {
            $.ajax({
                url: "/agent/edit/"+id,
                type: "GET",
                success: function (data) {
                    $("#updateUser").html(data);
                }
            });
        }
    </script>
@endsection
