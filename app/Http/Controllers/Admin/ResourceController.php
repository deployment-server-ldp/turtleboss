<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use App\Support\Html;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Generic admin CRUD. Subclasses declare the model, route prefix, fields and list columns.
 * Field spec: name,label,type(text|textarea|richtext|number|select|checkbox|image|datetime|password|multiselect|email),
 *             rules, options (for select/multiselect), help, virtual(bool: not a DB column), default.
 */
abstract class ResourceController extends Controller
{
    protected string $model;
    protected string $route;      // e.g. admin.categories
    protected string $title;      // plural title
    protected string $singular;
    protected bool $seo = false;
    protected array $with = [];
    protected string $orderBy = 'id';
    protected string $orderDir = 'desc';
    protected array $searchable = ['name'];
    protected string $images = 'uploads';
    protected bool $canCreate = true;

    abstract protected function fields(?Model $m): array;
    abstract protected function columns(): array; // [label => callable(model) | attribute]

    protected function afterSave(Model $m, array $data, Request $r): void {}
    protected function beforeSave(array &$data, Request $r, ?Model $m): void {}
    protected function canDelete(Model $m): ?string { return null; } // return error message to block

    public function index(Request $r)
    {
        $q = ($this->model)::query()->with($this->with);
        if ($term = trim((string) $r->q)) {
            $q->where(function ($w) use ($term) { foreach ($this->searchable as $c) $w->orWhere($c, 'like', "%$term%"); });
        }
        $this->filter($q, $r);
        $rows = $q->orderBy($this->orderBy, $this->orderDir)->paginate(20)->withQueryString();
        return view('admin.resource.index', ['rows' => $rows, 'columns' => $this->columns(), 'title' => $this->title, 'route' => $this->route, 'canCreate' => $this->canCreate, 'extra' => $this->indexExtra()]);
    }
    protected function filter($q, Request $r): void {}
    protected function indexExtra(): array { return []; }

    public function create() { return $this->form(new $this->model); }
    public function edit($id) { return $this->form(($this->model)::findOrFail($id)); }

    protected function form(Model $m)
    {
        if ($this->seo && $m->exists) $m->loadMissing('seo');
        return view('admin.resource.form', ['m' => $m, 'fields' => $this->fields($m->exists ? $m : null), 'route' => $this->route, 'title' => $this->title, 'singular' => $this->singular, 'seo' => $this->seo, 'extraView' => $this->extraView($m)]);
    }
    protected function extraView(Model $m): ?string { return null; }

    public function store(Request $r) { return $this->save($r, new $this->model); }
    public function update(Request $r, $id) { return $this->save($r, ($this->model)::findOrFail($id)); }

    protected function save(Request $r, Model $m)
    {
        $fields = collect($this->fields($m->exists ? $m : null));
        $rules = $fields->filter(fn ($f) => !empty($f['rules']))->mapWithKeys(fn ($f) => [$f['name'] => $f['rules']])->all();
        $rules += [
            'seo.title' => 'nullable|max:160', 'seo.description' => 'nullable|max:320', 'seo.focus_keyword' => 'nullable|max:100',
            'seo.canonical_url' => 'nullable|url|max:255', 'seo.og_title' => 'nullable|max:160', 'seo.og_description' => 'nullable|max:320', 'seo.og_image_file' => 'nullable|image|max:4096',
        ];
        $v = $r->validate($rules);
        $data = [];
        $virtual = [];
        foreach ($fields as $f) {
            $n = $f['name']; $t = $f['type'] ?? 'text';
            if (!empty($f['virtual'])) { $virtual[$n] = $r->input($n); continue; }
            if ($t === 'image') {
                if ($r->hasFile($n)) {
                    $new = app(ImageService::class)->store($r->file($n), $this->images, null, $r->input($n . '_alt'));
                    app(ImageService::class)->delete($m->{$n});
                    $data[$n] = $new;
                } elseif ($r->boolean($n . '_remove')) { app(ImageService::class)->delete($m->{$n}); $data[$n] = null; }
                continue;
            }
            if ($t === 'password') { if ($r->filled($n)) $data[$n] = $r->input($n); continue; }
            if ($t === 'checkbox') { $data[$n] = $r->boolean($n); continue; }
            $val = $r->input($n);
            if ($t === 'richtext') $val = Html::clean($val);
            if (in_array($t, ['number', 'datetime', 'select']) && $val === '') $val = null;
            $data[$n] = $val;
        }
        if (array_key_exists('slug', $data) || $fields->contains('name', 'slug')) {
            $base = $data['slug'] ?: Str::slug($data[$fields->contains('name', 'name') ? 'name' : 'title'] ?? 'item');
            $data['slug'] = $this->uniqueSlug($m, Str::slug($base) ?: 'item');
        }
        $this->beforeSave($data, $r, $m->exists ? $m : null);
        $m->fill($data)->save();
        if ($this->seo) {
            $seo = $r->input('seo', []);
            if ($r->hasFile('seo.og_image_file')) $seo['og_image'] = app(ImageService::class)->store($r->file('seo.og_image_file'), 'seo');
            else $seo['og_image'] = $m->seo?->og_image;
            $m->saveSeo($seo);
        }
        $this->afterSave($m, $virtual, $r);
        \Illuminate\Support\Facades\Cache::forget('sitemap.xml');
        return redirect()->route($this->route . '.edit', $m->getKey())->with('status', $this->singular . ' saved.');
    }

    protected function uniqueSlug(Model $m, string $slug): string
    {
        $try = $slug; $i = 2;
        while (($this->model)::where('slug', $try)->when($m->exists, fn ($q) => $q->where($m->getKeyName(), '!=', $m->getKey()))->exists()) $try = $slug . '-' . $i++;
        return $try;
    }

    public function destroy($id)
    {
        $m = ($this->model)::findOrFail($id);
        if ($msg = $this->canDelete($m)) return back()->withErrors(['delete' => $msg]);
        foreach ($this->fields($m) as $f) if (($f['type'] ?? '') === 'image') app(ImageService::class)->delete($m->{$f['name']});
        $m->delete();
        \Illuminate\Support\Facades\Cache::forget('sitemap.xml');
        return redirect()->route($this->route . '.index')->with('status', $this->singular . ' deleted.');
    }
}
