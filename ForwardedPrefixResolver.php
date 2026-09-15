<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2026 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Router;

use Berlioz\Helpers\NetworkHelper;

/**
 * Class ForwardedPrefixResolver.
 *
 * Resolves trusted proxy mount paths without retaining request data.
 */
final readonly class ForwardedPrefixResolver
{
    public function __construct(
        private bool|string $header = false,
        private array $trustedProxies = [],
    ) {
    }

    /**
     * Resolve a normalized mount path, or null for a disabled, untrusted or invalid prefix.
     *
     * Rejects header lists, dot segments, controls, query/fragment delimiters,
     * backslashes, empty internal segments and ambiguous encoded separators/percent signs.
     *
     * @param array $serverParams Request server parameters
     *
     * @return string|null
     */
    public function resolve(array $serverParams): ?string
    {
        if (false === $this->header) {
            return null;
        }

        $header = true === $this->header ? 'X-Forwarded-Prefix' : $this->header;
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        $value = $serverParams[$serverKey] ?? null;

        if (!is_string($value) || '' === ($prefix = trim($value, '/'))) {
            return null;
        }

        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? null;

        if (
            !is_string($remoteAddr)
            || [] === $this->trustedProxies
            || !NetworkHelper::isTrustedProxy(trim($remoteAddr), $this->trustedProxies)
        ) {
            return null;
        }

        if (
            1 !== preg_match("#^(?:[a-zA-Z0-9/._~!$&'()*+;=:@-]|%[a-fA-F0-9]{2})+$#D", $prefix)
            || str_contains($prefix, '//')
            || 1 === preg_match('/%(?:2f|5c|25)/i', $prefix)
        ) {
            return null;
        }

        $decoded = rawurldecode($prefix);

        if (
            false !== strpbrk($decoded, "\\?#,")
            || 1 === preg_match('/[\x00-\x1F\x7F]/', $decoded)
        ) {
            return null;
        }

        foreach (explode('/', $decoded) as $segment) {
            if ('.' === $segment || '..' === $segment) {
                return null;
            }
        }

        return '/' . $prefix;
    }
}
