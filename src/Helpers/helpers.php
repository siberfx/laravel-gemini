<?php

declare(strict_types=1);

if (! function_exists('safe_base64_encode')) {
    /**
     * URL-safe base64 encoding without padding.
     */
    function safe_base64_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
