<?php
namespace Utils;
/**
 * 개인정보 마스킹 처리를 위한 유틸리티 클래스
 */
class MaskingUtil {
    /**
     * 문자열의 일부를 마스킹 처리
     * @param string $str 원본 문자열
     * @param int $start 시작 위치
     * @param int|null $length 마스킹할 길이 (null이면 끝까지)
     * @param string $maskChar 마스킹 문자
     * @return string
     */
    private static function maskSubstring($str, $start, $length = null, $maskChar = '*') {
        $strLength = mb_strlen($str, 'UTF-8');
        if ($length === null) {
            $length = $strLength - $start;
        }

        if ($start >= $strLength) {
            return $str;
        }

        return mb_substr($str, 0, $start, 'UTF-8') .
            str_repeat($maskChar, $length) .
            ($start + $length < $strLength ? mb_substr($str, $start + $length, null, 'UTF-8') : '');
    }

    /**
     * 이름 마스킹 처리
     * @param string $name
     * @return string
     */
    public static function name($name) {
        $length = mb_strlen($name, 'UTF-8');
        return $length > 1 ? self::maskSubstring($name, 1) : $name;
    }

    /**
     * 이메일 마스킹 처리
     * @param string $email
     * @return string
     */
    public static function email($email) {
        if (!str_contains($email, '@')) {
            return $email;
        }

        [$id, $domain] = explode('@', $email, 2);
        $idLength = strlen($id);

        return $idLength > 2
            ? substr($id, 0, 2) . str_repeat('*', $idLength - 2) . '@' . $domain
            : $email;
    }

    /**
     * 전화번호 마스킹 처리
     * @param string $phone
     * @return string
     */
    public static function phone($phone) {
        $numbers = preg_replace('/[^0-9]/', '', $phone);
        $length = strlen($numbers);

        if ($length < 10) return $phone;

        return substr($numbers, 0, 3) . '-' .
            str_repeat('*', $length - 7) . '-' .
            substr($numbers, -4);
    }
}
