<option value="">{{ $placeholder }}</option>
@foreach(($people ?? collect())->groupBy(fn ($person) => $person->role?->name ?? 'Tanpa role') as $roleName => $group)
    <optgroup label="{{ $roleName }}">
        @foreach($group as $person)
            <option value="{{ $person->id }}" @selected((string) ($selected ?? '') === (string) $person->id)>{{ $person->name }}</option>
        @endforeach
    </optgroup>
@endforeach
