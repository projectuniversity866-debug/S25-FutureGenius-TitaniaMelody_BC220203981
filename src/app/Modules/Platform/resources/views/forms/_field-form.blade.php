{{-- Add a field ($field = null) or edit one. Ids are prefixed so several of these can share a page. --}}
@php $p = $field ? 'f'.$field->id.'_' : 'new_'; @endphp
<div class="row g-2">
    <div class="col-md-5">
        <label for="{{ $p }}type" class="form-label">Type</label>
        <select id="{{ $p }}type" name="type" class="form-select form-select-sm">
            @foreach (\App\Modules\Platform\Models\FormField::TYPES as $key => [$label])
                <option value="{{ $key }}" @selected(($field?->type ?? 'short_text') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-7">
        <label for="{{ $p }}label" class="form-label">Question</label>
        <input type="text" id="{{ $p }}label" name="label" required maxlength="255" class="form-control form-control-sm"
               value="{{ $field?->label }}" placeholder="e.g. How useful was the workshop?">
    </div>
    <div class="col-md-7">
        <label for="{{ $p }}help" class="form-label">Hint <span class="aq-muted fw-normal">(optional)</span></label>
        <input type="text" id="{{ $p }}help" name="help" maxlength="300" class="form-control form-control-sm" value="{{ $field?->help }}">
    </div>
    <div class="col-md-5 d-flex align-items-end">
        <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" id="{{ $p }}is_required" name="is_required" value="1" @checked($field?->is_required)>
            <label class="form-check-label" for="{{ $p }}is_required">Required</label>
        </div>
    </div>
    <div class="col-12">
        <label for="{{ $p }}options" class="form-label">Choices <span class="aq-muted fw-normal">(for choice and dropdown questions — one per line)</span></label>
        <textarea id="{{ $p }}options" name="options" rows="3" class="form-control form-control-sm">{{ $field ? implode("\n", $field->options ?? []) : '' }}</textarea>
    </div>
</div>
