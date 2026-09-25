<?php

namespace UCrazy\AplikasiPencatatBarangCrud\App;

class View
{
    public static function render(string $view, array $model = []): void
    {
        require __DIR__ . '/../View/layout/header.php';
        require __DIR__ . '/../View/' . $view . '.php';
        require __DIR__ . '/../View/layout/footer.php';
    }

    public static function redirect(string $url): void
    {
        header("Location: $url");
        if ((getenv("APP_ENV") ?: ($_ENV['APP_ENV'] ?? 'prod')) != "test") {
            exit();
        }
    }
}
