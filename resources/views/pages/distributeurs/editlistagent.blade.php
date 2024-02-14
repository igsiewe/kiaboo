@if($agents)
    @if($agents->isNotEmpty())
        <div class="modal-header">
            <h5 class="modal-title" id="staticBackdropLabel">Liste des agents du distributeur {{$agents->first()->distributeur->name_distributeur}}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="card-header">
                <div class="card table-widget">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="add-row" class="table table-hover table-striped table-bordered dataTable dtr-inline" role="grid">
                                <thead>
                                <tr>
                                    <th scope="col"></th>
                                    <th scope="col">Agent</th>
                                    <th scope="col">Plafond</th>
                                    <th scope="col">Solde</th>
                                    <th scope="col">Date de création</th>

                                    <th scope="col">Statut</th>
                                </tr>
                                </thead>
                                <tbody>
                                @isset($agents)
                                    @if($agents->isNotEmpty())
                                        @foreach($agents as $c)
                                            <tr>
                                                <td>{{$loop->iteration}}</td>
                                                <td nowrap>{{$c->name." ".$c->surname}}</td>
                                                <td nowrap align="right">{{number_format($c->seuilapprovisionnement,0,","," ")." ".$money}}</td>

                                                <td nowrap align="right">
                                                    @if(doubleval($c->balance_after)<doubleval($c->seuilapprovisionnement) ||doubleval($c->balance_after)==0)
                                                        <span class="badge badge-danger" style="color:red;">{{number_format($c->balance_after,0,","," ")." ".$money}}</span>
                                                    @else
                                                        {{number_format($c->balance_after,0,","," ")." ".$money}}
{{--                                                        <span class="badge badge-danger">{{number_format($c->balance_after,0,","," ")." ".$money}}</span>--}}
                                                    @endif
                                                </td>
                                                <td nowrap>{{$c->created_at}}</td>

                                                @if($c->status==0)
                                                    <td align="center" title="Utilisateur bloqué">
                                                      <a type="button" class="btn" style="border: none;"><i class="fa fa-user-alt-slash" style="color: black"></i></a>
                                                    </td>
                                                @else
                                                    <td align="center" title="Actif">
                                                       <a type="button" class="btn" style="border: none;"><i class="fa fa-user" style="color:red;"></i></a>
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

        <div class="modal-footer">
            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Fermer</button>
        </div>
@else
    <div class="modal-header">
        <h5 class="modal-title" id="staticBackdropLabel">Liste des agents</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="card-header">
            Liste vide
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Fermer</button>
    </div>
@endif
@endif
