<?php

use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\UrlGenerator;

if ( ! function_exists('convertTimeToUSERzone')) {
    function convertTimeToUSERzone($str, $userTimezone, $format = 'Y-m-d H:i:s') {
        if (empty($str)) {
            return '';
        }
        $new_str = new DateTime($str, new DateTimeZone('UTC'));
        $new_str->setTimeZone(new DateTimeZone($userTimezone));

        return $new_str->format($format);
    }
}
if ( ! function_exists('convertTimeToUTCzone')) {
    function convertTimeToUTCzone($str, $userTimezone, $format = 'Y-m-d H:i:s') {
        $new_str = new DateTime($str, new DateTimeZone($userTimezone));
        $new_str->setTimeZone(new DateTimeZone('UTC'));

        return $new_str->format($format);
    }
}
if ( ! function_exists('urlGenerator')) {
    /**
     * @return UrlGenerator
     */
    function urlGenerator() {
        return new UrlGenerator(app());
    }
}
if ( ! function_exists('asset')) {
    /**
     * @param      $path
     * @param bool $secured
     *
     * @return string
     */
    function asset($path, $secured = true) {
        return urlGenerator()->asset($path, $secured);
    }
}
if ( ! function_exists('responseMessage')) {
    /**
     * @param        $code
     * @param        $success
     * @param string $error
     * @param null   $data
     *
     * @return JsonResponse
     */
    function responseMessage($code, $success, $error = '', $data = null) {
        return response()->json(['code' => $code, 'success' => $success, 'error' => $error, 'data' => $data]);
    }
}
