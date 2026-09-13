<?php
declare(strict_types=1);
namespace App\Services;

final class CrudValidation
{
    public static function validate(string $module, array $data, bool $editing = false): array
    {
        $errors = [];
        $text = static function (string $key, int $max, bool $required = true) use ($data, &$errors): void {
            $value = trim((string)($data[$key] ?? ''));
            if ($required && $value === '') {
                $errors[$key] = 'This field is required.';
            } elseif (preg_match_all('/./us', $value) > $max) {
                $errors[$key] = "Use $max characters or fewer.";
            }
        };
        if ($module !== 'budgets' && $module !== 'appearance') {
            $text('name', 120);
        }
        if ($module !== 'appearance' && !in_array($data['status'] ?? '', ['active', 'inactive'], true)) {
            $errors['status'] = 'Choose a valid status.';
        }
        if (in_array($module, ['categories', 'accounts'], true)) {
            $text('description', 16000, false);
        }
        if (in_array($module, ['categories', 'appearance'], true)) {
            if (!preg_match('/^[a-z][a-z0-9-]{0,79}$/D', (string)($data['icon'] ?? ''))) {
                $errors['icon'] = 'Enter a Feather icon name, such as tag or coffee.';
            }
            if (!preg_match('/^#[a-fA-F0-9]{6}$/D', (string)($data['color'] ?? ''))) {
                $errors['color'] = 'Choose a valid six-digit color.';
            }
            if ($module === 'appearance') $text('badge', 16, false);
        }
        if ($module === 'accounts') {
            if (!in_array($data['type'] ?? '', ['Cash', 'Bank', 'Card', 'Credit Card', 'Debit Card', 'UPI', 'Wallet'], true)) {
                $errors['type'] = 'Choose a valid account type.';
            }
            if (!$editing && (!is_numeric($data['opening_balance'] ?? '') || abs((float)$data['opening_balance']) > 9999999999999.99)) {
                $errors['opening_balance'] = 'Enter a valid opening balance.';
            }
        }
        if ($module === 'budgets') {
            foreach (['start_date', 'end_date'] as $key) {
                $value = (string)($data[$key] ?? '');
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                if (!$date || $date->format('Y-m-d') !== $value || $value < '1000-01-01') {
                    $errors[$key] = 'Enter a valid date.';
                }
            }
            if (!isset($errors['start_date']) && !isset($errors['end_date']) && ($data['end_date'] ?? '') < ($data['start_date'] ?? '')) {
                $errors['end_date'] = 'End date must be on or after start date.';
            }
            if (!is_numeric($data['budget_amount'] ?? '') || round((float)$data['budget_amount'], 2) <= 0 || (float)$data['budget_amount'] > 9999999999999.99) {
                $errors['budget_amount'] = 'Enter a budget amount greater than zero.';
            }
            if (!is_numeric($data['warning_threshold'] ?? '') || (float)$data['warning_threshold'] < 1 || (float)$data['warning_threshold'] > 100) {
                $errors['warning_threshold'] = 'Enter a threshold between 1 and 100.';
            }
        }
        if ($module === 'users') {
            $text('email', 190);
            if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
            if (!$editing) {
                $password = (string)($data['password'] ?? '');
                if (strlen($password) < 8 || strlen($password) > 72) $errors['password'] = 'Use a password between 8 and 72 bytes.';
                if ($password !== ($data['password_confirmation'] ?? '')) $errors['password_confirmation'] = 'Passwords do not match.';
            }
        }
        return $errors;
    }
}