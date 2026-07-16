<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class SizeHelper
{
    /**
     * Array urutan standar untuk ukuran pakaian (dari terkecil ke terbesar).
     * Meliputi ukuran bayi, anak, petite, standar, dan plus.
     *
     * @var array
     */
    public static $sizeOrder = [
        // Toddler / Kids
        '0-3M', '3-6M', '6-9M', '9-12M', '12-18M', '18-24M', '2T', '3T', '4T', '5T', '6T',
        // Petite sizes
        'PXXXS', 'PXXS', 'PXS', 'PP', 'PS', 'PM', 'PL', 'PXL', 'PXXL', 'P2XL', 'P3XL',
        // Standard sizes
        'XXXS', 'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '2XL', '3XL', '4XL', '5XL', '6XL',
        // Plus sizes
        '1X', '2X', '3X', '4X', '5X', '6X',
        // Free size
        'ALL SIZE', 'ALLSIZE', 'OS', 'FS'
    ];

    /**
     * Dapatkan prefix sorting (A, B, C) berdasarkan nama size, agar gampang di-sort oleh Collection atau usort.
     *
     * @param string $sizeName
     * @return string
     */
    public static function getSortablePrefix(string $sizeName): string
    {
        $sizeName = strtoupper(trim($sizeName));
        
        $index = array_search($sizeName, self::$sizeOrder);
        if ($index !== false) {
            return 'A' . str_pad($index, 5, '0', STR_PAD_LEFT);
        }
        
        // Jika angkanya murni (misal: 28, 29, 30)
        if (is_numeric($sizeName)) {
            return 'B' . str_pad($sizeName, 5, '0', STR_PAD_LEFT);
        }

        // Fallback natural sort untuk yang lain
        return 'C' . $sizeName;
    }

    /**
     * Urutkan array biasa (indexed array) berisi string ukuran.
     *
     * @param array $sizes
     * @return array
     */
    public static function sortArray(array $sizes): array
    {
        usort($sizes, function($a, $b) {
            $prefixA = self::getSortablePrefix($a);
            $prefixB = self::getSortablePrefix($b);
            
            // Natural sort behavior for the padded prefix
            return strnatcmp($prefixA, $prefixB);
        });
        
        return $sizes;
    }

    /**
     * Urutkan Laravel Collection berdasarkan ukuran.
     * 
     * @param Collection $collection Collection yang ingin di-sort
     * @param callable|string|null $sizeExtractor closure atau nama properti yang menghasilkan string size
     * @return Collection
     */
    public static function sortCollection(Collection $collection, $sizeExtractor = null): Collection
    {
        return $collection->sortBy(function ($item) use ($sizeExtractor) {
            if (is_callable($sizeExtractor)) {
                $sizeName = $sizeExtractor($item);
            } elseif (is_string($sizeExtractor)) {
                // If it's a dotted string like 'size.size', use data_get
                $sizeName = data_get($item, $sizeExtractor);
            } else {
                $sizeName = (string) $item;
            }
            
            return self::getSortablePrefix($sizeName ?? '');
        })->values();
    }
}
