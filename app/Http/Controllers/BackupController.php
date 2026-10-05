<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    private const BACKUP_FILE_PREFIX = 'senador-coco-backup-';

    public function index(): View
    {
        $onlineBackups = [];
        $onlineError = null;

        if ($this->onlineBackupConfigured()) {
            try {
                $onlineBackups = $this->listOnlineBackups();
            } catch (RuntimeException $exception) {
                $onlineError = $exception->getMessage();
            }
        }

        return view('backup.index', [
            'onlineBackupConfigured' => $this->onlineBackupConfigured(),
            'onlineBackupPath' => $this->onlineBackupPath(),
            'onlineBackups' => $onlineBackups,
            'onlineError' => $onlineError,
            'databaseConnection' => config('database.default'),
        ]);
    }

    public function create(): BinaryFileResponse|RedirectResponse
    {
        try {
            [$path, $fileName] = $this->createBackupFile();
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->log('CREATE', 'Created an offline database backup.');

        return response()
            ->download($path, $fileName)
            ->deleteFileAfterSend();
    }

    public function createGoogleDrive(): RedirectResponse
    {
        if (! $this->onlineBackupConfigured()) {
            return back()->with('error', 'Online backup folder is not configured yet.');
        }

        try {
            [$path, $fileName] = $this->createBackupFile();
            $destination = $this->onlineBackupPath().DIRECTORY_SEPARATOR.$fileName;
            File::copy($path, $destination);
            File::delete($path);
            $this->deleteOlderOnlineBackups($fileName);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->log('CREATE', "Saved latest online backup {$fileName} to the Google Drive synced folder.");

        return back()->with(
            'success',
            "Latest online backup saved: {$fileName}. Google Drive Desktop will sync it online."
        );
    }

    public function restore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'backup' => [
                'required',
                'file',
                'max:102400',
            ],
        ]);

        $file = $data['backup'];

        try {
            $this->restoreBackupFile(
                $file->getRealPath(),
                $file->getClientOriginalName()
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->log('RESTORE', 'Restored the database from an offline backup.');

        return redirect()
            ->route('login')
            ->with('success', 'Database restored. Please sign in again.');
    }

    public function restoreGoogleDrive(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file_id' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        if (! $this->onlineBackupConfigured()) {
            return back()->with('error', 'Online backup folder is not configured yet.');
        }

        try {
            $fileName = basename($data['file_id']);
            $path = $this->onlineBackupPath().DIRECTORY_SEPARATOR.$fileName;

            if (! File::exists($path)) {
                throw new RuntimeException('Selected online backup file was not found.');
            }

            $this->restoreBackupFile($path, $fileName);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->log('RESTORE', "Restored the database from online backup {$fileName}.");

        return redirect()
            ->route('login')
            ->with('success', 'Online backup restored. Please sign in again.');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function createBackupFile(): array
    {
        $connection = config('database.default');
        $directory = storage_path('app/private/backups');

        File::ensureDirectoryExists($directory);

        $timestamp = now()->format('Ymd-His');

        if ($connection === 'sqlite') {
            $databasePath = database_path('database.sqlite');

            if (! File::exists($databasePath)) {
                throw new RuntimeException('SQLite database file was not found.');
            }

            $fileName = self::BACKUP_FILE_PREFIX."{$timestamp}.sqlite";
            $backupPath = $directory.DIRECTORY_SEPARATOR.$fileName;

            File::copy($databasePath, $backupPath);

            return [$backupPath, $fileName];
        }

        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Backup is not supported for the {$connection} database connection yet.");
        }

        $fileName = self::BACKUP_FILE_PREFIX."{$timestamp}.sql";
        $backupPath = $directory.DIRECTORY_SEPARATOR.$fileName;

        $this->createMySqlBackupFile($backupPath);

        return [$backupPath, $fileName];
    }

    private function createMySqlBackupFile(string $backupPath): void
    {
        $pdo = DB::connection()->getPdo();
        $databaseName = DB::connection()->getDatabaseName();
        $tables = $this->mysqlTables();
        $contents = [
            '-- Senador Coco database backup',
            '-- Created: '.now()->format('Y-m-d H:i:s'),
            '-- Database: '.$databaseName,
            '',
            'SET FOREIGN_KEY_CHECKS=0;',
            '',
        ];

        foreach ($tables as $table) {
            $quotedTable = $this->quoteIdentifier($table);
            $createTableResult = DB::select("SHOW CREATE TABLE {$quotedTable}");
            $createTableRow = (array) $createTableResult[0];
            $createTable = $createTableRow['Create Table'] ?? array_values($createTableRow)[1];

            $contents[] = "DROP TABLE IF EXISTS {$quotedTable};";
            $contents[] = $createTable.';';
            $contents[] = '';

            $rows = DB::table($table)->get();

            if ($rows->isEmpty()) {
                continue;
            }

            foreach ($rows->chunk(100) as $chunk) {
                $firstRow = (array) $chunk->first();
                $columns = array_map(
                    fn (string $column): string => $this->quoteIdentifier($column),
                    array_keys($firstRow)
                );

                $values = $chunk
                    ->map(function (object $row) use ($pdo): string {
                        return '('.collect((array) $row)
                            ->map(fn ($value): string => $this->quoteValue($pdo, $value))
                            ->implode(', ').')';
                    })
                    ->implode(",\n");

                $contents[] = "INSERT INTO {$quotedTable} (".implode(', ', $columns).') VALUES';
                $contents[] = $values.';';
                $contents[] = '';
            }
        }

        $contents[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $contents[] = '';

        File::put($backupPath, implode("\n", $contents));
    }

    /**
     * @return array<int, string>
     */
    private function mysqlTables(): array
    {
        return collect(DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"'))
            ->map(fn (object $row): string => (string) array_values((array) $row)[0])
            ->values()
            ->all();
    }

    private function quoteIdentifier(string $value): string
    {
        return '`'.str_replace('`', '``', $value).'`';
    }

    private function quoteValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $pdo->quote((string) $value);
    }

    private function restoreBackupFile(string $path, string $fileName): void
    {
        $connection = config('database.default');
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($connection === 'sqlite') {
            if ($extension !== 'sqlite') {
                throw new RuntimeException('Select a valid .sqlite backup file.');
            }

            File::copy($path, database_path('database.sqlite'));

            return;
        }

        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Restore is not supported for the {$connection} database connection yet.");
        }

        if ($extension !== 'sql') {
            throw new RuntimeException('Select a valid .sql backup file.');
        }

        $database = $this->databaseConfig();

        $command = [
            config('services.database_tools.mysql_path'),
            "--host={$database['host']}",
            "--port={$database['port']}",
            "--user={$database['username']}",
        ];

        if ($database['password'] !== '') {
            $command[] = "--password={$database['password']}";
        }

        $command[] = $database['database'];

        $process = new Process($command);
        $process->setInput(File::get($path));
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'Unable to restore MySQL backup. Check MYSQL_PATH in .env. '.$process->getErrorOutput()
            );
        }
    }

    /**
     * @return array{host: string, port: string, database: string, username: string, password: string}
     */
    private function databaseConfig(): array
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}");

        return [
            'host' => (string) ($database['host'] ?? '127.0.0.1'),
            'port' => (string) ($database['port'] ?? '3306'),
            'database' => (string) ($database['database'] ?? ''),
            'username' => (string) ($database['username'] ?? ''),
            'password' => (string) ($database['password'] ?? ''),
        ];
    }

    private function onlineBackupConfigured(): bool
    {
        return filled(config('services.online_backup.path'))
            && File::isDirectory($this->onlineBackupPath());
    }

    private function onlineBackupPath(): string
    {
        return rtrim((string) config('services.online_backup.path'), '\\/');
    }

    /**
     * @return array<int, array{id: string, name: string, size: int, createdTime: string}>
     */
    private function listOnlineBackups(): array
    {
        $path = $this->onlineBackupPath();

        if (! File::isDirectory($path)) {
            throw new RuntimeException('Online backup folder was not found. Check ONLINE_BACKUP_PATH in .env.');
        }

        return collect(File::files($path))
            ->filter(fn (SplFileInfo $file): bool => $this->isBackupFile($file))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->take(1)
            ->map(fn ($file) => [
                'id' => $file->getFilename(),
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'createdTime' => date('c', $file->getMTime()),
            ])
            ->values()
            ->all();
    }

    private function deleteOlderOnlineBackups(string $latestFileName): void
    {
        collect(File::files($this->onlineBackupPath()))
            ->filter(fn (SplFileInfo $file): bool => $this->isBackupFile($file))
            ->reject(fn (SplFileInfo $file): bool => $file->getFilename() === $latestFileName)
            ->each(fn (SplFileInfo $file): bool => File::delete($file->getRealPath()));
    }

    private function isBackupFile(SplFileInfo $file): bool
    {
        return str_starts_with($file->getFilename(), self::BACKUP_FILE_PREFIX)
            && in_array(strtolower($file->getExtension()), ['sql', 'sqlite'], true);
    }

    private function log(string $action, string $description): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'module' => 'BACKUP',
            'action' => $action,
            'description' => $description,
        ]);
    }
}
