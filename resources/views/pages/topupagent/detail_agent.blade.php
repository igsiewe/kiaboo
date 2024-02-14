@isset($agent)
    <div class='loader'>
        <div class='spinner-grow text-primary' role='status'>
            <span class='sr-only'>Loading...</span>
        </div>
    </div>
    <table width="100%" cellpadding="5" cellspacing="5">
        <tr><td align="center" nowrap colspan="4">
                <div class="card-header stat-widget card-shadow-danger border-danger">
                    <span class="d-inline-block pr-2">
                    <i class="fa fa-user fa-2x"></i>
                    </span>
                    <span class="d-inline-block"><h4><b>{{$agent->name." ".$agent->surname." / Réf : #".$agent->login}}</b></h4></span>
                </div>
            </td></tr>
        <tr>
            <td><h6>Référence : </h6></td><td><h6><span style="text-decoration-line: underline; display: flex">#{{$agent->login}}</span></h6></td>
            <td><h6>Status : </h6></td>
            <td><h6><span style="text-decoration-line: underline; display: flex">
                @if($agent->status==0)
                    <span class="badge bg-danger">Bloqué</span>
                @else
                    <span class="badge bg-success">Actif</span>
                @endif
                </span></h6>
            </td>
        </tr>

        <tr>
            <td><h6>Ville : </h6></td><td><h6><span style="text-decoration-line: underline; display: flex">{{$agent->quartier." / ".$agent->ville->name_ville}}</span></h6></td>
            <td><h6>Adresse : </h6></td><td><h6><span style="text-decoration-line: underline; display: flex">{{$agent->adresse}}</span></h6></td>
        </tr>
        <tr><td align="center" nowrap colspan="4">
                <div class="card-header stat-widget card-shadow-danger border-danger"></div>
            </td></tr>

    </table>

    <p/>
@endisset
