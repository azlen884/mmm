<?php
/**
 * ApexSMM Validation & Rate Limiting Engine
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/logger.php';

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $message = ''): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val === '') {
            $this->errors[$field][] = $message ?: ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
        return $this;
    }

    public function email(string $field, string $message = ''): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?: 'Please enter a valid email address.';
        }
        return $this;
    }

    public function username(string $field, string $message = ''): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $val)) {
            $this->errors[$field][] = $message ?: 'Username must be 3-30 characters (letters, numbers, underscores).';
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $message = ''): self
    {
        $val = (string)($this->data[$field] ?? '');
        if (mb_strlen($val) < $min) {
            $this->errors[$field][] = $message ?: ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min} characters.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $message = ''): self
    {
        $val = (string)($this->data[$field] ?? '');
        if (mb_strlen($val) > $max) {
            $this->errors[$field][] = $message ?: ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$max} characters.";
        }
        return $this;
    }

    public function matches(string $field, string $matchField, string $message = ''): self
    {
        $val = (string)($this->data[$field] ?? '');
        $matchVal = (string)($this->data[$matchField] ?? '');
        if ($val !== $matchVal) {
            $this->errors[$field][] = $message ?: ucfirst(str_replace('_', ' ', $field)) . ' does not match ' . str_replace('_', ' ', $matchField) . '.';
        }
        return $this;
    }

    public function url(string $field, string $message = ''): self
    {
        $val = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && !preg_match('/^https?:\/\/[^\s\/$.?#].[^\s]*$/i', $val)) {
            $this->errors[$field][] = $message ?: 'Please provide a valid URL starting with http:// or https://';
        }
        return $this;
    }

    public function integer(string $field, int $min = 1, ?int $max = null, string $message = ''): self
    {
        $val = $this->data[$field] ?? null;
        if (!filter_var($val, FILTER_VALIDATE_INT) && $val !== 0 && $val !== '0') {
            $this->errors[$field][] = $message ?: ucfirst(str_replace('_', ' ', $field)) . ' must be a valid whole number.';
            return $this;
        }

        $intVal = (int)$val;
        if ($intVal < $min) {
            $this->errors[$field][] = $message ?: ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min}.";
        } elseif ($max !== null && $intVal > $max) {
            $this->errors[$field][] = $message ?: ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$max}.";
        }

        return $this;
    }

    public function numeric(string $field, float $min = 0.0, ?float $max = null, string $message = ''): self
    {
        $val = $this->data[$field] ?? null;
        if (!is_numeric($val)) {
            $this->errors[$field][] = $message ?: ucfirst(str_replace('_', ' ', $field)) . ' must be a valid amount.';
            return $this;
        }

        $numVal = (float)$val;
        if ($numVal < $min) {
            $this->errors[$field][] = $message ?: ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min}.";
        } elseif ($max !== null && $numVal > $max) {
            $this->errors[$field][] = $message ?: ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$max}.";
        }

        return $this;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }
        $firstField = reset($this->errors);
        return $firstField[0] ?? null;
    }
}

/**
 * Rate Limiting Utility
 * Protects login, registration, password changes, and user API calls.
 */
class RateLimiter
{
    public static function check(string $action, string $identifier, int $maxAttempts = 5, int $decaySeconds = 300): bool
    {
        $key = hash('sha256', $action . '_' . $identifier);
        
        try {
            $now = date('Y-m-d H:i:s');
            // Clean expired rate records
            Database::execute("DELETE FROM rate_limits WHERE reset_at < ?", [$now]);

            $record = Database::fetchOne("SELECT hits, reset_at FROM rate_limits WHERE rate_key = ?", [$key]);

            if ($record) {
                if ($record['hits'] >= $maxAttempts) {
                    return false; // Rate limit exceeded
                }
                Database::execute("UPDATE rate_limits SET hits = hits + 1 WHERE rate_key = ?", [$key]);
            } else {
                $resetAt = date('Y-m-d H:i:s', time() + $decaySeconds);
                Database::execute("INSERT INTO rate_limits (rate_key, hits, reset_at) VALUES (?, 1, ?)", [$key, $resetAt]);
            }
            return true;
        } catch (\Throwable $e) {
            Logger::error('RateLimiter error: ' . $e->getMessage());
            return true; // Fail open to not block user on DB glitch
        }
    }

    public static function clear(string $action, string $identifier): void
    {
        $key = hash('sha256', $action . '_' . $identifier);
        try {
            Database::execute("DELETE FROM rate_limits WHERE rate_key = ?", [$key]);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
