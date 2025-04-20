@isset($distributeur)
    <table width="100%" cellpadding="5" cellspacing="5">
        <tr><td align="center" nowrap colspan="4">
                <div class="card-header stat-widget card-shadow-danger border-danger">
                    <span class="d-inline-block pr-2">
                    <i class="fa fa-handshake fa-2x"></i>
                    </span>
                    <span class="d-inline-block"><h4><b>{{$distributeur->name_distributeur}}</b></h4></span>
                </div>
            </td></tr>
        <tr>
            <td><h6>Téléphone : </h6></td><td><h6><span style="text-decoration-line: underline; display: flex">#{{$distributeur->phone}}</span></h6></td>
            <td><h6>Status : </h6></td>
            <td><h6><span style="text-decoration-line: underline; display: flex">
                @if($distributeur->status==0)
                    <span class="badge bg-danger">Bloqué</span>
                @else
                    <span class="badge bg-success">Actif</span>
                @endif
                </span></h6>
            </td>
        </tr>
        <tr>
            <td><h6>Contact : </h6></td><td><h6><span style="text-decoration-line: underline; display: flex">{{$distributeur->name_contact."  ".$distributeur->surname_contact}}</span></h6></td>
            <td><h6>Email : </h6></td><td><h6><span style="text-decoration-line: underline; display: flex">{{$distributeur->email}}</span></h6></td>
        </tr>

        <tr><td align="center" nowrap colspan="4">
                <div class="card-header stat-widget card-shadow-danger border-danger"></div>
            </td></tr>

    </table>

    <p/>
@endisset
