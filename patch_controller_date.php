<?php
$file = '/var/www/html/wip/api/app/Features/Productivity/Controllers/ProductivityStatisticsController.php';
$content = file_get_contents($file);

$newMethod = <<<'CODE'
    public function detailedStatistics(Request $request)
    {
        $lots = Lot::where('is_cancelled', false)->with('glGroup.customer')->get();

        $query = ProductionItemDetail::join('production_items', 'production_item_details.production_item_id', '=', 'production_items.id')
            ->join('productions', 'production_items.production_id', '=', 'productions.id')
            ->select(
                'production_items.lot_id',
                'production_items.color',
                'production_item_details.size_name',
                DB::raw('SUM(qty_input) as input_qty'),
                DB::raw('SUM(qty_output) as output_qty'),
                DB::raw('MIN(productions.production_date) as start_date'),
                DB::raw('MAX(productions.production_date) as last_update')
            );
            
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('productions.production_date', [$request->start_date, $request->end_date]);
        }

        $outputs = $query->groupBy('production_items.lot_id', 'production_items.color', 'production_item_details.size_name')
            ->get();
CODE;

$pattern = '/public function detailedStatistics\(Request \$request\)\s*\{\s*\$lots = Lot::where\(\'is_cancelled\', false\)->with\(\'glGroup\.customer\'\)->get\(\);\s*\$outputs = ProductionItemDetail::join.*?->get\(\);/ms';
if (preg_match($pattern, $content)) {
    $content = preg_replace($pattern, $newMethod, $content);
    file_put_contents($file, $content);
    echo "Controller replaced successfully.\n";
} else {
    echo "Could not match method signature.\n";
}
