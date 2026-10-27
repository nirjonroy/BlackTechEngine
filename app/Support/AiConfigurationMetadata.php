<?php

namespace App\Support;

use App\Enums\AiModelCapability;
use InvalidArgumentException;
use JsonException;
use Stringable;

class AiConfigurationMetadata
{
    /** @var list<string> */
    public const ROUTE_SETTING_KEYS = [];

    private const MAX_AUDIT_DEPTH = 4;

    private const MAX_AUDIT_ENTRIES = 50;

    private const MAX_AUDIT_STRING_LENGTH = 1000;

    /**
     * @param  array<array-key, mixed>  $capabilities
     * @return list<string>
     */
    public static function capabilities(array $capabilities): array
    {
        if (! array_is_list($capabilities)) {
            throw new InvalidArgumentException('AI model capabilities must be a list.');
        }

        $allowedCapabilities = array_column(AiModelCapability::cases(), 'value');
        $validatedCapabilities = [];

        foreach ($capabilities as $capability) {
            if (! is_string($capability) || ! in_array($capability, $allowedCapabilities, true)) {
                throw new InvalidArgumentException('AI model capabilities contain an unsupported value.');
            }

            if (in_array($capability, $validatedCapabilities, true)) {
                throw new InvalidArgumentException('AI model capabilities must not contain duplicates.');
            }

            $validatedCapabilities[] = $capability;
        }

        return $validatedCapabilities;
    }

    /**
     * @param  array<array-key, mixed>|null  $settings
     * @return array<string, mixed>|null
     */
    public static function routeSettings(?array $settings): ?array
    {
        if ($settings === null) {
            return null;
        }

        if (array_is_list($settings) && $settings === []) {
            return [];
        }

        $unsupportedKeys = array_diff(array_keys($settings), self::ROUTE_SETTING_KEYS);

        if ($unsupportedKeys !== []) {
            throw new InvalidArgumentException('AI workload route settings contain unsupported keys.');
        }

        return $settings;
    }

    /**
     * @param  array<array-key, mixed>  $changes
     * @return array<array-key, mixed>
     */
    public static function auditChanges(array $changes): array
    {
        $entryCount = 0;
        self::validateAuditValue($changes, 0, $entryCount);

        return $changes;
    }

    private static function validateAuditValue(mixed $value, int $depth, int &$entryCount): void
    {
        if ($depth > self::MAX_AUDIT_DEPTH) {
            throw new InvalidArgumentException('AI configuration audit changes are nested too deeply.');
        }

        if (is_array($value)) {
            foreach ($value as $key => $nestedValue) {
                $entryCount++;

                if ($entryCount > self::MAX_AUDIT_ENTRIES) {
                    throw new InvalidArgumentException('AI configuration audit changes contain too many entries.');
                }

                if (is_string($key) && preg_match('/authorization|api[_-]?key|access[_-]?token|refresh[_-]?token|secret|credential|prompt|response|request[_-]?payload|headers?/i', $key) === 1) {
                    throw new InvalidArgumentException('AI configuration audit changes contain a sensitive field.');
                }

                self::validateAuditValue($nestedValue, $depth + 1, $entryCount);
            }

            return;
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_string($value)) {
            if (mb_strlen($value) > self::MAX_AUDIT_STRING_LENGTH) {
                throw new InvalidArgumentException('AI configuration audit values must be concise summaries.');
            }

            if (preg_match('/\bBearer\s+\S+|\b(?:sk|key)-[A-Za-z0-9_-]{12,}/i', $value) === 1) {
                throw new InvalidArgumentException('AI configuration audit changes contain secret-like content.');
            }

            return;
        }

        if (! is_null($value) && ! is_bool($value) && ! is_int($value) && ! is_float($value)) {
            throw new InvalidArgumentException('AI configuration audit changes contain an unsupported value.');
        }
    }

    /**
     * @param  array<array-key, mixed>|null  $value
     */
    public static function encodeJson(?array $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('AI configuration metadata must be valid JSON.', previous: $exception);
        }
    }
}
