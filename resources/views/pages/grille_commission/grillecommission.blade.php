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
                            <i class="fa fa-star fa-2x"></i>
                            </span>
                        <span class="d-inline-block"><h4><b>&nbsp;&nbsp;GRILLE DE COMMISSIONNEMENT</b></h4></span>
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
                        <div class="card-group">
                            <div class="btn-actions-pane-right">
                                <div role="group" class="btn-group-sm btn-group-lg">
                                    <form action="#" method="POST" name="exportform" enctype="multipart/form-data">
                                        @csrf
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                            <i class="fa fa-plus"></i>  Ajouter
                                        </button>
                                    </form>

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
                                            <th scope="col">Partenaire</th>
                                            <th scope="col">Service</th>
                                            <th scope="col">Borne Inf.</th>
                                            <th scope="col">Borne Sup.</th>
                                            <th scope="col">Valeur</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Date de mise à jour</th>
                                            <th scope="col">Statut</th>
                                            <th scope="col"></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @isset($grilleCommission)
                                            @if($grilleCommission->isNotEmpty())
                                                @foreach($grilleCommission as $c)
                                                    <tr>
                                                        <td>{{$loop->iteration}}</td>
                                                        <td>{{$c->name_partenaire}}</td>
                                                        <td nowrap>{{$c->name_service}}</td>
                                                        <td nowrap align="right">{{number_format($c->borne_min,0,","," ")." ".$money}}</td>
                                                        <td nowrap align="right">{{number_format($c->borne_max,0,","," ")." ".$money}}</td>
                                                        <td nowrap align="right">{{$c->type_commission=="borne"?number_format($c->amount,0,","," ")." ".$money:$c->taux}}</td>
                                                        <td nowrap>{{$c->type_commission}}</td>
                                                        <td nowrap align="center">{{$c->updated_at}}</td>
                                                        @if($c->status==0)
                                                            <td align="center" title="Non valide">
                                                                <a type="button" class="btn" style="border: none;" title="Non active"><i class="fa fa-thumbs-down" style="color: red"></i></a>
                                                            </td>
                                                            <td align="center" title="Non valide">
                                                                <a type="button" class="btn" style="border: none;" title="Non valide"><i class="fa fa-trash" style="color: black"></i></a>
                                                            </td>
                                                        @else
                                                            <td align="center" title="Valide">
                                                                <a type="button" class="btn" style="border: none;" title="Active"><i class="fa fa-thumbs-up" style="color:black;"></i></a>
                                                            </td>
                                                            <td align="center">
                                                                <form action="{{route("deleteCommission",[$c->id])}}" id="frmDelete{{$c->id}}" name="frmDelete{{$c->id}}">
                                                                    @csrf
                                                                    <a type="button" class="btn" style="border: none;" title="Cliquez pour supprimer" onclick="javascript:document.getElementById('frmDelete{{$c->id}}').submit();"><i class="fa fa-trash" style="color: black"></i></a>
                                                                </form>
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
                    <h5 class="modal-title" id="staticBackdropLabel">Ajouter un nouveau plan de commissionnement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{route("addNewCommission")}}" id="frmCommission" name="frmCommission">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 col-lg-4">
                                <label class="form-label">Partenaire</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-hand-holding"></i></span>
                                    <select class="form-select" name="partenaire" id="partenaire" required onchange="javascript:getService(this.value)">
                                        @isset($partenaires)
                                            @if($partenaires)
                                                <option  value="" >Sélectionner un partenaire</option>
                                                @foreach($partenaires as $s)
                                                    <option  value="{{ $s->id }}" >{{$s->name_partenaire}}</option>
                                                @endforeach
                                            @endif
                                        @endisset
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-4">
                                <label class="form-label">Service</label>
                                <div id="servicePartenaire">
                                    <div class="input-group mb-12">
                                        <span class="input-group-text"><i class="fa fa-check-circle"></i></span>
                                        <select class="form-select" name="service" id="service" required>
                                            <option  value="" >Sélectionner un service</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-4">
                                <label class="form-label">Type</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-cog"></i></span>
                                    <select class="form-select" name="typecommission" id="typecommission" required>
                                        <option  value="" >Sélectionner un type</option>
                                        <option  value="borne" >Borne</option>
                                        <option  value="taux" >Taux</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <p/>
                        <div class="row">
                            <div class="col-md-12 col-lg-4">
                                <label class="form-label">Borne minimale</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-hand-point-down"></i></span>
                                    <input type="number" class="form-control" name="bornemin" id="bornemin" placeholder="Borne minimale" required>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-4">
                                <label class="form-label">Borne maximale</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-hand-point-up"></i></span>
                                    <input type="number" class="form-control" name="bornemax" id="bornemax" placeholder="Borne maximale" required>
                                </div>
                            </div>
                            <div class="col-md-12 col-lg-4">
                                <label class="form-label">Commission</label>
                                <div class="input-group mb-12">
                                    <span class="input-group-text"><i class="fa fa-money-check-alt"></i></span>
                                    <input type="number" class="form-control" step="0.01" min="0" name="commission" id="commission" placeholder="Commission" required>
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


    <script>
        function getService(idPartenaire) {
            document.getElementById("service").options.length=0;
            $.ajax({
                url: "/services/partenaire/"+idPartenaire,
                type: "GET",
                success: function (data) {
                    $("#servicePartenaire").html(data);
                }
            });
        }
    </script>
@endsection
