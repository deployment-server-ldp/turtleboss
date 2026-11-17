<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index()
    {
        $files = collect(Storage::disk('local')->files('backups'))->map(fn ($f) => ['name' => basename($f), 'size' => Storage::disk('local')->size($f), 'at' => \Carbon\Carbon::createFromTimestamp(Storage::disk('local')->lastModified($f))])->sortByDesc('at')->values();
        return view('admin.backup', ['files' => $files, 'driver' => config('database.default'), 'maintenance' => app()->isDownForMaintenance()]);
    }

    public function create()
    {
        try { $name = app(\App\Services\BackupService::class)->run(); }
        catch (\Throwable $e) { return back()->withErrors(['backup' => 'Backup failed: ' . $e->getMessage()]); }
        return back()->with('status', "Backup created: $name");
    }

    public function download(string $file)
    {
        abort_unless(preg_match('/^[\w.\-]+\.(sql|sqlite|gz)$/', $file) && Storage::disk('local')->exists("backups/$file"), 404);
        return Storage::disk('local')->download("backups/$file");
    }

    public function delete(string $file)
    {
        abort_unless(preg_match('/^[\w.\-]+\.(sql|sqlite|gz)$/', $file), 404);
        Storage::disk('local')->delete("backups/$file");
        return back()->with('status', 'Backup deleted.');
    }

    public function clearCache()
    {
        Artisan::call('cache:clear'); Artisan::call('view:clear'); \Illuminate\Support\Facades\Cache::forget('sitemap.xml');
        return back()->with('status', 'Caches cleared.');
    }

    public function maintenance(Request $r)
    {
        if ($r->input('mode') === 'down') { Artisan::call('down', ['--secret' => 'tb-admin-' . substr(md5(config('app.key')), 0, 8), '--retry' => 60]); return back()->with('status', 'Maintenance mode ON. Bypass: /tb-admin-' . substr(md5(config('app.key')), 0, 8)); }
        Artisan::call('up');
        return back()->with('status', 'Site is live again.');
    }

    public function inbox()
    {
        return view('admin.inbox', ['messages' => ContactMessage::latest()->paginate(20), 'subscribers' => NewsletterSubscriber::latest()->paginate(20, ['*'], 'sp'), 'subscriberCount' => NewsletterSubscriber::count()]);
    }
    public function readMessage(ContactMessage $message) { $message->update(['is_read' => true]); return back(); }
    public function deleteMessage(ContactMessage $message) { $message->delete(); return back(); }
    public function exportSubscribers()
    {
        return response()->streamDownload(function () { $o = fopen('php://output', 'w'); fputcsv($o, ['Email', 'Subscribed']); NewsletterSubscriber::chunk(500, fn ($c) => $c->each(fn ($s) => fputcsv($o, [$s->email, $s->created_at->toDateString()]))); fclose($o); }, 'subscribers.csv', ['Content-Type' => 'text/csv']);
    }
}
