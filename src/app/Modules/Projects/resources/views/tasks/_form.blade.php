{{-- Fields shared by "Add a task" and "Edit task".  $task is null when adding. --}}
@php use App\Modules\Projects\Models\ProjectTask; @endphp
<div class="mb-3">
    <label for="task_title" class="form-label">Task</label>
    <input type="text" id="task_title" name="title" value="{{ old('title', $task?->title) }}" maxlength="150" required
           class="form-control @error('title') is-invalid @enderror" @error('title') aria-describedby="task_title_error" @enderror>
    @error('title')<div id="task_title_error" class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label for="task_description" class="form-label">Details <span class="aq-muted fw-normal">(optional)</span></label>
    <textarea id="task_description" name="description" rows="3" maxlength="2000" class="form-control @error('description') is-invalid @enderror">{{ old('description', $task?->description) }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="row g-2 mb-3">
    <div class="col-sm-6">
        <label for="task_assignee" class="form-label">Assigned to</label>
        <select id="task_assignee" name="assignee_id" class="form-select @error('assignee_id') is-invalid @enderror" @error('assignee_id') aria-describedby="task_assignee_error" @enderror>
            <option value="">Nobody yet</option>
            @foreach ($workers as $worker)
                <option value="{{ $worker->id }}" @selected((int) old('assignee_id', $task?->assignee_id) === $worker->id)>{{ $worker->name }}{{ $worker->id === auth()->id() ? ' (me)' : '' }}</option>
            @endforeach
        </select>
        @error('assignee_id')<div id="task_assignee_error" class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-sm-6">
        <label for="task_priority" class="form-label">Priority</label>
        <select id="task_priority" name="priority" class="form-select @error('priority') is-invalid @enderror">
            @foreach (ProjectTask::PRIORITIES as $value => [$label])
                <option value="{{ $value }}" @selected(old('priority', $task?->priority ?? 'normal') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="mb-3">
    <label for="task_due" class="form-label">Due date <span class="aq-muted fw-normal">(optional)</span></label>
    <input type="date" id="task_due" name="due_on" value="{{ old('due_on', $task?->due_on?->format('Y-m-d')) }}"
           class="form-control @error('due_on') is-invalid @enderror" @error('due_on') aria-describedby="task_due_error" @enderror>
    @error('due_on')<div id="task_due_error" class="invalid-feedback">{{ $message }}</div>@enderror
</div>
