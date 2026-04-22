<?php

declare(strict_types=1);

namespace VIXI\CahSplit\Stats;

if (!defined('ABSPATH')) {
    exit;
}

final class Significance
{
    public function twoProportionPValue(
        int $successesA,
        int $trialsA,
        int $successesB,
        int $trialsB,
    ): ?float {
        if ($trialsA <= 0 || $trialsB <= 0) {
            return null;
        }
        $pA   = $successesA / $trialsA;
        $pB   = $successesB / $trialsB;
        $pool = ($successesA + $successesB) / ($trialsA + $trialsB);
        $se   = \sqrt($pool * (1.0 - $pool) * (1.0 / $trialsA + 1.0 / $trialsB));
        if ($se <= 0.0) {
            return null;
        }
        $z = ($pA - $pB) / $se;
        return $this->pValueTwoSided($z);
    }

    public function summarize(?float $pValue): string
    {
        if ($pValue === null) {
            return 'n/a';
        }
        if ($pValue < 0.01) {
            return \sprintf('p = %.3f ***', $pValue);
        }
        if ($pValue < 0.05) {
            return \sprintf('p = %.3f *', $pValue);
        }
        if ($pValue < 0.1) {
            return \sprintf('p = %.3f (.)', $pValue);
        }
        return \sprintf('p = %.3f', $pValue);
    }

    private function pValueTwoSided(float $z): float
    {
        return 2.0 * (1.0 - $this->standardNormalCdf(\abs($z)));
    }

    private function standardNormalCdf(float $z): float
    {
        $absZ = \abs($z);
        $t    = 1.0 / (1.0 + 0.2316419 * $absZ);
        $phi  = 0.3989422804014327 * \exp(-0.5 * $absZ * $absZ);
        $series = $t * (
            0.319381530
            + $t * (-0.356563782
            + $t * (1.781477937
            + $t * (-1.821255978
            + $t * 1.330274429)))
        );
        $cdf = 1.0 - $phi * $series;
        return $z >= 0 ? $cdf : 1.0 - $cdf;
    }
}
