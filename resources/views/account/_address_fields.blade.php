<div class="grid gap-3 sm:grid-cols-2">
    <input name="label" class="field" placeholder="Label (Home, Office)" value="{{ $a?->label }}">
    <input name="name" class="field" placeholder="Full name *" value="{{ $a?->name }}" required>
    <input name="phone" class="field" placeholder="Phone *" value="{{ $a?->phone }}" required>
    <input name="address" class="field" placeholder="Address *" value="{{ $a?->address }}" required>
    <select name="city" class="field" required><option value="">City *</option>@foreach($cities as $c)<option @selected($a?->city === $c)>{{ $c }}</option>@endforeach</select>
    <select name="province" class="field"><option value="">Province</option>@foreach($provinces as $p)<option @selected($a?->province === $p)>{{ $p }}</option>@endforeach</select>
    <input name="postal_code" class="field" placeholder="Postal code" value="{{ $a?->postal_code }}">
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked($a?->is_default) class="rounded border-line"> Make default</label>
</div>
