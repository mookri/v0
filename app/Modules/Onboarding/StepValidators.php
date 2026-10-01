<?php

namespace App\Modules\Onboarding;

/**
 * Per-category evidence validators (field catalog → code). Each category
 * validates its own shape and returns field-keyed reasons; the step PUT
 * route maps these to 422 validation_failed. Predicates stay coarse
 * (present/non-empty for completeness); these enforce shape and format.
 */
final class StepValidators
{
    /**
     * @return array<string, list<string>>
     */
    public static function validate(string $step, mixed $value): array
    {
        return match ($step) {
            'identity' => self::validateIdentity($value),
            'credentials' => self::validateCertifications($value),
            'services' => self::validateIdList($value, 'services'),
            'areas' => self::validateIdList($value, 'areas'),
            'availability' => self::validateAvailability($value),
            'business' => self::validateBusiness($value),
            'capacity' => self::validateCapacity($value),
            'documents' => self::validateIdList($value, 'documents'),
            'agreement' => self::validateAgreement($value),
            'review' => [],
            default => ['step' => ['unknown_step']],
        };
    }

    /**
     * @return array<string, list<string>>
     */
    private static function validateIdentity(mixed $value): array
    {
        $errors = [];
        if (! is_array($value)) {
            return ['identity' => ['must_be_object']];
        }
        $name = $value['name'] ?? null;
        if (! is_string($name) || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = ['name_must_be_2_100_chars'];
        }
        $phone = $value['phone'] ?? null;
        if (! is_string($phone) || preg_match('/^\+[1-9][0-9]{7,14}$/', $phone) !== 1) {
            $errors['phone'] = ['phone_must_be_e164'];
        }
        if (isset($value['email']) && filter_var($value['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = ['email_invalid'];
        }

        return $errors;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function validateCertifications(mixed $value): array
    {
        if (! is_array($value) || ($value['certifications'] ?? null) === null) {
            return ['credentials' => ['must_be_object']];
        }

        return self::validateIdList($value['certifications'], 'certifications');
    }

    /**
     * @return array<string, list<string>>
     */
    private static function validateIdList(mixed $value, string $field): array
    {
        if (! is_array($value) || $value === []) {
            return [$field => ['must_be_non_empty_list']];
        }
        foreach ($value as $item) {
            if (! is_string($item) || trim($item) === '') {
                return [$field => ['items_must_be_non_empty_strings']];
            }
        }

        return [];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function validateAvailability(mixed $value): array
    {
        if (! is_array($value) || ($value['set'] ?? null) !== true) {
            return ['availability' => ['must_be_confirmed']];
        }

        return [];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function validateBusiness(mixed $value): array
    {
        $errors = [];
        if (! is_array($value)) {
            return ['business' => ['must_be_object']];
        }
        $gstin = $value['gstin'] ?? null;
        if (! is_string($gstin) || preg_match('/^[0-9A-Z]{15}$/', $gstin) !== 1) {
            $errors['gstin'] = ['gstin_must_be_15_chars'];
        }
        $bank = $value['bankAccount'] ?? $value['bank_account'] ?? null;
        if (! is_string($bank) || trim($bank) === '') {
            $errors['bank_account'] = ['bank_account_required'];
        }

        return $errors;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function validateCapacity(mixed $value): array
    {
        if (! is_array($value)) {
            return ['capacity' => ['must_be_object']];
        }
        $slots = $value['weeklySlots'] ?? $value['weekly_slots'] ?? null;
        if (! is_int($slots) && ! (is_string($slots) && ctype_digit($slots))) {
            return ['weekly_slots' => ['weekly_slots_must_be_positive_integer']];
        }
        if ((int) $slots <= 0) {
            return ['weekly_slots' => ['weekly_slots_must_be_positive_integer']];
        }

        return [];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function validateAgreement(mixed $value): array
    {
        if (! is_array($value) || ($value['accepted'] ?? null) !== true) {
            return ['agreement' => ['must_be_accepted']];
        }

        return [];
    }
}
