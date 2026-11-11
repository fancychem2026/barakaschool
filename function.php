
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function requireAdmin(): void
{
    if (!isset($_SESSION["admin_id"])) {
        header("Location: login.php");
        exit;
    }
}

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function csrfToken(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function verifyCsrf(): void
{
    $submitted = $_POST["csrf_token"] ?? "";
    $saved = $_SESSION["csrf_token"] ?? "";

    if (
        !is_string($submitted) ||
        !is_string($saved) ||
        $saved === "" ||
        !hash_equals($saved, $submitted)
    ) {
        http_response_code(403);
        exit("Invalid request token. Please return and try again.");
    }
}
?>