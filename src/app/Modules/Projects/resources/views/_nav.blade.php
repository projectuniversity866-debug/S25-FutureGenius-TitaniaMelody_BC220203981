{{-- Sections of one project.  @include('projects::_nav', ['active' => 'tasks']) --}}
<x-tabs label="Project sections" :items="[
    ['label' => 'Overview', 'url' => route('projects.show', $project), 'active' => $active === 'overview'],
    ['label' => 'Feedback', 'url' => route('projects.threads.index', $project), 'active' => $active === 'feedback',
     'count' => $project->threads()->where('status', 'open')->count()],
    ['label' => 'Tasks', 'url' => route('projects.tasks.index', $project), 'active' => $active === 'tasks',
     'count' => $project->tasks()->where('status', '!=', 'done')->count()],
]" />
