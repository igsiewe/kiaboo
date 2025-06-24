@isset($detailutilisateur)
    <form action="{{route("setUpdateUtilisateur",[$detailutilisateur->id])}}" id="frmUpdateUser" name="frmOperation">
        @csrf
        <div class="modal-body">
            <div class='loader'>
                <div class='spinner-grow text-primary' role='status'>
                    <span class='sr-only'>Loading...</span>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Nom *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Nom *" aria-label="Name" value="{{$detailutilisateur->name}}" required>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Prénom *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                        <input type="text" id="surname" name="surname" class="form-control" value="{{$detailutilisateur->surname}}" placeholder="Prénom *" aria-label="surname" required>
                    </div>
                </div>
            </div><p/>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Téléphone *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-phone"></i></span>
                        <input type="texte" id="telephone" readonly="true" disabled name="telephone" class="form-control" placeholder="Téléphone *" value="{{$detailutilisateur->telephone}}" aria-label="Téléphone" required>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Email *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                        <input type="text" id="email" name="email" class="form-control" placeholder="Email *" aria-label="email"  value="{{$detailutilisateur->email}}"required>
                    </div>
                </div>
            </div><p/>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Numéro CNI *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-id-card"></i></span>
                        <input type="texte" id="numcni"  name="numcni" class="form-control" placeholder="Numéro CNI *" value="{{$detailutilisateur->numcni}}" aria-label="Téléphone" required>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Date de fin de validité CNI *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                        <input type="date" id="datecni" name="datecni" class="form-control" placeholder="Date fin validité CNI *" aria-label="datecni" title="Date fin de validité CNI"  value="{{$detailutilisateur->datecni}}" required>
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
                                        @if($s->id == $detailutilisateur->ville_id)
                                            <option  value="{{ $s->id }}" selected>{{$s->name_ville}}</option>
                                        @else
                                            <option  value="{{ $s->id }}" >{{$s->name_ville}}</option>
                                        @endif
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
                        <input type="text" id="quartier" name="quartier" class="form-control" value="{{$detailutilisateur->quartier}}" placeholder="Quartier *" aria-label="quartier" required>
                    </div>
                </div>
            </div>
            <p/>
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <label class="form-label">Adresse *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-address-card"></i></span>
                        <textarea type="text" id="adresse" name="adresse" maxlength="150" class="form-control" placeholder="Adresse *" aria-label="adresse" required>{{$detailutilisateur->adresse}}</textarea>
                    </div>
                </div>
            </div>
            <p/>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Type user *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-hand-lizard"></i></span>
                        <select class="form-select" name="typeuser" id="typeuser" required onchange="javascript:getListDistributeur(this.value)">
                            @isset($typeUtilisateurs)
                                @if($typeUtilisateurs)

                                    @if(Auth::user()->type_user_id !=\App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                        <option  value="" >Choisir un rôle</option>
                                    @endif
                                    @foreach($typeUtilisateurs as $s)

                                        @if($s->id == $detailutilisateur->type_user_id)
                                            <option  value="{{ $s->id }}" selected >{{strtoupper($s->name_type_user)}}</option>
                                        @else
                                            <option  value="{{ $s->id }}" >{{strtoupper($s->name_type_user)}}</option>
                                        @endif
                                    @endforeach
                                @endif
                            @endisset
                        </select>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <div id="chargeListeDistributeur">

                        @if($detailutilisateur->type_user_id==\App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                            <label class="form-label">Distributeur *</label>
                            <div class="input-group mb-12">
                                <span class="input-group-text"><i class="fa fa-hand-holding"></i></span>

                                <select class="form-select" name="mondistributeur" id="mondistributeur" required>
                                    @isset($mesdistributeurs)
                                        @if($mesdistributeurs)
                                            @if(Auth::user()->type_user_id !=\App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                               <option  value="" >Sélectionner une distributeur</option>
                                            @endif
                                            @foreach($mesdistributeurs as $s)
                                                @if($s->id == $detailutilisateur->distributeur_id)
                                                    <option  value="{{ $s->id }}" selected>{{$s->name_distributeur}}</option>
                                                @else
                                                    <option  value="{{ $s->id }}" >{{$s->name_distributeur}}</option>
                                                @endif
                                            @endforeach
                                        @endif
                                    @endisset
                                </select>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>


        <div class="modal-footer">
            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Fermer</button>
            <button type="submit" class="btn btn-danger">Enregistrer</button>
        </div>
    </form>
@endisset
