<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('app:migrate-laying-planning')]
#[Description('Command description')]
class MigrateLayingPlanning extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mengambil sampel data untuk dipetakan...');

        // Ambil beberapa baris data sampel (misal 5)
        $oldPlannings = DB::connection('legacy_db')
            ->table('laying_plannings')
            ->whereYear('plan_date', 2026) // Menyaring kolom plan_date yang tahunnya 2026
            ->limit(5)
            ->get();

        $previewData = [];

        foreach ($oldPlannings as $old) {
            // Ambil relasi detailnya sekalian

            $buyer = DB::connection('legacy_db')
                ->table('buyers')
                ->where('id', $old->buyer_id)
                ->first();

            $color = DB::connection('legacy_db')
                ->table('colors')
                ->where('id', $old->color_id)
                ->first();

            $gls = DB::connection('legacy_db')
                ->table('gls')
                ->where('id', $old->gl_id)
                ->first();

            $style = DB::connection('legacy_db')
                ->table('styles')
                ->where('id', $old->style_id)
                ->first();

            $details = DB::connection('legacy_db')
                ->table('laying_planning_details')
                ->where('laying_planning_id', $old->id)
                ->get();

            // Ambil relasi sizenya sekalian
            $sizes = DB::connection('legacy_db')
                ->table('laying_planning_sizes')
                ->where('laying_planning_id', $old->id)
                ->get();

            // ==========================================
            // AMAN KAN DATA CUSTOMER (BUYER)
            // ==========================================
            // 1. Cek apakah customer dengan nama ini sudah ada di DB baru
            $existingCustomer = DB::table('customers')->where('name', $buyer->name)->first();

            if ($existingCustomer) {
                // Jika sudah ada, gunakan UUID yang sudah terdaftar
                $customerUid = $existingCustomer->id;
            } else {
                // Jika belum ada, buat UUID baru dan insert ke DB baru
                $customerUid = (string) Str::uuid();
                DB::table('customers')->insert([
                    'id' => $customerUid,
                    'name' => $buyer->name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ==========================================
            // AMAN KAN DATA GL GROUPS
            // ==========================================
            // 2. Cek apakah GL Number ini sudah ada di DB baru

            $glParts = explode('-', $gls->gl_number);

            // Ambil bagian depan untuk GL Number asli (66781)
            $cleanGlNumber = $glParts[0];

            // Ambil bagian belakang untuk LOT (00)
            // Gunakan null coalescing (??) untuk jaga-jaga jika ada data lama yang tidak punya strip
            $lotNumber = $glParts[1] ?? '00';

            $existingGl = DB::table('gl_groups')->where('gl_number', $cleanGlNumber)->first();
            // Pecah string berdasarkan tanda strip (-)

            if ($existingGl) {
                $glUid = $existingGl->id;

                // Opsional: Update customer_id jika diperlukan
                DB::table('gl_groups')->where('id', $glUid)->update([
                    'customer_id' => $customerUid,
                    'updated_at' => now(),
                ]);
            } else {
                $glUid = (string) Str::uuid();
                DB::table('gl_groups')->insert([
                    'id' => $glUid,
                    'gl_number' => $cleanGlNumber,
                    'customer_id' => $customerUid, // Menggunakan UUID customer yang valid
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ==========================================
            // AMAN KAN DATA LOT
            // ==========================================
            // 3. Cek apakah Lot ini sudah ada di DB baru
            $existingLot = DB::table('lots')
                ->where('gl_id', $glUid)
                ->where('lot_number', $lotNumber)
                ->first();
            if ($existingLot) {
                $lotUid = $existingLot->id;
            } else {
                $lotUid = (string) Str::uuid();
                DB::table('lots')->insert([
                    'id' => $lotUid,
                    'gl_id' => $glUid,
                    'lot_number' => $lotNumber,
                    'gmt_qty' => $old->order_qty,
                    'lot_code' => $cleanGlNumber.'-'.$lotNumber,
                    'style_no' => $style->style,
                    'sam' => null,
                    'delivery_date' => $old->delivery_date,
                    'order_date' => null,
                    'is_cancelled' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ==========================================
            // AMAN KAN DATA COLOR
            // ==========================================
            // 4. Cek apakah Color ini sudah ada di DB baru
            $existingColor = DB::table('colors')
                ->where('gl_id', $glUid)
                ->where('standard_name', $color->color)
                ->first();
            if ($existingColor) {
                $colorUid = $existingColor->id;
            } else {
                $colorUid = (string) Str::uuid();
                DB::table('colors')->insert([
                    'id' => $colorUid,
                    'gl_id' => $glUid,
                    'standard_name' => $color->color,
                    'code' => $color->color_code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ==========================================
            // AMAN KAN DATA FABRIC
            // ==========================================
            // 5. Cek apakah Fabric ini sudah ada di DB baru
            $existingFabric = DB::table('fabrics')
                ->where('gl_id', $glUid)
                ->where('standard_content', $old->fabric_cons_desc)
                ->first();
            if ($existingFabric) {
                $fabricUid = $existingFabric->id;
            } else {
                $fabricUid = (string) Str::uuid();
                DB::table('fabrics')->insert([
                    'id' => $fabricUid,
                    'gl_id' => $glUid,
                    'standard_content' => $old->fabric_cons_desc ?? '-',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ==========================================
            // AMAN KAN DATA LAYING PLANNING
            // ==========================================
            // 6. Cek apakah laying Planning ini sudah ada di DB baru

            // cek laying Planning Type
            $existingLayingPlanningType = DB::table('laying_planning_types')->where('type', $old->laying_planning_type_id)->first();
            if ($existingLayingPlanningType) {
                $layingPlanningTypeId = $existingLayingPlanningType->id;
            } else {
                $layingPlanningTypeId = (string) Str::uuid();
                DB::table('laying_planning_types')->insert([
                    'id' => $layingPlanningTypeId,
                    'type' => $old->laying_planning_type_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $existingLayingPlannings = DB::table('laying_plannings')
                ->where('serial_number', $old->serial_number)
                ->first();

            if ($existingLayingPlannings) {
                $layingPlanningsUid = $existingLayingPlannings->id;
            } else {
                $layingPlanningsUid = (string) Str::uuid();
                DB::table('laying_plannings')->insert([
                    'id' => $layingPlanningsUid,
                    'serial_number' => $old->serial_number,
                    'po_number' => $old->fabric_po,
                    'lot_id' => $lotUid,
                    'laying_planning_type_id' => $layingPlanningTypeId,
                    'laying_planning_parent_id' => null,
                    'color_id' => $colorUid,
                    'fabric_id' => $fabricUid,
                    'plan_date' => $old->plan_date,
                    'fabric_pattern' => $old->fabric_pattern ?? '-',
                    'is_combine' => false,
                    'laying_planning_combine_id' => null,
                    'is_set_item' => false,
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ]);
            }
            // ==========================================
            // SIMPAN DATA PREVIEW
            // ==========================================
            $previewData[] = [
                'tabel_utama' => $old,
                'color' => $color,
                'gls' => $gls,
                'buyer' => $buyer,
                'style' => $style,
                'tabel_detailss' => $details,
                'tabel_sizes' => $sizes,
                'mapped_uuids' => [
                    'customer_id_new' => $customerUid,
                    'gl_id_new' => $glUid,
                    'lot_id_new' => $lotUid,
                ],
            ];
        }

        // Simpan jadi file JSON di root project Laravel 13
        file_put_contents(
            base_path('sampel_data_lama.json'),
            json_encode($previewData, JSON_PRETTY_PRINT)
        );

        $this->info("Selesai! Buka file 'sampel_data_lama.json' di VS Code buat liat datanya.");
    }
}
