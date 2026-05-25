<?php
$file = '/var/www/html/wip/api/app/Features/Productivity/Controllers/ProductivityStatisticsController.php';
$content = file_get_contents($file);

$newMethod = <<<'CODE'
    public function detailedStatistics(Request $request)
    {
        $lots = Lot::where('is_cancelled', false)->with('glGroup.customer')->get();

        $outputs = ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->select(
                'production_items.lot_id',
                'production_items.color',
                'production_item_details.size_name',
                DB::raw('SUM(qty_input) as input_qty'),
                DB::raw('SUM(qty_output) as output_qty'),
                DB::raw('MIN(productions.production_date) as start_date'),
                DB::raw('MAX(productions.production_date) as last_update')
            )
            ->groupBy('production_items.lot_id', 'production_items.color', 'production_item_details.size_name')
            ->get();

        $data = [];

        foreach ($outputs as $out) {
            $lot = $lots->firstWhere('id', $out->lot_id);
            if (!$lot) continue;

            $colorName = trim($out->color);
            $baseName = $colorName;
            $type = 'other';
            if (preg_match('/(.*?)\s*\((TOP|PANT|PANTS)\)$/i', $colorName, $matches)) {
                $baseName = trim($matches[1]);
                $typeMatch = strtoupper($matches[2]);
                $type = $typeMatch === 'TOP' ? 'top' : 'pant';
            }
            
            $size = trim($out->size_name);
            if ($size && strtoupper($size) !== 'TOTAL') {
                $baseName .= ' - ' . $size;
                $displayColor = $colorName . ' - ' . $size;
            } else {
                $displayColor = $colorName;
            }

            $orderQty = (int) $out->input_qty;
            $balance = $out->output_qty - $orderQty;
            
            $startDate = Carbon::parse($out->start_date);
            $lastUpdate = Carbon::parse($out->last_update);
            $daysRunning = $startDate->diffInDays($lastUpdate) + 1;

            $achievement = $orderQty > 0 ? ($out->output_qty / $orderQty) * 100 : 0;

            $data[] = [
                'gl_number' => $lot->lot_code,
                'color' => $displayColor,
                'base_name' => $baseName,
                'type' => $type,
                'order_qty' => $orderQty,
                'output_qty' => (int) $out->output_qty,
                'balance' => (int) $balance,
                'days_running' => $daysRunning,
                'achievement' => round($achievement, 2),
                'last_update' => $lastUpdate->format('Y-m-d')
            ];
        }

        $aggregated = [];
        foreach ($data as $item) {
            $glKey = $item['gl_number'];
            if (!isset($aggregated[$glKey])) {
                $aggregated[$glKey] = [
                    'gl_number' => $item['gl_number'],
                    'order_qty' => 0, 
                    'output_qty' => 0,
                    'balance' => 0,
                    'days_running' => $item['days_running'],
                    'achievement' => 0,
                    'last_update' => $item['last_update'],
                    'is_set_item' => false,
                    'colors' => [],
                    'color_parts' => []
                ];
            }
            
            if ($item['last_update'] > $aggregated[$glKey]['last_update']) {
                $aggregated[$glKey]['last_update'] = $item['last_update'];
            }
            if ($item['days_running'] > $aggregated[$glKey]['days_running']) {
                $aggregated[$glKey]['days_running'] = $item['days_running'];
            }
            
            $aggregated[$glKey]['colors'][] = [
                'color' => $item['color'],
                'order_qty' => $item['order_qty'],
                'output_qty' => $item['output_qty'],
                'balance' => $item['balance'],
                'achievement' => $item['achievement']
            ];

            $baseName = $item['base_name'];
            $type = $item['type'];

            if (!isset($aggregated[$glKey]['color_parts'][$baseName])) {
                $aggregated[$glKey]['color_parts'][$baseName] = [
                    'top_input' => 0, 'pant_input' => 0, 'other_input' => 0,
                    'top_output' => 0, 'pant_output' => 0, 'other_output' => 0,
                    'has_set' => false
                ];
            }
            
            $aggregated[$glKey]['color_parts'][$baseName][$type . '_input'] += $item['order_qty'];
            $aggregated[$glKey]['color_parts'][$baseName][$type . '_output'] += $item['output_qty'];
            
            if ($type !== 'other') {
                $aggregated[$glKey]['color_parts'][$baseName]['has_set'] = true;
                $aggregated[$glKey]['is_set_item'] = true;
            }
        }

        foreach ($aggregated as &$gl) {
            $totalOutput = 0;
            $totalInput = 0;
            foreach ($gl['color_parts'] as $baseName => $parts) {
                if ($parts['has_set']) {
                    $totalInput += min($parts['top_input'], $parts['pant_input']);
                    $totalInput += $parts['other_input'];
                    
                    $totalOutput += min($parts['top_output'], $parts['pant_output']);
                    $totalOutput += $parts['other_output'];
                } else {
                    $totalInput += $parts['other_input'];
                    $totalOutput += $parts['other_output'];
                }
            }
            
            $gl['order_qty'] = $totalInput;
            $gl['output_qty'] = $totalOutput;
            $gl['balance'] = $gl['output_qty'] - $gl['order_qty'];
            $gl['achievement'] = $gl['order_qty'] > 0 
                ? round(($gl['output_qty'] / $gl['order_qty']) * 100, 2) 
                : 0;
                
            unset($gl['color_parts']); 
        }

        return response()->json([
            'status' => 'success',
            'data' => array_values($aggregated)
        ]);
    }
CODE;

$pattern = '/public function detailedStatistics\(Request \$request\).*?^    \}/ms';
if (preg_match($pattern, $content)) {
    $content = preg_replace($pattern, $newMethod, $content);
    file_put_contents($file, $content);
    echo "Controller replaced successfully.\n";
} else {
    echo "Could not match method signature.\n";
}
