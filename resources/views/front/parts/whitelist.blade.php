<label for="{{ $id }}-input" class="{{ $labelClass ?? '' }}">
    {{ $label }}
    @isset($locked)
        <i class="fa-sharp fa-lock" id="{{ $id }}LockIcon" @class(['d-none' => ! $locked])></i>
    @endisset
</label>
<u-combobox data-multiple id="{{ $id }}" @class(['d-none' => $locked ?? false])>

    @foreach($selected as $key)
        @isset($whitelistDatabase[$key])
            <data value="{{ $key }}">{{ $whitelistDatabase[$key]->name }}</data>
        @endisset
    @endforeach

    <input list="{{ $id }}-list" id="{{ $id }}-input" placeholder="{{ $placeholder }}" @disabled($locked ?? false)>
    <u-datalist id="{{ $id }}-list" tabindex="-1" hidden>
        <select name="{{ $inputName }}[]" multiple @disabled($locked ?? false)></select>
        @foreach($lists as $list)
            <u-option value="{{ $list->id }}">{{ $list->name }}</u-option>
        @endforeach
    </u-datalist>
</u-combobox>
