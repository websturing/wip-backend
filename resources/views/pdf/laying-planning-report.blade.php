<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>LAYING PLANNING & CUTTING REPORT</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .title { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 5px; }
        .subtitle { text-align: center; font-weight: bold; font-size: 12px; margin-bottom: 20px; }
        .info-table { width: 100%; font-size: 10px; margin-bottom: 20px; }
        .info-table td { padding: 2px 5px; }
        .main-table { width: 100%; border-collapse: collapse; font-size: 9px; text-align: center; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 4px; }
        .main-table th { background-color: #f5f5f5; }
        .footer-table { width: 100%; margin-top: 50px; text-align: center; }
        .footer-table td { width: 25%; }
        .signature-line { margin-top: 50px; border-top: 1px solid #000; width: 80%; display: inline-block; }
        .remark { margin-top: 10px; font-size: 10px; }
    </style>
</head>
<body>
    <div style="position: absolute; top: 0; right: 0; text-align: right; font-size: 8px;">
        RP-GLA-CUT-002-00<br>Rev 00
    </div>

    <div style="font-weight: bold; font-size: 12px;">PT. GHIM LI INDONESIA</div>

    <div class="title">LAYING PLANNING & CUTTING REPORT</div>
    <div class="subtitle">{{ $data->serial_number }}</div>

    <table class="info-table">
        <tr>
            <td width="8%"><b>Buyer</b></td>
            <td width="22%">{{ $data->lot->glGroup->customer->name ?? $data->lot->brand ?? '-' }}</td>
            <td width="10%"><b>Order Qty</b></td>
            <td width="10%">{{ $data->lot->gmt_qty ?? 0 }}</td>
            <td width="10%"><b>Fabric P/O</b></td>
            <td width="20%">{{ $data->po_number ?? $data->lot->po_number ?? '-' }}</td>
            <td width="10%"><b>Delivery Date:</b></td>
            <td width="10%">{{ $data->lot->delivery_date ? date('d F Y', strtotime($data->lot->delivery_date)) : '-' }}</td>
        </tr>
        <tr>
            <td><b>Style</b></td>
            <td>{{ $data->lot->style_no ?? '-' }}</td>
            <td><b>Total Cut Qty</b></td>
            <td>
                @php
                    $headerTotalCut = 0;
                    foreach($details as $d) {
                        foreach($d->sizes as $dsz) {
                            $headerTotalCut += ($dsz->ratio_per_size * $d->layer_qty);
                        }
                    }
                @endphp
                {{ $headerTotalCut }}
            </td>
            <td><b>Fabric Type</b></td>
            <td>{{ $data->fabric->standard_content ?? $data->fabric->content ?? '-' }}</td>
            <td><b>Plan Date:</b></td>
            <td>{{ $data->plan_date ? date('d F Y', strtotime($data->plan_date)) : '-' }}</td>
        </tr>
        <tr>
            <td><b>GL</b></td>
            <td>{{ $data->lot->lot_code ?? '-' }}</td>
            <td></td><td></td>
            <td><b>Fabric Cons</b></td>
            <td>{{ $data->fabric_pattern ?? '-' }}</td>
            <td></td><td></td>
        </tr>
        <tr>
            <td><b>Color</b></td>
            <td colspan="3">{{ $data->color->standard_name ?? $data->color->name ?? '-' }}</td>
            <td><b>Description</b></td>
            <td colspan="3">{{ $data->description ?? '-' }}</td>
        </tr>
    </table>

    @php
        $sizeCount = $sizes->count() > 0 ? $sizes->count() : 1;
        // Pre-compute grand totals for ratio per size across all details
        $grandRatioPerSize = [];
        $grandTotalYds = 0;
        $grandTotalLay = 0;
        foreach ($sizes as $sIdx => $sz) {
            $grandRatioPerSize[$sIdx] = 0;
        }
        foreach ($details as $detail) {
            $grandTotalYds += $detail->total_length;
            $grandTotalLay += $detail->layer_qty;
            foreach ($sizes as $sIdx => $sz) {
                foreach ($detail->sizes as $dsz) {
                    if ($dsz->size_id == $sz->id || $dsz->size_name == ($sz->size ?? '')) {
                        $grandRatioPerSize[$sIdx] += ($dsz->ratio_per_size * $detail->layer_qty);
                        break;
                    }
                }
            }
        }
        $totalOrder = 0;
        foreach ($sizes as $sz) {
            $totalOrder += ($sz->pivot->order_qty ?? 0);
        }
        $grandTotalRatio = array_sum($grandRatioPerSize);
    @endphp

    <table class="main-table">
        <thead>
            {{-- Row 1: Group headers --}}
            <tr>
                <th rowspan="3">No Laying Sheet</th>
                <th rowspan="3">Batch # No.</th>
                <th colspan="{{ $sizeCount }}">Size/Order</th>
                <th rowspan="3">Total</th>
                <th rowspan="3">Yds Qty</th>
                <th rowspan="3" colspan="2">Marker Code</th>
                <th rowspan="3">LOT</th>
                <th colspan="3">Marker</th>
                <th colspan="{{ $sizeCount }}">Ratio</th>
                <th rowspan="3">Lay Qty</th>
                <th rowspan="3">Cut Qty</th>
                <th rowspan="3">Date</th>
                <th rowspan="3">Layer</th>
                <th rowspan="3">Cutter</th>
                <th rowspan="3">Emb Print</th>
                <th rowspan="3">Sew Line</th>
            </tr>
            {{-- Row 2: Size names --}}
            <tr>
                @if($sizes->count() > 0)
                    @foreach($sizes as $sz)
                        <th>{{ $sz->size ?? '-' }}</th>
                    @endforeach
                @else
                    <th>-</th>
                @endif
                <th>Length</th>
                <th>Yds</th>
                <th>Inch</th>
                @if($sizes->count() > 0)
                    @foreach($sizes as $sz)
                        <th>{{ $sz->size ?? '-' }}</th>
                    @endforeach
                @else
                    <th>-</th>
                @endif
            </tr>
            {{-- Row 3: Order Qty per Size --}}
            <tr>
                @if($sizes->count() > 0)
                    @foreach($sizes as $sz)
                        <th>{{ $sz->pivot->order_qty ?? 0 }}</th>
                    @endforeach
                @else
                    <th>0</th>
                @endif
                <th>{{ $totalOrder }}</th>
                <th colspan="16"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $index => $detail)
                @php
                    $detailTotalRatio = 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->batch_no ?? '-' }}</td>

                    {{-- Size/Order (ratio × layer per size for this detail) --}}
                    @if($sizes->count() > 0)
                        @foreach($sizes as $sz)
                            @php
                                $ratioVal = 0;
                                foreach($detail->sizes as $dsz) {
                                    if($dsz->size_id == $sz->id || $dsz->size_name == ($sz->size ?? '')) {
                                        $ratioVal = $dsz->ratio_per_size * $detail->layer_qty;
                                        $detailTotalRatio += $ratioVal;
                                        break;
                                    }
                                }
                            @endphp
                            <td>{{ $ratioVal > 0 ? $ratioVal : '-' }}</td>
                        @endforeach
                    @else
                        <td>-</td>
                    @endif

                    <td>{{ $detailTotalRatio > 0 ? $detailTotalRatio : '-' }}</td>
                    <td>{{ number_format($detail->total_length, 2) }}</td>
                    <td colspan="2">{{ $detail->marker_code ?? '-' }}</td>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ number_format($detail->marker_length, 2) }}</td>
                    <td>{{ $detail->marker_yard }}</td>
                    <td>{{ $detail->marker_inch }}</td>

                    {{-- Ratio per size (just ratio_per_size, not multiplied) --}}
                    @if($sizes->count() > 0)
                        @foreach($sizes as $sz)
                            @php
                                $ratioOnly = '-';
                                foreach($detail->sizes as $dsz) {
                                    if($dsz->size_id == $sz->id || $dsz->size_name == ($sz->size ?? '')) {
                                        $ratioOnly = $dsz->ratio_per_size;
                                        break;
                                    }
                                }
                            @endphp
                            <td>{{ $ratioOnly }}</td>
                        @endforeach
                    @else
                        <td>-</td>
                    @endif

                    <td>{{ $detail->layer_qty }}</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
            @endforeach

            {{-- Empty separator row --}}
            <tr>
                <td>.</td>
                <td colspan="{{ 16 + ($sizeCount * 2) }}"></td>
            </tr>

            {{-- Total Row --}}
            <tr style="font-weight: bold;">
                <td colspan="2">Total</td>
                {{-- Grand total ratio × layer per size --}}
                @if($sizes->count() > 0)
                    @foreach($sizes as $sIdx => $sz)
                        <td>{{ $grandRatioPerSize[$sIdx] }}</td>
                    @endforeach
                @else
                    <td>-</td>
                @endif
                <td>{{ $grandTotalRatio }}</td>
                <td>{{ number_format($grandTotalYds, 2) }}</td>
                <td colspan="6"></td>
                @if($sizes->count() > 0)
                    @foreach($sizes as $sz)
                        <td></td>
                    @endforeach
                @else
                    <td></td>
                @endif
                <td>{{ $grandTotalLay }}</td>
                <td>-</td>
                <td colspan="5"></td>
            </tr>

            {{-- (+/-) Selisih Row --}}
            <tr style="font-weight: bold;">
                <td colspan="2">( + / - )</td>
                @if($sizes->count() > 0)
                    @foreach($sizes as $sIdx => $sz)
                        @php
                            $orderQtyForSize = $sz->pivot->order_qty ?? 0;
                            $diff = $grandRatioPerSize[$sIdx] - $orderQtyForSize;
                        @endphp
                        <td>{{ $diff >= 0 ? '+' : '' }}{{ $diff }}</td>
                    @endforeach
                @else
                    <td>-</td>
                @endif
                @php $totalDiff = $grandTotalRatio - $totalOrder; @endphp
                <td>{{ $totalDiff >= 0 ? '+' : '' }}{{ $totalDiff }}</td>
                <td>
                    @if($totalOrder > 0)
                        {{ number_format(($grandTotalRatio / $totalOrder) * 100, 2) }} %
                    @else
                        - %
                    @endif
                </td>
                <td colspan="{{ 13 + $sizeCount }}"></td>
            </tr>
        </tbody>
    </table>

    <div class="remark">
        <b>Remark:</b><br>
        @foreach($details as $detail)
            @if($detail->materials && count($detail->materials) > 0)
                @foreach($detail->materials as $mat)
                    - ({{ $mat->type->detail_type ?? '-' }}): {{ number_format($mat->value_per_layer, 2) }} {{ $mat->unit ?? 'yd' }}/L<br>
                @endforeach
            @endif
        @endforeach
    </div>

    <table class="footer-table">
        <tr>
            <td>
                Prepared by:<br>
                <div class="signature-line"></div>
            </td>
            <td></td>
            <td>
                Authorized by:<br>
                <div class="signature-line"></div>
            </td>
            <td>
                Approved by:<br>
                <div class="signature-line"></div>
            </td>
        </tr>
    </table>

    <div style="text-align: right; font-size: 8px; margin-top: 20px;">
        Print By: {{ auth()->user()->name ?? 'User' }}
    </div>
</body>
</html>
