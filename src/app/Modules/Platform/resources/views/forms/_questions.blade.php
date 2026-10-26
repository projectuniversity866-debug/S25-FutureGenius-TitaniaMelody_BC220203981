{{-- The questions of a form, as accessible inputs named answers[field id].
     Used by the form's own page and by event registration (roadmap 3.7).
     @include('platform::forms._questions', ['fields' => $form->fields]) --}}
@foreach ($fields as $field)
    @php
        $name = 'answers['.$field->id.']';
        $key = 'answers.'.$field->id;
        $id = 'q'.$field->id;
        $error = $errors->first($key) ?: $errors->first($key.'.*');
        $old = old($key);
        $describedBy = trim(($field->help ? $id.'_help ' : '').($error ? $id.'_error' : ''));
    @endphp
    <div class="aq-form-question">
        @if (in_array($field->type, ['single_choice', 'multiple_choice', 'rating'], true))
            <fieldset @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                <legend class="form-label">{{ $field->label }}@if ($field->is_required)<span class="aq-danger" aria-hidden="true"> *</span><span class="visually-hidden"> (required)</span>@endif</legend>
                @if ($field->type === 'rating')
                    <div class="aq-rating">
                        @foreach (range(1, 5) as $n)
                            <input class="btn-check" type="radio" name="{{ $name }}" id="{{ $id }}_{{ $n }}" value="{{ $n }}" @checked((string) $old === (string) $n)>
                            <label class="btn btn-quiet btn-sm" for="{{ $id }}_{{ $n }}">{{ $n }}<span class="visually-hidden"> out of 5</span></label>
                        @endforeach
                    </div>
                @else
                    @foreach ($field->options ?? [] as $j => $option)
                        <div class="form-check">
                            <input class="form-check-input" id="{{ $id }}_{{ $j }}"
                                   type="{{ $field->type === 'multiple_choice' ? 'checkbox' : 'radio' }}"
                                   name="{{ $field->type === 'multiple_choice' ? $name.'[]' : $name }}" value="{{ $option }}"
                                   @checked($field->type === 'multiple_choice' ? in_array($option, (array) $old, true) : $old === $option)>
                            <label class="form-check-label" for="{{ $id }}_{{ $j }}">{{ $option }}</label>
                        </div>
                    @endforeach
                @endif
            </fieldset>
        @elseif ($field->type === 'consent')
            <div class="form-check">
                <input class="form-check-input @if ($error) is-invalid @endif" type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1"
                       @checked($old) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                <label class="form-check-label" for="{{ $id }}">{{ $field->label }}@if ($field->is_required)<span class="aq-danger" aria-hidden="true"> *</span><span class="visually-hidden"> (required)</span>@endif</label>
            </div>
        @else
            <label for="{{ $id }}" class="form-label">{{ $field->label }}@if ($field->is_required)<span class="aq-danger" aria-hidden="true"> *</span><span class="visually-hidden"> (required)</span>@endif</label>
            @if ($field->type === 'long_text')
                <textarea id="{{ $id }}" name="{{ $name }}" rows="4" maxlength="5000" @class(['form-control', 'is-invalid' => $error])
                          @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @required($field->is_required)>{{ $old }}</textarea>
            @elseif ($field->type === 'dropdown')
                <select id="{{ $id }}" name="{{ $name }}" @class(['form-select', 'is-invalid' => $error])
                        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @required($field->is_required)>
                    <option value="">Choose…</option>
                    @foreach ($field->options ?? [] as $option)
                        <option value="{{ $option }}" @selected($old === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            @else
                <input id="{{ $id }}" name="{{ $name }}" value="{{ is_string($old) ? $old : '' }}" @class(['form-control', 'is-invalid' => $error])
                       type="{{ ['email' => 'email', 'number' => 'number', 'date' => 'date'][$field->type] ?? 'text' }}"
                       @if ($field->type === 'short_text') maxlength="255" @endif
                       @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @required($field->is_required)>
            @endif
        @endif
        @if ($field->help)<div id="{{ $id }}_help" class="form-text">{{ $field->help }}</div>@endif
        @if ($error)<div id="{{ $id }}_error" class="aq-text-sm aq-danger mt-1">{{ $error }}</div>@endif
    </div>
@endforeach
