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
                        <span class="d-inline-block"><h4><b>&nbsp;&nbsp;GESTION DES PROPECTS</b></h4></span>
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

                    <div class="card-header">
                        <div class="card table-widget">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="add-row" class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid">
                                        <thead>
                                        <tr>
                                            <th scope="col"></th>
                                            <th scope="col">Genre</th>
                                            <th scope="col">Noms & Prénoms</th>
                                            <th scope="col">Téléphone</th>
                                            <th scope="col">Ville</th>
                                            <th scope="col">Quartier</th>
                                            <th scope="col">Statut</th>
                                            <th scope="col">Date de création</th>

                                            <th scope="col"></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @isset($listProspect)
                                            @if($listProspect->isNotEmpty())
                                                @foreach($listProspect as $c)
                                                    <tr>
                                                        <td>{{$loop->iteration}}</td>
                                                        <td nowrap>{{$c->genre}}</td>
                                                        <td nowrap>{{$c->phone}}</td>
                                                        <td nowrap>{{$c->quartier->ville->name_ville}}</td>
                                                        <td nowrap>{{$c->quartier->name_quartier}}</td>

                                                        @if($c->status==0)
                                                            <td align="center" title="En attente de validation">
                                                              <a type="button" class="btn" style="border: none;" title="En attente de validation"><i class="fa fa-user-alt-slash" style="color: black"></i></a>
                                                            </td>
                                                        @else
                                                            <td align="center" title="Validé">
                                                                   <a type="button" class="btn" style="border: none;"   title="Prospect validé"><i class="fa fa-user" style="color:red;"></i></a>
                                                            </td>
                                                        @endif
                                                        <td nowrap align="center">{{$c->created_at}}</td>
                                                        <td align="center" nowrap>
                                                            <a type="button" class="btn" style="border: none; color: red" data-bs-toggle="modal" data-bs-target="#staticBackdropEdit" title="Editer" onclick="getUpdateProspect({{$c->id}})" >
                                                                <i class="fa fa-pen"></i>
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

    <div class="modal fade" id="staticBackdropEdit" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Détail du propoect</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div id="updatePropect">

                </div>
            </div>
        </div>
    </div>

    <script>
        function getUpdateProspect(id) {
            $.ajax({
                url: "/propect/edit/"+id,
                type: "GET",
                success: function (data) {
                    $("#updatePropect").html(data);
                }
            });
        }

    </script>
@endsection
