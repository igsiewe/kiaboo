@isset($mesdistributeurs)

        <label class="form-label">Distributeur *</label>
        <div class="input-group mb-12">
            <span class="input-group-text"><i class="fa fa-hand-holding"></i></span>
            <select class="form-select" name="mondistributeur" id="mondistributeur" required>
                @isset($mesdistributeurs)
                    @if($mesdistributeurs)
                        <option  value="" >Sélectionner une distributeur</option>
                        @foreach($mesdistributeurs as $s)
                            <option  value="{{ $s->id }}" >{{ strtoupper($s->name_distributeur)}}</option>
                        @endforeach
                    @endif
                @endisset
            </select>
        </div>

@endif
