<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;

/** Database backup: mysqldump for MySQL (when available), file copy for SQLite, otherwise a portable JSON-SQL export via PDO. */
class BackupService
{
    public function run(): string
    {
        Storage::disk('local')->makeDirectory('backups');
        $conn = config('database.default');
        $cfg = config("database.connections.$conn");
        $stamp = now()->format('Ymd-His');
        $dir = Storage::disk('local')->path('backups');

        if ($cfg['driver'] === 'sqlite') {
            if ($cfg['database'] === ':memory:') throw new \RuntimeException('In-memory database cannot be backed up.');
            $name = "db-$stamp.sqlite";
            copy($cfg['database'], "$dir/$name");
        } else {
            $name = "db-$stamp.sql";
            $bin = trim((string) @shell_exec('command -v mysqldump'));
            if ($bin && $cfg['driver'] === 'mysql') {
                $cmd = sprintf('%s --single-transaction --quick --host=%s --port=%s --user=%s %s > %s 2>&1', escapeshellarg($bin), escapeshellarg($cfg['host']), escapeshellarg((string) $cfg['port']), escapeshellarg($cfg['username']), escapeshellarg($cfg['database']), escapeshellarg("$dir/$name"));
                putenv('MYSQL_PWD=' . $cfg['password']);
                exec($cmd, $o, $code);
                putenv('MYSQL_PWD');
                if ($code !== 0) throw new \RuntimeException('mysqldump failed');
            } else {
                $this->phpDump("$dir/$name");
            }
        }
        // keep the 14 most recent
        $files = collect(Storage::disk('local')->files('backups'))->sortByDesc(fn ($f) => Storage::disk('local')->lastModified($f))->values();
        $files->slice(14)->each(fn ($f) => Storage::disk('local')->delete($f));
        return $name;
    }

    private function phpDump(string $path): void
    {
        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
        $fh = fopen($path, 'w');
        fwrite($fh, "-- Turtle Boss PHP backup " . now() . "\nSET FOREIGN_KEY_CHECKS=0;\n");
        foreach (\Illuminate\Support\Facades\DB::select('SHOW TABLES') as $row) {
            $table = array_values((array) $row)[0];
            $create = (array) \Illuminate\Support\Facades\DB::selectOne("SHOW CREATE TABLE `$table`");
            fwrite($fh, "DROP TABLE IF EXISTS `$table`;\n" . array_values($create)[1] . ";\n");
            foreach (\Illuminate\Support\Facades\DB::table($table)->cursor() as $r) {
                $vals = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), (array) $r);
                fwrite($fh, "INSERT INTO `$table` VALUES (" . implode(',', $vals) . ");\n");
            }
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }
}
