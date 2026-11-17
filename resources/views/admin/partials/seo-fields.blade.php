@php $s = $model->exists ? $model->seo : null; $v = fn ($k) => old("seo.$k", $s?->$k); @endphp
<details class="card p-5 sm:p-6" @if($s || $errors->has('seo.*')) open @endif>
    <summary class="cursor-pointer text-base font-semibold">SEO settings</summary>
    <div class="mt-4 grid gap-4 sm:grid-cols-2" x-data="{ t: @js($v('title') ?? ''), d: @js($v('description') ?? '') }">
        <div class="sm:col-span-2"><label class="label">SEO title <span class="font-normal text-muted" x-text="t.length + '/60'"></span></label><input name="seo[title]" x-model="t" class="field" maxlength="160"></div>
        <div class="sm:col-span-2"><label class="label">Meta description <span class="font-normal text-muted" x-text="d.length + '/160'"></span></label><textarea name="seo[description]" x-model="d" rows="2" class="field" maxlength="320"></textarea></div>
        <div><label class="label">Focus keyword</label><input name="seo[focus_keyword]" value="{{ $v('focus_keyword') }}" class="field"></div>
        <div><label class="label">Canonical URL (optional)</label><input name="seo[canonical_url]" value="{{ $v('canonical_url') }}" class="field" placeholder="https://…"></div>
        <div><label class="label">Open Graph title</label><input name="seo[og_title]" value="{{ $v('og_title') }}" class="field"></div>
        <div><label class="label">Open Graph image</label><input type="file" name="seo[og_image_file]" accept="image/*" class="block w-full text-sm"> @if($s?->og_image)<img src="{{ img($s->og_image) }}" class="mt-2 h-14 rounded" alt="">@endif</div>
        <div class="sm:col-span-2"><label class="label">Open Graph description</label><textarea name="seo[og_description]" rows="2" class="field">{{ $v('og_description') }}</textarea></div>
        <label class="flex items-center gap-2 sm:col-span-2"><input type="checkbox" name="seo[noindex]" value="1" @checked($v('noindex')) class="rounded border-line"> <span><b>noindex</b> – hide from search engines (also removed from sitemap)</span></label>
    </div>
</details>
