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
        '127.0.0.0/8',     // RFC1700 loopback
        '10.0.0.0/8',      // RFC1918
        '172.16.0.0/12',   // RFC1918
        '192.168.0.0/16',  // RFC1918
        '169.254.0.0/16',  // RFC3927 link-local
        '0.0.0.0/8',       // RFC5735 unspecified
        '240.0.0.0/4',     // RFC1112 reserved
        '::1/128',         // IPv6 loopback
        'fc00::/7',        // RFC4193 unique local
        'fe80::/10',       // IPv6 link-local
        '::ffff:0:0/96',   // IPv4-mapped IPv6
        '::/128',          // IPv6 unspecified
    ];

    /** @var array<string, bool> Memoized inRange() results */
    private static array $cache = [];

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
        $cacheKey = $ip . '|' . $cidr;

        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        return self::$cache[$cacheKey] = self::compute($ip, $cidr);
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
     * Checks against RFC 1918 (IPv4), RFC 4193 (IPv6), loopback ranges,
     * link-local ranges, IPv4-mapped IPv6, and unspecified addresses.
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
     * Clear the memoization cache. Use between unrelated request cycles
     * if the cache size becomes a concern (long-running workers).
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Compute the actual CIDR membership check (uncached).
     */
    private static function compute(string $ip, string $cidr): bool
    {
        $packedIp = inet_pton($ip);

        if ($packedIp === false) {
            return false;
        }

        if (str_contains($cidr, '/')) {
            [$network, $prefixStr] = explode('/', $cidr, 2);

            if ($prefixStr === '' || ctype_digit($prefixStr) === false) {
                // Empty / non-numeric prefix is not a valid CIDR. Without this
                // guard, "1.2.3.4/" would coerce to prefix=0 and match every IP.
                return false;
            }

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
     * Build a binary netmask from a prefix length.
     */
    private static function buildNetmask(int $prefix, int $byteLength): string
    {
        $mask = str_repeat("\xff", intdiv($prefix, 8));

        $remainder = $prefix % 8;

        if ($remainder > 0) {
            $mask .= chr(0xff << (8 - $remainder) & 0xff);
        }

        return str_pad($mask, $byteLength, "\x00");
    }
}
