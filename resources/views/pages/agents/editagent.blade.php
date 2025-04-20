@isset($detailagent)
    <form action="{{route("setUpdateAgent",[$detailagent->id])}}" id="frmUpdateUser" name="frmOperation">
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
                        <input type="text" id="name" name="name" class="form-control" placeholder="Nom *" aria-label="Name" value="{{$detailagent->name}}" required>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Prénom *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                        <input type="text" id="surname" name="surname" class="form-control" value="{{$detailagent->surname}}" placeholder="Prénom *" aria-label="surname" required>
                    </div>
                </div>
            </div><p/>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Téléphone *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-phone"></i></span>
                        <input type="texte" id="telephone" readonly="true" disabled name="telephone" class="form-control" placeholder="Téléphone *" value="{{$detailagent->telephone}}" aria-label="Téléphone" required>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Email *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                        <input type="text" id="email" name="email" class="form-control" placeholder="Email *" aria-label="email"  value="{{$detailagent->email}}"required>
                    </div>
                </div>
            </div><p/>
            <div class="row">
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Numéro CNI *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-id-card"></i></span>
                        <input type="texte" id="numcni"  name="numcni" class="form-control" placeholder="Numéro CNI *" value="{{$detailagent->numcni}}" aria-label="Téléphone" required>
                    </div>
                </div>
                <div class="col-md-12 col-lg-6">
                    <label class="form-label">Date de fin de validité CNI *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                        <input type="date" id="datecni" name="datecni" class="form-control" placeholder="Date fin validité CNI *" aria-label="datecni" title="Date fin de validité CNI"  value="{{$detailagent->datecni}}" required>
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
                                        @if($s->id == $detailagent->ville_id)
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
                        <input type="text" id="quartier" name="quartier" class="form-control" value="{{$detailagent->quartier}}" placeholder="Quartier *" aria-label="quartier" required>
                    </div>
                </div>
            </div>
            <p/>
            <div class="row">
                <div class="col-md-12 col-lg-12">
                    <label class="form-label">Adresse *</label>
                    <div class="input-group mb-12">
                        <span class="input-group-text"><i class="fa fa-address-card"></i></span>
                        <textarea type="text" id="adresse" name="adresse" maxlength="150" class="form-control" placeholder="Adresse *" aria-label="adresse" required>{{$detailagent->adresse}}</textarea>
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
                                        @if($s->id == $detailagent->distributeur_id)
                                            <option  value="{{ $s->id }}" selected>{{$s->name_distributeur}}</option>
                                        @else
                                            <option  value="{{ $s->id }}" >{{$s->name_distributeur}}</option>
                                        @endif
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
                        <input type="number" id="seuil" name="seuil" class="form-control" value="{{$detailagent->seuilapprovisionnement}}" placeholder="Seuil d'alerte" aria-label="seuil" required>
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
