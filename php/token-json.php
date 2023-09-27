<?php
$tokenzz = "XXX";
header('Content-Type: application/json; charset=utf-8');
$json = json_decode(file_get_contents('php://input'));

$user_token = $json->tokenzz ?? "";
if(empty($user_token) || ($user_token !== $tokenzz)){
    echo json_encode(['erro'=> 'autorização inválida']);
    exit;
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle): bool {
        if ( is_string($haystack) && is_string($needle) ) {
            return '' === $needle || false !== strpos($haystack, $needle);
        } else {
            return false;
        }
    }
}