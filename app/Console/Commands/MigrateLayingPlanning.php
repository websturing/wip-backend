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
            ->whereYear('created_at', 2026) // Menyaring kolom plan_date yang tahunnya 2026
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get();

        $previewData = [];

        foreach ($oldPlannings as $old) {
            // Ambil relasi detailnya sekalian

            $user = DB::connection('legacy_db')
                ->table('users')
                ->where('id', $old->created_by)
                ->first();

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
                // Jika sudah ada, gunakan UUID yang sudah terdaftar dan update data
                $customerUid = $existingCustomer->id;
                DB::table('customers')->where('id', $customerUid)->update([
                    'name' => $buyer->name,
                    'updated_at' => now(),
                ]);
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
                DB::table('lots')->where('id', $lotUid)->update([
                    'gmt_qty' => $old->order_qty,
                    'lot_code' => $cleanGlNumber.'-'.$lotNumber,
                    'style_no' => $style->style,
                    'delivery_date' => $old->delivery_date,
                    'updated_at' => now(),
                ]);
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
                DB::table('colors')->where('id', $colorUid)->update([
                    'code' => $color->color_code,
                    'updated_at' => now(),
                ]);
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

            // cek apakah user ada di database baru
            $existingUser = $user ? DB::table('users')->where('id', $user->id)->first() : null;

            if ($existingUser) {
                $userId = $existingUser->id;
                if ($user) {
                    DB::table('users')->where('id', $userId)->update([
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $user->password,
                        'status' => $user->active ?? 1,
                        'updated_at' => now(),
                    ]);
                }
            } elseif ($user) {
                $userId = $user->id;
                DB::table('users')->insert([
                    'id' => $userId,
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => $user->password,
                    'status' => $user->active ?? 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $userId = null;
            }

            // ==========================================
            // AMAN KAN DATA LAYING PLANNING
            // ==========================================
            // 6. Cek apakah laying Planning ini sudah ada di DB baru

            // cek laying Planning Type
            $existingLayingPlanningTypeLegacy = DB::connection('legacy_db')->table('laying_planning_types')->where('id', $old->laying_planning_type_id)->first();
            $existingLayingPlanningType = DB::table('laying_planning_types')->where('type', $existingLayingPlanningTypeLegacy->type)->first();

            if ($existingLayingPlanningType) {
                $layingPlanningTypeId = $existingLayingPlanningType->id;
            } else {
                $layingPlanningTypeId = (string) Str::uuid();
                DB::table('laying_planning_types')->insert([
                    'id' => $layingPlanningTypeId,
                    'type' => $existingLayingPlanningTypeLegacy->type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $existingLayingPlannings = DB::table('laying_plannings')
                ->where('serial_number', $old->serial_number)
                ->first();

            if ($existingLayingPlannings) {
                $layingPlanningsUid = $existingLayingPlannings->id;
                DB::table('laying_plannings')->where('id', $layingPlanningsUid)->update([
                    'po_number' => $old->fabric_po,
                    'lot_id' => $lotUid,
                    'laying_planning_type_id' => $layingPlanningTypeId,
                    'color_id' => $colorUid,
                    'fabric_id' => $fabricUid,
                    'plan_date' => $old->plan_date,
                    'fabric_pattern' => $old->fabric_pattern ?? '-',
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
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
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ]);
            }

            foreach ($sizes as $size) {
                $existingSizeLagecy = DB::connection('legacy_db')->table('sizes')->where('id', $size->size_id)->first();
                if ($existingSizeLagecy) {
                    $existingSize = DB::table('sizes')->where('size', $existingSizeLagecy->size)->first();
                    if ($existingSize) {
                        $sizeId = $existingSize->id;
                    } else {
                        $sizeId = (string) Str::uuid();
                        DB::table('sizes')->insert([
                            'id' => $sizeId,
                            'size' => $existingSizeLagecy->size,
                            'created_at' => $existingSizeLagecy->created_at,
                            'updated_at' => $existingSizeLagecy->updated_at,
                        ]);
                    }
                }

                $exisitingLayingPlanningSize = DB::table('laying_planning_sizes')->where('laying_planning_id', $layingPlanningsUid)->where('size_id', $sizeId)->first();
                if ($exisitingLayingPlanningSize) {
                    DB::table('laying_planning_sizes')->where('id', $exisitingLayingPlanningSize->id)->update([
                        'order_qty' => $size->quantity,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('laying_planning_sizes')->insert([
                        'id' => (string) Str::uuid(),
                        'laying_planning_id' => $layingPlanningsUid,
                        'size_id' => $sizeId,
                        'order_qty' => $size->quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // LAYING PLANNING DETAILS
            foreach ($details as $detail) {
                $existingLayingPlanningDetail = DB::table('laying_planning_details')
                    ->where('laying_planning_id', $layingPlanningsUid)
                    ->where('table_number', $detail->table_number)
                    ->first();

                $existingLayingPlanningDetailTypes = DB::connection('legacy_db')->table('laying_planning_detail_types')->where('id', $detail->laying_planning_detail_type_id)->first();

                $layingPlannigDetailTypesId = null;

                if ($existingLayingPlanningDetailTypes) {
                    // Check if it exists in the new DB. The old db might use 'type' or 'detail_type'
                    $legacyTypeStr = $existingLayingPlanningDetailTypes->type ?? $existingLayingPlanningDetailTypes->detail_type ?? '-';

                    $layingPlannigDetailTypes = DB::table('laying_planning_detail_types')
                        ->where('detail_type', $legacyTypeStr)
                        ->first();

                    if ($layingPlannigDetailTypes) {
                        $layingPlannigDetailTypesId = $layingPlannigDetailTypes->id;
                    } else {
                        $layingPlannigDetailTypesId = (string) Str::uuid();
                        DB::table('laying_planning_detail_types')->insert([
                            'id' => $layingPlannigDetailTypesId,
                            'detail_type' => $legacyTypeStr,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if ($existingLayingPlanningDetail) {
                    $layingPlanningDetailUid = $existingLayingPlanningDetail->id;
                    DB::table('laying_planning_details')->where('id', $layingPlanningDetailUid)->update([
                        'laying_planning_detail_type_id' => $layingPlannigDetailTypesId,
                        'table_number' => $detail->table_number,
                        'layer_qty' => $detail->layer_qty,
                        'marker_code' => $detail->marker_code,
                        'marker_yard' => $detail->marker_yard,
                        'marker_inch' => $detail->marker_inch,
                        'allowance_inch' => 2,
                        'is_pilot_run' => 0,
                        'updated_by' => $userId,
                        'updated_at' => $detail->updated_at ?? now(),
                    ]);
                } else {
                    $layingPlanningDetailUid = (string) Str::uuid();
                    DB::table('laying_planning_details')->insert([
                        'id' => $layingPlanningDetailUid,
                        'laying_planning_id' => $layingPlanningsUid,
                        'laying_planning_detail_type_id' => $layingPlannigDetailTypesId,
                        'table_number' => $detail->table_number,
                        'layer_qty' => $detail->layer_qty,
                        'marker_code' => $detail->marker_code,
                        'marker_yard' => $detail->marker_yard,
                        'marker_inch' => $detail->marker_inch,
                        'allowance_inch' => 2,
                        'is_pilot_run' => 0,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                        'created_at' => $detail->created_at ?? now(),
                        'updated_at' => $detail->updated_at ?? now(),
                    ]);
                }

                $detailSizes = DB::connection('legacy_db')
                    ->table('laying_planning_detail_sizes')
                    ->where('laying_planning_detail_id', $detail->id)
                    ->get();

                // LAYING PLANNING SIZES
                foreach ($detailSizes as $size) {

                    $existingSizeLagecy = DB::connection('legacy_db')->table('sizes')->where('id', $size->size_id)->first();

                    if ($existingSizeLagecy) {

                        $existingSize = DB::table('sizes')->where('size', $existingSizeLagecy->size)->first();

                        if ($existingSize) {
                            $sizeId = $existingSize->id;
                        } else {
                            $sizeId = (string) Str::uuid();
                            DB::table('sizes')->insert([
                                'id' => $sizeId,
                                'size' => $existingSizeLagecy->size,
                                'created_at' => $existingSizeLagecy->created_at,
                                'updated_at' => $existingSizeLagecy->updated_at,
                            ]);
                        }

                    }

                    $existingLayingPlanningSize = DB::table('laying_planning_detail_sizes')
                        ->where('laying_planning_detail_id', $layingPlanningDetailUid)
                        ->where('size_id', $sizeId)
                        ->first();

                    if ($existingLayingPlanningSize) {
                        DB::table('laying_planning_detail_sizes')
                            ->where('id', $existingLayingPlanningSize->id)
                            ->update([
                                'ratio_per_size' => $size->ratio_per_size,
                                'updated_at' => $size->updated_at ?? now(),
                            ]);
                    } else {
                        $layingPlanningDetailSizeUid = (string) Str::uuid();
                        DB::table('laying_planning_detail_sizes')->insert([
                            'id' => $layingPlanningDetailSizeUid,
                            'laying_planning_detail_id' => $layingPlanningDetailUid,
                            'size_id' => $sizeId,
                            'ratio_per_size' => $size->ratio_per_size,
                            'created_at' => $size->created_at ?? now(),
                            'updated_at' => $size->updated_at ?? now(),
                        ]);
                    }
                }
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
