<?php

declare(strict_types=1);

namespace VIXI\CahSplit;

if (!defined('ABSPATH')) {
    exit;
}

final class LeadPayloadParser
{
    private const SERVICE_LABEL_TO_RAW = [
        'Car Accident'                 => 'car_accident',
        'Motorcycle Accident'          => 'motorcycle_accident',
        'Trucking Accident'            => 'trucking_accident',
        'Bicycle or E-bike Accident'   => 'bicycle_accident',
        'Accident or Injury at Work'   => 'work_accident',
        'Pedestrian Accident'          => 'pedestrian_accident',
        'Other Accident'               => 'other_accident',
    ];

    private const ATTORNEY_LABEL_TO_RAW = [
        'Not Yet'            => 'not_yet',
        'I Have An Attorney' => 'has_attorney',
    ];

    private const FAULT_LABEL_TO_RAW = [
        'No'  => 'no',
        'Yes' => 'yes',
    ];

    private const INJURY_LABEL_TO_RAW = [
        'Yes' => 'yes',
        'No'  => 'no',
    ];

    private const TIMEFRAME_LABEL_TO_RAW = [
        'Within 1 Week'       => 'within_1_week',
        'Within 1-3 months'   => 'within_1_3_months',
        'Within 4-6 months'   => 'within_4_6_months',
        'Within 1 Year'       => 'within_1_year',
        'Within 2 Year'       => 'within_2_year',
        'Longer than 2 Year'  => 'longer_than_2_year',
    ];

    private const INSURED_LABEL_TO_RAW = [
        'Yes' => 'yes',
        'No'  => 'no',
    ];

    private const FIELD_LABELS = [
        'service_type'      => 'Type of service',
        'attorney'          => 'Attorney',
        'fault'             => 'Fault',
        'injury'            => 'Injury',
        'timeframe'         => 'Accident Happen',
        'state'             => 'State',
        'zipcode'           => 'What is your zip code?',
        'insured'           => 'Insured',
        'describe_accident' => 'Briefly describe your accident to us.',
        'first_name'        => 'First name',
        'last_name'         => 'Last name',
        'email'             => 'What is your email address?',
        'phone'             => 'What is your phone number?',
    ];

    public function parse(array $makePayload): array
    {
        $submission = $makePayload[0]['form_submission']['fields'] ?? [];
        if (!\is_array($submission)) {
            $submission = [];
        }

        $out = [];
        foreach (self::FIELD_LABELS as $column => $label) {
            $out[$column] = $this->valueByLabel($submission, $label);
        }

        $out['service_type'] = self::SERVICE_LABEL_TO_RAW[$out['service_type']]  ?? null;
        $out['attorney']     = self::ATTORNEY_LABEL_TO_RAW[$out['attorney']]     ?? null;
        $out['fault']        = self::FAULT_LABEL_TO_RAW[$out['fault']]           ?? null;
        $out['injury']       = self::INJURY_LABEL_TO_RAW[$out['injury']]         ?? null;
        $out['timeframe']    = self::TIMEFRAME_LABEL_TO_RAW[$out['timeframe']]   ?? null;
        $out['insured']      = self::INSURED_LABEL_TO_RAW[$out['insured']]       ?? null;

        $out['state']   = \is_string($out['state'])
            ? \strtolower(\trim($out['state']))
            : null;
        $out['zipcode'] = \is_string($out['zipcode'])
            ? \substr(\trim($out['zipcode']), 0, 16)
            : null;

        $out['phone'] = $this->normalizePhone($out['phone']);
        $out['email'] = \is_string($out['email']) ? \sanitize_email($out['email']) : null;

        $trustedForm = $this->valueByLabel($submission, 'Trusted Form Cert URL');
        $out['trustedform_cert_url'] = \is_string($trustedForm) && $trustedForm !== ''
            ? \esc_url_raw($trustedForm)
            : null;

        $out = $this->addUtms($out, $submission);

        foreach ($out as $key => $value) {
            if (\is_string($value)) {
                $out[$key] = \sanitize_text_field($value);
            }
        }

        return $out;
    }

    private function valueByLabel(array $fields, string $label): ?string
    {
        foreach ($fields as $field) {
            if (!\is_array($field)) {
                continue;
            }
            if (($field['label'] ?? null) === $label) {
                $value = $field['value'] ?? null;
                return $value === null ? null : (string) $value;
            }
        }
        return null;
    }

    private function normalizePhone(mixed $raw): ?string
    {
        if (!\is_string($raw) || $raw === '') {
            return null;
        }
        $digits = \preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        return \substr($digits, 0, 32);
    }

    private function addUtms(array $out, array $fields): array
    {
        $map = [
            'utm_source'         => ['hidden_978002841632858', 'utm_source'],
            'utm_medium'         => ['hidden_953875108661844', 'utm_medium'],
            'utm_term'           => ['hidden_25865364589303', 'utm_term'],
            'utm_campaign'       => ['hidden_337672242598594', 'utm_campaign'],
            'utm_adname'         => ['hidden_579780982435417', 'utm_adname'],
            'utm_adid'           => ['hidden_585157882311305', 'utm_adid'],
            'utm_adsetid'        => ['hidden_92922683992474', 'utm_adsetid'],
            'utm_campaignid'     => ['hidden_436124489257771', 'utm_campaignid'],
            'utm_placement'     => ['hidden_97816714524287', 'utm_placement'],
            'utm_sitesourcename' => ['hidden_77823743129070', 'utm_sitesourcename'],
            'utm_creative'       => ['hidden_202243593262175', 'utm_creative'],
            'utm_adsetname'      => ['hidden_utm_adsetname', 'utm_adsetname'],
            'utm_state'          => ['hidden_utm_state', 'utm_state'],
            'clickid'            => ['hidden_clickid', 'clickid'],
        ];

        foreach ($map as $column => [$fieldKey, $fallbackLabel]) {
            $value = null;
            if (isset($fields[$fieldKey]) && \is_array($fields[$fieldKey])) {
                $value = $fields[$fieldKey]['value'] ?? null;
            }
            if ($value === null || $value === '') {
                $value = $this->valueByLabel($fields, $fallbackLabel);
            }
            $out[$column] = ($value === null || $value === '') ? null : (string) $value;
        }

        return $out;
    }
}
