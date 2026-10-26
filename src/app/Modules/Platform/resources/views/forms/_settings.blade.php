{{-- Title, description and response settings — shared by forms/create and the builder. --}}
<div class="mb-3">
    <label for="title" class="form-label">Title</label>
    <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $form?->title) }}" required maxlength="255" placeholder="e.g. Workshop feedback survey">
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label for="description" class="form-label">Description <span class="aq-muted fw-normal">(optional)</span></label>
    <textarea id="description" name="description" rows="3" maxlength="2000" class="form-control">{{ old('description', $form?->description) }}</textarea>
</div>
<fieldset class="mb-3">
    <legend class="form-label">Who can respond</legend>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="requires_login" name="requires_login" value="1" @checked(old('requires_login', $form?->requires_login))>
        <label class="form-check-label" for="requires_login">Only signed-in members</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="one_response_per_user" name="one_response_per_user" value="1" @checked(old('one_response_per_user', $form?->one_response_per_user))>
        <label class="form-check-label" for="one_response_per_user">One response per member (needs sign-in)</label>
    </div>
</fieldset>
<div class="row g-3">
    <div class="col-md-7">
        <label for="confirmation_message" class="form-label">Thank-you message <span class="aq-muted fw-normal">(optional)</span></label>
        <input type="text" id="confirmation_message" name="confirmation_message" maxlength="500" class="form-control"
               value="{{ old('confirmation_message', $form?->confirmation_message) }}" placeholder="Thanks — we will be in touch.">
    </div>
    <div class="col-md-5">
        <label for="closes_at" class="form-label">Closes automatically <span class="aq-muted fw-normal">(optional)</span></label>
        <input type="datetime-local" id="closes_at" name="closes_at" class="form-control @error('closes_at') is-invalid @enderror"
               value="{{ old('closes_at', $form?->closes_at?->format('Y-m-d\TH:i')) }}">
        @error('closes_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
