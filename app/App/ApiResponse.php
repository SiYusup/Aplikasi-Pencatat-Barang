<?php

namespace UCrazy\AplikasiPencatatBarangCrud\App;

use UCrazy\AplikasiPencatatBarangCrud\Exception\ApiResponseSent;

class ApiResponse
{
    public static function isTest(): bool
    {
        return (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod')) == 'test';
    }

    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/json');
        }
        echo json_encode($data);
        if (self::isTest()) {
            throw new ApiResponseSent((array) $data, $code);
        }
        exit();
    }

    public static function success(mixed $data = null, string $message = 'OK'): void
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    public static function error(string $message, int $code = 400, mixed $errors = null): void
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        self::json($body, $code);
    }

    /** Ambil body JSON (untuk fetch application/json) digabung dengan $_POST */
    public static function input(): array
    {
        $data = $_POST;
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = array_merge($data, $decoded);
            }
        }
        // Method override untuk form HTML: ?_method=PUT / input _method
        return $data;
    }
}
