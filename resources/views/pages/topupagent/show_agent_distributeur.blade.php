@isset($listagents)
    <select class="form-select" name="agent" id="agent" onchange="">
        @isset($listagents)
            @if($listagents->count()>0)
                <option  value="" >Tous les agents</option>
                @foreach($listagents as $s)
                    @if(isset($agent))
                        @if($s->id == $agent)
                            <option  value="{{ $s->id }}" selected >{{$s->login." | ".$s->name." ".$s->surname}}</option>
                        @else
                            <option  value="{{ $s->id }}" >{{$s->login." | ".$s->name." ".$s->surname}}</option>
                        @endif
                    @else
                        <option  value="{{ $s->id }}" >{{$s->login." | ".$s->name." ".$s->surname}}</option>
                    @endif
                @endforeach
            @endif
        @endisset
    </select>
@endif
