<div class="title">
    <h1>{{ $title }}</h1>

    @isset($subtitle)
        <h2>{{ $subtitle }}</h2>
    @endisset

    @isset($editLink)
        <form id="form" method="POST" action="{{ route('search.edit') }}">
            @csrf
            @foreach(request()->all() as $key => $value)
                @if(is_array($value))
                    @foreach($value as $subkey => $subvalue)
                        <input type="hidden" name="{{ $key }}[{{ $subkey }}]" value="{{ $subvalue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="d-flex flex-wrap gap-2" style="margin-top: 0.5rem;">
                <button id="editSearchBtn" type="submit" class="btn btn-sm btn-outline-warning mb-1"><i class="fa-sharp fa-pencil"></i> Edit filters</button>
                <button id="bookmarkBtn" type="button" class="btn btn-sm btn-outline-warning mb-1"><i class="fa-sharp fa-bookmark"></i> Bookmark search</button>
            </div>

            <script>
                document.getElementById('bookmarkBtn').addEventListener('click', function(event){
                    if(window.umami){
                        umami.track('Interactions', {interaction: `Bookmark search`})
                    }
                    alert("To save these search filters, manually bookmark this page in your browser.")
                })
            </script>
        </form>        
    @endisset

</div>