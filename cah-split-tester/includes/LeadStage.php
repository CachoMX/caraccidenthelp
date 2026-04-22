<?php

declare(strict_types=1);

namespace VIXI\CahSplit;

if (!defined('ABSPATH')) {
    exit;
}

final class LeadStage
{
    public const STAGE_QUALIFIED    = 'qualified';
    public const STAGE_DISQUALIFIED = 'disqualified';
    public const STAGE_UNKNOWN      = 'unknown';

    public const URL_QUALIFIED    = 'https://caraccidenthelp.net/thank-you/?lead_stage=qualified-lead&from_cah_form=1';
    public const URL_DISQUALIFIED = 'https://caraccidenthelp.net/diminished-value-claim/?lead_stage=disqualified-lead&from_cah_form=1';

    private const QUALIFIED_SERVICES = [
        'car_accident',
        'motorcycle_accident',
        'trucking_accident',
    ];

    private const QUALIFIED_TIMEFRAMES = [
        'within_1_week',
        'within_1_3_months',
        'within_4_6_months',
        'within_1_year',
    ];

    public function compute(array $fields): string
    {
        $required = ['service_type', 'attorney', 'fault', 'injury', 'timeframe'];
        foreach ($required as $key) {
            if (empty($fields[$key])) {
                return self::STAGE_UNKNOWN;
            }
        }

        $qualified = (
            $fields['fault']    === 'no'
            && $fields['injury']   === 'yes'
            && $fields['attorney'] === 'not_yet'
            && \in_array($fields['service_type'], self::QUALIFIED_SERVICES, true)
            && \in_array($fields['timeframe'], self::QUALIFIED_TIMEFRAMES, true)
        );

        return $qualified ? self::STAGE_QUALIFIED : self::STAGE_DISQUALIFIED;
    }

    public function redirectUrl(string $stage): ?string
    {
        return match ($stage) {
            self::STAGE_QUALIFIED    => self::URL_QUALIFIED,
            self::STAGE_DISQUALIFIED => self::URL_DISQUALIFIED,
            default                  => null,
        };
    }
}
