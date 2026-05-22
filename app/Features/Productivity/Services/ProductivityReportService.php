<?php

namespace App\Features\Productivity\Services;

use App\Features\Productivity\Models\Productivity;
use App\Features\Production\Models\Production;
use Illuminate\Support\Facades\Http;

class ProductivityReportService
{
    public function getSummaryData($date, $lineIds = null)
    {
        $query = Productivity::with(['line', 'lots.glGroup.customer'])
            ->whereDate('date', $date);

        if (!empty($lineIds)) {
            $query->whereIn('line_id', $lineIds);
        }

        $productivities = $query->get();

        // Custom Sort by Line Name (DS first, then NS, then numeric)
        $productivities = $productivities->sort(function($a, $b) {
            $nameA = strtoupper($a->line->name ?? '');
            $nameB = strtoupper($b->line->name ?? '');
            
            $prefixA = preg_replace('/[^A-Z]/', '', explode(' ', $nameA)[0] ?? '');
            $prefixB = preg_replace('/[^A-Z]/', '', explode(' ', $nameB)[0] ?? '');
            
            if ($prefixA !== $prefixB) {
                return strcmp($prefixA, $prefixB);
            }
            
            $isNsA = strpos($nameA, 'NS') !== false ? 1 : 0;
            $isNsB = strpos($nameB, 'NS') !== false ? 1 : 0;
            
            if ($isNsA !== $isNsB) {
                return $isNsA <=> $isNsB;
            }
            
            preg_match('/\d+/', $nameA, $matchA);
            preg_match('/\d+/', $nameB, $matchB);
            $numA = isset($matchA[0]) ? (int)$matchA[0] : 0;
            $numB = isset($matchB[0]) ? (int)$matchB[0] : 0;
            
            return $numA <=> $numB;
        });

        $allLineIds = $productivities->pluck('line_id')->unique();
        $maxDate = $productivities->max('date');

        $productionData = Production::whereIn('line_id', $allLineIds)
            ->whereDate('production_date', '<=', $maxDate)
            ->with(['items.details'])
            ->get();

        // Group by Line Prefix (A, B, C...)
        $groupedBySheet = $productivities->groupBy(function($p) {
            return substr(strtoupper($p->line->name ?? 'OTHER'), 0, 1);
        });

        $results = [];

        foreach ($groupedBySheet as $prefix => $sheetItems) {
            $allTotals = ['sewers' => 0, 'manpower' => 0, 'hours' => 0, 'working_hour_sum' => 0, 'target' => 0, 'output' => 0, 'lines_count' => 0];
            $catTotals = [];

            foreach ($sheetItems as $productivity) {
                $name = strtoupper($productivity->line->name ?? '');
                $isNs = strpos($name, 'NS') !== false;
                preg_match('/\d+/', $name, $matches);
                $num = isset($matches[0]) ? (int)$matches[0] : 0;
                
                $rangeStart = floor(($num - 1) / 8) * 8 + 1;
                $rangeEnd = $rangeStart + 7;
                $catKey = ($isNs ? 'NS' : 'DS') . '_' . $rangeStart;
                
                if (!isset($catTotals[$catKey])) {
                    $catTotals[$catKey] = [
                        'prefix' => $prefix, 'isNs' => $isNs, 'start' => $rangeStart, 'end' => $rangeEnd,
                        'sewers' => 0, 'manpower' => 0, 'hours' => 0, 'working_hour_sum' => 0, 'target' => 0, 'output' => 0, 'lines_count' => 0
                    ];
                }

                $groupedLots = $this->groupLots($productivity->lots);
                $allTotals['lines_count']++;
                $catTotals[$catKey]['lines_count']++;

                foreach ($groupedLots as $lotGroup) {
                    $firstLot = $lotGroup[0];
                    $smv = (float)($firstLot->pivot->smv ?? $productivity->smv ?? 0);
                    $mp = (float)($productivity->manpower ?? $firstLot->pivot->manpower ?? 0);
                    $mg = (float)($productivity->sewer ?? $firstLot->pivot->sewer ?? 0);
                    $wh = (float)($firstLot->pivot->working_hour ?? $productivity->working_hour ?? 8);
                    $target = $smv > 0 ? floor(($mp + $mg) * $wh * 60 / $smv) : 0;
                    
                    $pData = $productionData->filter(fn($p) => 
                        $p->line_id === $productivity->line_id && 
                        $p->production_date->format('Y-m-d') === $productivity->date->format('Y-m-d')
                    );
                    $section = strtoupper($firstLot->pivot->section ?? 'ALL');
                    $lotOutputs = collect($lotGroup)->map(function ($l) use ($pData, $section) {
                        return collect($pData)
                            ->flatMap->items
                            ->filter(fn($pi) => (string)$pi->lot_id === (string)$l->id && strtoupper($pi->section ?? 'ALL') === $section)
                            ->flatMap->details->sum('qty_output');
                    })->toArray();
                    $output = !empty($lotOutputs) ? array_sum($lotOutputs) : 0;

                    if ($lotGroup === $groupedLots[0]) {
                        $allTotals['sewers'] += $mg;
                        $allTotals['manpower'] += $mp;
                        $allTotals['working_hour_sum'] += $wh;
                        
                        $catTotals[$catKey]['sewers'] += $mg;
                        $catTotals[$catKey]['manpower'] += $mp;
                        $catTotals[$catKey]['working_hour_sum'] += $wh;
                    }
                    
                    $allTotals['hours'] += (($mg + $mp) * $wh);
                    $allTotals['target'] += $target;
                    $allTotals['output'] += $output;
                    
                    $catTotals[$catKey]['hours'] += (($mg + $mp) * $wh);
                    $catTotals[$catKey]['target'] += $target;
                    $catTotals[$catKey]['output'] += $output;
                }
            }
            
            uasort($catTotals, function($a, $b) {
                if ($a['isNs'] !== $b['isNs']) return $a['isNs'] ? 1 : -1;
                return $a['start'] <=> $b['start'];
            });

            // Format for UI
            $categories = [];
            foreach ($catTotals as $cat) {
                $avgHours = $cat['lines_count'] > 0 ? $cat['working_hour_sum'] / $cat['lines_count'] : 0;
                $ach = $cat['target'] > 0 ? ($cat['output'] / $cat['target']) : 0;
                $categories[] = [
                    'label' => "SEWING ({$prefix}{$cat['start']} - {$prefix}{$cat['end']})",
                    'shiftName' => $cat['isNs'] ? 'NIGHT' : 'DAY',
                    'shiftCode' => $cat['isNs'] ? 'NS' : 'DS',
                    'start' => $cat['start'],
                    'end' => $cat['end'],
                    'metrics' => [
                        'Sewer/Helper' => $cat['sewers'] + $cat['manpower'],
                        'Total Hours' => $avgHours,
                        'Total Daily Target' => $cat['target'],
                        'Total Production Output' => $cat['output'],
                        '% of Achieved' => $ach
                    ]
                ];
            }

            $avgHoursAll = $allTotals['lines_count'] > 0 ? $allTotals['working_hour_sum'] / $allTotals['lines_count'] : 0;
            $achAll = $allTotals['target'] > 0 ? ($allTotals['output'] / $allTotals['target']) : 0;

            $results[] = [
                'prefix' => $prefix,
                'categories' => $categories,
                'allTotals' => [
                    'Sewer/Helper' => $allTotals['sewers'] + $allTotals['manpower'],
                    'Total Hours' => $avgHoursAll,
                    'Total Daily Target' => $allTotals['target'],
                    'Total Production Output' => $allTotals['output'],
                    '% of Achieved' => $achAll,
                    'lines_count' => $allTotals['lines_count']
                ]
            ];
        }

        return $results;
    }

    private function groupLots($lots)
    {
        $grouped = [];
        $visited = [];

        foreach ($lots as $lot) {
            if (in_array($lot->id, $visited)) continue;

            $mergeId = $lot->pivot->merge_id ?? null;
            if (!$mergeId) {
                $grouped[] = [$lot];
                $visited[] = $lot->id;
                continue;
            }

            $group = $lots->filter(function($l) use ($mergeId) {
                return ($l->pivot->merge_id ?? null) === $mergeId;
            })->values()->all();

            $grouped[] = $group;
            foreach ($group as $gLot) {
                $visited[] = $gLot->id;
            }
        }
        return $grouped;
    }
}
