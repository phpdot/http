<?php

declare(strict_types=1);

/**
 * IpUtils
 *
 * Static utility class for IP address operations including CIDR matching
 * and private address detection.
 *
 * @author Omar Hamdan <omar@phpdot.com>
 * @license MIT
 */

namespace PHPdot\Http;

final class IpUtils
{
    /** @var list<string> */
    private const array PRIVATE_RANGES = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',
        '::1/128',
        'fc00::/7',
    ];

    /**
     * Check if an IP address falls within a CIDR range.
     *
     * @param string $ip The IP address to check
     * @param string $cidr The CIDR notation range (e.g. "192.168.1.0/24" or bare IP "192.168.1.1")
     *
     * @return bool True if the IP is within the range
     */
    public static function inRange(string $ip, string $cidr): bool
    {
        $packedIp = inet_pton($ip);

        if ($packedIp === false) {
            return false;
        }

        if (str_contains($cidr, '/')) {
            [$network, $prefixStr] = explode('/', $cidr, 2);
            $prefix = (int) $prefixStr;
        } else {
            $network = $cidr;
            $prefix = self::isIPv6($cidr) ? 128 : 32;
        }

        $packedNetwork = inet_pton($network);

        if ($packedNetwork === false) {
            return false;
        }

        if (strlen($packedIp) !== strlen($packedNetwork)) {
            return false;
        }

        $byteLength = strlen($packedIp);
        $totalBits = $byteLength * 8;

        if ($prefix < 0 || $prefix > $totalBits) {
            return false;
        }

        $mask = self::buildNetmask($prefix, $byteLength);

        return ($packedIp & $mask) === ($packedNetwork & $mask);
    }

    /**
     * Check if an IP address matches any of the given CIDR ranges.
     *
     * @param string $ip The IP address to check
     * @param list<string> $ranges Array of CIDR notation ranges
     *
     * @return bool True if the IP matches any range
     */
    public static function matches(string $ip, array $ranges): bool
    {
        foreach ($ranges as $cidr) {
            if (self::inRange($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address is a private/reserved address.
     *
     * Checks against RFC 1918 (IPv4), RFC 4193 (IPv6), and loopback ranges.
     *
     * @param string $ip The IP address to check
     *
     * @return bool True if the IP is private
     */
    public static function isPrivate(string $ip): bool
    {
        return self::matches($ip, self::PRIVATE_RANGES);
    }

    /**
     * Check if a string is a valid IPv4 address.
     *
     * @param string $ip The string to check
     *
     * @return bool True if the string is a valid IPv4 address
     */
    public static function isIPv4(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Check if a string is a valid IPv6 address.
     *
     * @param string $ip The string to check
     *
     * @return bool True if the string is a valid IPv6 address
     */
    public static function isIPv6(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Build a binary netmask from a prefix length.
     *
     * @param int $prefix The prefix length in bits
     * @param int $byteLength The total byte length (4 for IPv4, 16 for IPv6)
     *
     * @return string The binary netmask
     */
    private static function buildNetmask(int $prefix, int $byteLength): string
    {
        $mask = str_repeat("\xff", intdiv($prefix, 8));

        $remainder = $prefix % 8;

        if ($remainder > 0) {
            $mask .= chr(0xff << (8 - $remainder) & 0xff);
        }

        $mask = str_pad($mask, $byteLength, "\x00");

        return $mask;
    }
}
