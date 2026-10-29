<?php

namespace App\Support;

class TicketPaperSize
{
    /**
     * Estimate one continuous thermal-paper page from the printed item rows.
     * Dompdf uses points for custom paper dimensions.
     *
     * @param iterable<object> $items
     */
    public static function heightForItems(
        iterable $items,
        float $fixedContentHeight,
        int $charactersPerLine,
        float $lineHeight,
        float $rowSpacing,
        ?callable $printedText = null,
    ): float {
        $height = $fixedContentHeight;
        $charactersPerLine = max(1, $charactersPerLine);

        foreach ($items as $item) {
            $text = $printedText
                ? (string) $printedText($item)
                : (string) ($item->descripcion ?? '');
            $lines = 0;

            foreach (preg_split('/\R/u', trim($text)) ?: [''] as $line) {
                $length = mb_strlen($line);
                $lines += max(1, (int) ceil($length / $charactersPerLine));
            }

            // Each row also prints quantity and amount on a separate detail line.
            $height += $rowSpacing + ($lines * $lineHeight);
        }

        return (float) ceil(max(360, $height));
    }
}
