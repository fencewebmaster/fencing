<?php

declare(strict_types=1);

namespace Fc\Admin\Services;

/**
 * GO's Slat fence heights: every height its cut-to-size order form (V9.03) offers in its Height dropdowns. The list
 * behind the Slat style's `fence_heights_mm` setting, read here by the rollout script (writable/add-slat-heights.php)
 * and by the Fence Styles editor's "Load GO's heights".
 */
final class SlatHeightListService
{
    /** The setting in the fence file: [slat size][gap][number of slats] => height in mm. */
    public const SETTING_KEY = 'fence_heights_mm';

    /** The style whose lists the Slat gate reads, and Slat Infill too wherever it has no list of its own. */
    public const OWNER_SLUG = 'slat';

    /**
     * Styles that may hold the setting. Slat Infill's own list for a slat size and gap comes before Slat's, as the
     * planner reads them (SlatFence.slatHeightListSlugs in fences/calc/slat_fence.js).
     */
    public const STYLE_SLUGS = ['slat', 'slat_fence_infill'];

    /** Tenths of a mm, so every height is exact: a 65 slat is designed at 65.3 and a 90 at 90.3. */
    private const WIDTHS = [65 => 653, 90 => 903];

    /** Xpress gaps and what the snap-in spacers fit them at: a 9 gap at 9.3 and a 20 gap at 21.1. */
    private const XPRESS_GAPS = [0 => 0, 5 => 50, 9 => 93, 20 => 211];

    private const QUICKSCREEN_GAPS = [12, 15, 30];

    /**
     * Gaps 0, 5, 9 and 20 are GO's Xpress sheet, 12, 15 and 30 its Quickscreen sheet: the split the planner's panel
     * height already follows.
     *
     * @return array<int, array<int, array<int, int>>> [slat size][gap][slats] => mm
     */
    public static function goHeights(): array
    {
        $heights = [];
        foreach (self::WIDTHS as $size => $width) {
            $byGap = [];
            foreach (self::XPRESS_GAPS as $gap => $fitted) {
                $byGap[$gap] = self::xpress($width, $fitted);
            }
            foreach (self::QUICKSCREEN_GAPS as $gap) {
                $byGap[$gap] = self::quickscreen($width, $gap * 10);
            }
            ksort($byGap);
            $heights[$size] = $byGap;
        }

        return $heights;
    }

    /**
     * GO's Xpress sheet (BAYG!W56): ROUNDDOWN(2 x 3 end caps + N x (width + gap) - gap), from 3 slats up to the shorter
     * of the 6000 side frame and the 5800 centre rail + 2 gaps + 2 caps.
     *
     * @return array<int, int> slats => height
     */
    private static function xpress(int $width, int $gap): array
    {
        $pitch = $width + $gap;
        $tallest = min(60000, 58000 + 2 * $gap + 60);
        $heights = [];
        for ($slats = 3, $most = intdiv($tallest - 60 + $gap, $pitch); $slats <= $most; $slats++) {
            $heights[$slats] = intdiv(60 + $slats * $pitch - $gap, 10);
        }

        return $heights;
    }

    /**
     * GO's Quickscreen sheet (QSHS!Z51): ROUND(N x (width + gap) - gap + one 3 cap), from 3 slats up to the 5800 side
     * frame.
     *
     * @return array<int, int> slats => height
     */
    private static function quickscreen(int $width, int $gap): array
    {
        $pitch = $width + $gap;
        $heights = [];
        for ($slats = 3, $most = intdiv(58000 + $gap, $pitch); $slats <= $most; $slats++) {
            $heights[$slats] = intdiv($slats * $pitch - $gap + 30 + 5, 10);
        }

        return $heights;
    }
}
