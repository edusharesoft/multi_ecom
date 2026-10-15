<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Backups without mysqldump (works on Laragon/Windows and shared hosting):
 *   - database: pure-PHP SQL dump (gzip), streamed table by table
 *   - uploads:  zip of storage/app/public
 *   - branch export: zip of CSV files with one store's own data
 * Files live in storage/app/backups (never web-accessible).
 */
class BackupService
{
    public const DIR = 'backups';

    public function disk()
    {
        return Storage::disk('local');
    }

    public function list(): array
    {
        $files = [];
        foreach ($this->disk()->files(self::DIR) as $f) {
            if (preg_match('/\.(sql\.gz|zip)$/', $f)) {
                $files[] = ['name' => basename($f), 'size' => $this->disk()->size($f), 'time' => $this->disk()->lastModified($f),
                    'type' => str_starts_with(basename($f), 'db-') ? 'database' : (str_starts_with(basename($f), 'uploads-') ? 'uploads' : 'export')];
            }
        }
        usort($files, fn ($a, $b) => $b['time'] <=> $a['time']);

        return $files;
    }

    public function path(string $name): string
    {
        if (! preg_match('/^[A-Za-z0-9._-]+\.(sql\.gz|zip)$/', $name) || ! $this->disk()->exists(self::DIR.'/'.$name)) {
            throw new RuntimeException('Backup file not found.');
        }

        return $this->disk()->path(self::DIR.'/'.$name);
    }

    protected function pdo(): PDO
    {
        $c = config('database.connections.'.config('database.default'));
        if (($c['driver'] ?? '') !== 'mysql' && ($c['driver'] ?? '') !== 'mariadb') {
            throw new RuntimeException('Database backup supports MySQL/MariaDB only.');
        }
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4";

        return new PDO($dsn, $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);
    }

    public function database(): string
    {
        @set_time_limit(0);
        $this->disk()->makeDirectory(self::DIR);
        $name = 'db-'.now()->format('Ymd-His').'.sql.gz';
        $full = $this->disk()->path(self::DIR.'/'.$name);
        $gz = gzopen($full, 'wb6');
        $pdo = $this->pdo();
        $meta = DB::connection()->getPdo();   // buffered connection for metadata

        gzwrite($gz, "-- MultiBranch backup ".now()->toDateTimeString()."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        foreach ($meta->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM) as [$table]) {
            $create = $meta->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM)[1];
            gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n{$create};\n");
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $batch = [];
            $bytes = 0;
            $cols = null;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cols ??= '`'.implode('`,`', array_keys($row)).'`';
                $batch[] = $line = '('.implode(',', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)).')';
                $bytes += strlen($line);
                if (count($batch) >= 200 || $bytes > 4 * 1024 * 1024) {   // keep INSERT lines well under gzgets / max_allowed_packet limits
                    $bytes = 0;
                    gzwrite($gz, "INSERT INTO `{$table}` ({$cols}) VALUES ".implode(',', $batch).";\n");
                    $batch = [];
                }
            }
            if ($batch) {
                gzwrite($gz, "INSERT INTO `{$table}` ({$cols}) VALUES ".implode(',', $batch).";\n");
            }
            $stmt->closeCursor();
            gzwrite($gz, "\n");
        }
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);

        return $name;
    }

    public function uploads(): string
    {
        @set_time_limit(0);
        $this->needZip();
        $this->disk()->makeDirectory(self::DIR);
        $name = 'uploads-'.now()->format('Ymd-His').'.zip';
        $zip = new ZipArchive;
        $zip->open($this->disk()->path(self::DIR.'/'.$name), ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $root = Storage::disk('public')->path('');
        $count = 0;
        foreach (Storage::disk('public')->allFiles() as $f) {
            if (str_starts_with($f, '.')) {
                continue;
            }
            $zip->addFile($root.$f, $f);
            $count++;
        }
        if ($count === 0) {
            $zip->addFromString('README.txt', 'No uploaded files yet.');
        }
        $zip->close();

        return $name;
    }

    /** One store's own data as CSV files in a zip (for the merchant). */
    public function branchExport(Branch $b): string
    {
        @set_time_limit(0);
        $this->needZip();
        $this->disk()->makeDirectory(self::DIR.'/exports');
        $name = 'export-'.$b->slug.'-'.now()->format('Ymd-His').'.zip';
        $path = $this->disk()->path(self::DIR.'/exports/'.$name);
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $orderIds = DB::table('orders')->where('branch_id', $b->id)->pluck('id');
        $sets = [
            'products' => DB::table('products')->where('branch_id', $b->id),
            'product_variants' => DB::table('product_variants')->whereIn('product_id', DB::table('products')->where('branch_id', $b->id)->select('id')),
            'customers' => DB::table('customers')->where('branch_id', $b->id)->select(['id', 'name', 'phone', 'email', 'group', 'wallet_balance', 'loyalty_points', 'created_at']),
            'orders' => DB::table('orders')->where('branch_id', $b->id),
            'order_items' => DB::table('order_items')->whereIn('order_id', $orderIds),
        ];
        foreach (['suppliers', 'purchases', 'account_transactions', 'vouchers', 'employees', 'attendances', 'posts', 'pages'] as $t) {
            if (Schema::hasTable($t)) {
                $sets[$t] = DB::table($t)->where('branch_id', $b->id);
            }
        }
        foreach ($sets as $file => $query) {
            $fh = fopen('php://temp', 'w+');
            fwrite($fh, "\xEF\xBB\xBF");
            $header = false;
            foreach ($query->orderBy('id')->cursor() as $row) {
                $row = (array) $row;
                unset($row['password'], $row['remember_token']);
                if (! $header) {
                    fputcsv($fh, array_keys($row));
                    $header = true;
                }
                fputcsv($fh, array_map(fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v), $row));
            }
            rewind($fh);
            $zip->addFromString($file.'.csv', stream_get_contents($fh));
            fclose($fh);
        }
        $zip->close();

        return 'exports/'.$name;
    }

    /** Restore a .sql.gz created by database(). Destroys current data. */
    public function restore(string $name): int
    {
        @set_time_limit(0);
        $full = $this->path($name);
        if (! str_ends_with($name, '.sql.gz')) {
            throw new RuntimeException('Only database (.sql.gz) backups can be restored.');
        }
        $pdo = DB::connection()->getPdo();
        $gz = gzopen($full, 'rb');
        $buf = '';
        $n = 0;
        while (! gzeof($gz)) {
            $line = gzgets($gz, 16 * 1024 * 1024);
            if ($line === false) {
                break;
            }
            if ($buf === '' && (str_starts_with($line, '--') || trim($line) === '')) {
                continue;
            }
            $buf .= $line;
            // statements end with ";\n" (values are escaped, so data never holds a raw newline); a chunk cut by the
            // gzgets length limit does not end with "\n" and is not executed half-way
            if (str_ends_with($line, ";\n") || (gzeof($gz) && str_ends_with(rtrim($line), ';'))) {
                $pdo->exec($buf);
                $buf = '';
                $n++;
            }
        }
        gzclose($gz);

        return $n;
    }

    /** Keep the newest $keep files of each type. */
    public function prune(int $keep = 7): void
    {
        foreach (['database', 'uploads'] as $type) {
            $files = array_values(array_filter($this->list(), fn ($f) => $f['type'] === $type));
            foreach (array_slice($files, $keep) as $f) {
                $this->disk()->delete(self::DIR.'/'.$f['name']);
            }
        }
    }

    protected function needZip(): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP zip extension চালু নেই — Laragon → PHP → Extensions → zip চালু করুন।');
        }
    }
}
