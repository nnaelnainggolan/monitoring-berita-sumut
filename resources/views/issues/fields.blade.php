<label class="md:col-span-2">Judul isu<input class="field" name="title" value="{{ old('title', $issue->title ?? $news->title ?? '') }}" required maxlength="255"></label>
<label class="md:col-span-2">Ringkasan isu<textarea class="field" name="summary" rows="4" required>{{ old('summary', $issue->summary ?? $news->summary ?? '') }}</textarea></label>
<label>Prioritas<select class="field" name="priority">@foreach(\App\Models\Issue::PRIORITIES as $key => $label)<option value="{{ $key }}" @selected(old('priority', $issue->priority ?? 'normal') === $key)>{{ $label }}</option>@endforeach</select></label>
<label>Penanggung jawab<select class="field" name="assigned_to"><option value="">Belum ditugaskan</option>@foreach($assignees as $user)<option value="{{ $user->id }}" @selected((string)old('assigned_to', $issue->assigned_to ?? '') === (string)$user->id)>{{ $user->name }} · {{ \App\Models\User::ROLES[$user->role] }}</option>@endforeach</select></label>
<label>Tenggat (WIB)<input class="field" type="datetime-local" name="due_at" value="{{ old('due_at', isset($issue) ? $issue->due_at?->format('Y-m-d\TH:i') : '') }}"></label>
@foreach(\App\Models\Taxonomy::KINDS as $kind => $label)
<label>{{ $label }}<select class="field" name="{{ $kind }}_id"><option value="">Belum ditentukan</option>@foreach($taxonomies->get($kind, collect()) as $option)<option value="{{ $option->id }}" @selected((string)old($kind.'_id', $issue->{$kind.'_id'} ?? $news->{$kind.'_id'} ?? '') === (string)$option->id)>{{ $option->name }}</option>@endforeach</select></label>
@endforeach
