@isset($listservices)
    <select class="form-select"  id="service" name="service" onchange="">
        <option value="">Tous les services</option>
        @if($listservices)
            @foreach($listservices as $s)
                @if(isset($service))
                    @if($s->id == $service)
                        <option  value="{{ $s->id }}" selected >{{$s->name_service}}</option>
                    @else
                        <option  value="{{ $s->id }}" >{{$s->name_service}}</option>
                    @endif
                @else
                    <option  value="{{ $s->id }}" >{{$s->name_service}}</option>
                @endif
            @endforeach
        @endif
    </select>
@endisset
