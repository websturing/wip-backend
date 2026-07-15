<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RollbackMigrateLayingPlanning extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rollback-migrate-laying-planning';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghapus data (truncate) yang diinsert dari command app:migrate-laying-planning';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('Perhatian: Command ini akan MENGHAPUS (TRUNCATE) seluruh isi dari tabel-tabel hasil migrasi:');

        $tables = [
            'laying_planning_detail_sizes',
            'laying_planning_details',
            'laying_planning_sizes',
            'laying_plannings',
            'laying_planning_detail_types',
            'laying_planning_types',
        ];

        foreach ($tables as $table) {
            $this->line("- $table");
        }

        if (! $this->confirm('Apakah Anda yakin ingin mengosongkan tabel-tabel ini secara permanen?')) {
            $this->info('Proses dibatalkan.');

            return;
        }

        $this->info('Memulai penghapusan data...');

        // Nonaktifkan constraint agar bisa melakukan TRUNCATE walau ada relasi foreign key
        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            DB::table($table)->truncate();
            $this->line("Tabel $table berhasil dikosongkan.");
        }

        // Tanyakan secara terpisah untuk tabel users, karena menghapus tabel users bisa mengakibatkan gagal login
        if ($this->confirm('Apakah Anda juga ingin mengosongkan tabel "users"? (Pilih "no" jika ada user yang aktif/login)')) {
            DB::table('users')->truncate();
            $this->line('Tabel users berhasil dikosongkan.');
        } else {
            $this->line('Tabel users dilewati.');
        }

        // Aktifkan kembali constraint
        Schema::enableForeignKeyConstraints();

        $this->info('Penghapusan data migrasi telah selesai!');
    }
}
