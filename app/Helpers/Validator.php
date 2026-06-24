<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Validator — Input validation
 *
 * Usage:
 *   $validator = Validator::make($request->all(), [
 *       'email'    => 'required|email',
 *       'password' => 'required|min:8',
 *       'role'     => 'required|in:admin,editor,viewer',
 *   ]);
 *
 *   if ($validator->fails()) {
 *       // Handle errors
 *       $errors = $validator->errors(); // ['email' => 'Email is required', ...]
 *   }
 *
 *   $cleanData = $validator->validated(); // Only fields that passed
 *
 * Supported rules (pipe-separated):
 *   required          - Field must be present and not empty
 *   email             - Must be a valid email address
 *   min:n             - String must be at least n characters
 *   max:n             - String must not exceed n characters
 *   url               - Must be a valid URL
 *   in:a,b,c          - Value must be one of the listed options
 *   numeric           - Must be a number
 *   integer           - Must be an integer
 *   confirmed         - Field must match 'field_confirmation' value
 *   file              - Must be a successfully uploaded file
 *   mimes:jpg,png     - Uploaded file must have one of these extensions
 *   max_size:n        - Uploaded file must not exceed n bytes
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errorMessages = [];

    private function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
    }

    /**
     * Create a new Validator instance and run validation immediately.
     */
    public static function make(array $data, array $rules): static
    {
        $validator = new static($data, $rules);
        $validator->validate();
        return $validator;
    }

    public function passes(): bool
    {
        return empty($this->errorMessages);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get all validation errors, keyed by field name.
     * Returns the FIRST error per field.
     */
    public function errors(): array
    {
        return $this->errorMessages;
    }

    /**
     * Get only the input fields that were listed in the rules and passed validation.
     * Useful for safe mass assignment — never pass $request->all() directly to create().
     */
    public function validated(): array
    {
        $result = [];
        foreach (array_keys($this->rules) as $field) {
            if (!isset($this->errorMessages[$field]) && array_key_exists($field, $this->data)) {
                $result[$field] = $this->data[$field];
            }
        }
        return $result;
    }

    // ─── Internal ─────────────────────────────────────────────────────────

    /**
     * Run all rules for all fields.
     */
    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            // Rules are pipe-separated: 'required|email|max:150'
            $rules = explode('|', $ruleString);

            foreach ($rules as $rule) {
                // Split rule name from its argument: 'min:8' => ['min', '8']
                $parts     = explode(':', $rule, 2);
                $ruleName  = trim($parts[0]);
                $ruleParam = isset($parts[1]) ? trim($parts[1]) : null;

                $error = $this->applyRule($field, $ruleName, $ruleParam);

                if ($error !== null) {
                    // Store only the first error per field, then stop checking that field
                    $this->errorMessages[$field] = $error;
                    break;
                }
            }
        }
    }

    /**
     * Apply a single rule to a field and return an error message (or null if it passes).
     */
    private function applyRule(string $field, string $rule, ?string $param): ?string
    {
        $value     = $this->data[$field] ?? null;
        $label     = ucfirst(str_replace('_', ' ', $field)); // 'first_name' => 'First name'

        return match ($rule) {

            'required' => (
                $value === null || $value === '' || $value === []
                    ? "{$label} is required."
                    : null
            ),

            'email' => (
                $value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)
                    ? "{$label} must be a valid email address."
                    : null
            ),

            'url' => (
                $value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)
                    ? "{$label} must be a valid URL."
                    : null
            ),

            'numeric' => (
                $value !== null && $value !== '' && !is_numeric($value)
                    ? "{$label} must be a number."
                    : null
            ),

            'integer' => (
                $value !== null && $value !== '' && !ctype_digit((string)$value)
                    ? "{$label} must be a whole number."
                    : null
            ),

            'min' => (
                $param !== null && $value !== null && mb_strlen((string)$value, 'UTF-8') < (int)$param
                    ? "{$label} must be at least {$param} characters."
                    : null
            ),

            'max' => (
                $param !== null && $value !== null && mb_strlen((string)$value, 'UTF-8') > (int)$param
                    ? "{$label} must not exceed {$param} characters."
                    : null
            ),

            'in' => (
                $param !== null && $value !== null && $value !== '' &&
                !in_array($value, explode(',', $param), true)
                    ? "{$label} must be one of: " . implode(', ', explode(',', $param)) . "."
                    : null
            ),

            'confirmed' => (
                $value !== ($this->data[$field . '_confirmation'] ?? null)
                    ? "{$label} confirmation does not match."
                    : null
            ),

            'file' => (
                !isset($this->data[$field]) || ($this->data[$field]['error'] ?? 4) !== UPLOAD_ERR_OK
                    ? "{$label} must be a valid uploaded file."
                    : null
            ),

            'mimes' => (
                $param !== null &&
                isset($this->data[$field]) &&
                ($this->data[$field]['error'] ?? 4) === UPLOAD_ERR_OK
                    ? $this->validateMimes($field, $param, $label)
                    : null
            ),

            'max_size' => (
                $param !== null &&
                isset($this->data[$field]) &&
                ($this->data[$field]['size'] ?? 0) > (int)$param
                    ? "{$label} must not exceed " . number_format((int)$param / 1024 / 1024, 1) . "MB."
                    : null
            ),

            default => null, // Unknown rules are silently ignored
        };
    }

    /**
     * Validate that an uploaded file has an allowed extension.
     */
    private function validateMimes(string $field, string $param, string $label): ?string
    {
        $file           = $this->data[$field];
        $allowedMimes   = explode(',', $param);

        // Get the actual file extension from the original filename
        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedMimes, true)) {
            return "{$label} must be a file of type: " . implode(', ', $allowedMimes) . ".";
        }

        return null;
    }
}
