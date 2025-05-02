<?php

declare(strict_types=1);

namespace Sui;

class Utils
{
    /**
     * @param string $address The address to format.
     * @return string The formatted address.
     */
    public static function formatAddress(string $address): string
    {
        if (strlen($address) <= 6) {
            return $address;
        }
        $offset = str_starts_with($address, '0x') ? 2 : 0;
        return '0x' . substr($address, $offset, 4) . Constants::ELLIPSIS . substr($address, -4);
    }

    /**
     * @param string $digest The digest to format.
     * @return string The formatted digest.
     */
    public static function formatDigest(string $digest): string
    {
        return substr($digest, 0, Constants::DIGEST_LENGTH) . Constants::ELLIPSIS;
    }

    /**
     * @param array<int>|string $input
     * @return string
     */
    public static function toBase58(array|string $input): string
    {
        if (is_string($input)) {
            $unpack = unpack('C*', $input);
            $input = array_values($unpack ? $unpack : []);
        }

        $base58Array = [];
        $hex = bin2hex(implode(array_map('chr', $input)));

        $value = '0';
        $hexLength = strlen($hex);
        for ($i = 0; $i < $hexLength; $i++) {
            $value = bcadd(bcmul($value, '16'), base_convert($hex[$i], 16, 10));
        }

        while (bccomp($value, '0') > 0) {
            $remainder = bcmod($value, '58');
            $value = bcdiv($value, '58', 0);
            $base58Array[] = Constants::BASE58_ALPHABET[intval($remainder)];
        }

        foreach ($input as $byte) {
            if (0 !== $byte) {
                break;
            }
            $base58Array[] = Constants::BASE58_ALPHABET[0];
        }

        return implode('', array_reverse($base58Array));
    }


    /**
     * @param string $input
     * @return array<int>
     */
    public static function fromBase58(string $input): array
    {
        $value = '0';

        for ($i = 0; $i < strlen($input); $i++) {
            $value = bcadd(bcmul($value, '58'), (string)strpos(Constants::BASE58_ALPHABET, $input[$i]));
        }

        // Decimal to hexadecimal
        $hex = '';
        while (bccomp($value, '0') > 0) {
            $remainder = bcmod($value, '16');
            $hex = dechex((int)$remainder) . $hex;
            $value = bcdiv($value, '16', 0);
        }

        // Pad hex if odd length
        if (0 !== strlen($hex) % 2) {
            $hex = '0' . $hex;
        }

        // Count leading '1's (which represent 0x00 bytes)
        $leadingOnes = 0;
        for ($i = 0; $i < strlen($input) && $input[$i] === Constants::BASE58_ALPHABET[0]; $i++) {
            $leadingOnes++;
        }

        $result = array_fill(0, $leadingOnes, 0); // Add 0x00 bytes for each leading '1'

        $unpack = unpack('C*', hex2bin($hex) ?: '');
        $decoded = array_values($unpack ?: []);

        return array_merge($result, $decoded);
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isValidTransactionDigest(string $value): bool
    {
        try {
            $buffer = self::fromBase58($value);
            return Constants::TX_DIGEST_LENGTH === count($buffer);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isHex(string $value): bool
    {
        return 1 === preg_match('/^(0x|0X)?[a-fA-F0-9]+$/', $value) && 0 === strlen($value) % 2;
    }

    /**
     * @param string $value
     * @return int|float
     */
    public static function getHexByteLength(string $value): int|float
    {
        return preg_match('/^(0x|0X)/', $value) ? (strlen($value) - 2) / 2 : strlen($value) / 2;
    }

    /**
     * @param string $value
     * @return string
     */
    public static function prepareSuiAddress(string $value): string
    {
        return str_replace('0x', '', self::normalizeSuiAddress($value));
    }

    /**
     * @param string $value
     * @param bool $forceAdd0x
     * @return string
     */
    public static function normalizeSuiAddress(string $value, bool $forceAdd0x = false): string
    {
        $address = strtolower($value);
        if (!$forceAdd0x && str_starts_with($address, '0x')) {
            $address = substr($address, 2);
        }
        return '0x' . str_pad($address, Constants::SUI_ADDRESS_LENGTH * 2, '0');
    }

    /**
     * @param string $value
     * @param bool $forceAdd0x
     * @return string
     */
    public static function normalizeSuiObjectId(string $value, bool $forceAdd0x = false): string
    {
        return self::normalizeSuiAddress($value, $forceAdd0x);
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isValidSuiAddress(string $value): bool
    {
        return self::isHex($value) && Constants::SUI_ADDRESS_LENGTH === self::getHexByteLength($value);
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isValidSuiObjectId(string $value): bool
    {
        return self::isValidSuiAddress($value);
    }

    /**
     * @param string $value
     * @return object{address:string,module:string,name:string,typeParams:array<mixed>}
     */
    public static function parseStructTag(string $value): object
    {
        [$address, $module] = explode('::', $value);

        $isMvrPackage = self::isValidNamedPackage($address);

        $rest = substr($value, strlen($address) + strlen($module) + 4);
        $name = str_contains($rest, '<') ? substr($rest, 0, strpos($rest, '<') ?: 0) : $rest;
        $typeParams = str_contains($rest, '<')
            ? array_map(
                fn($typeParam) => self::parseTypeTag(trim($typeParam)),
                self::splitGenericParameters(
                    substr($rest, strpos($rest, '<') + 1, strrpos($rest, '>') - strpos($rest, '<') - 1)
                )
            )
            : [];

        return (object)[
            'address' => $isMvrPackage ? $address : self::normalizeSuiAddress($address),
            'module' => $module,
            'name' => $name,
            'typeParams' => $typeParams,
        ];
    }

    /**
     * @param string $value
     * @return object
     */
    public static function parseTypeTag(string $value): object
    {
        if (str_contains($value, '::')) {
            return self::parseStructTag($value);
        }
        return (object)[
            'address' => self::normalizeSuiAddress($value),
            'module' => '',
            'name' => $value,
        ];
    }

    /**
     * @param string $value
     * @return string
     */
    public static function normalizeStructTag(string $value): string
    {
        $structTag = self::parseStructTag($value);
        $formattedTypeParams = '';

        if (!empty($structTag->typeParams)) {
            $formattedTypeParams = '<' . implode(',', array_map(
                fn($typeParam) => is_string($typeParam) ? self::normalizeStructTag($typeParam) : $typeParam,
                $structTag->typeParams
            )) . '>';
        }

        return sprintf('%s::%s::%s%s', $structTag->address, $structTag->module, $structTag->name, $formattedTypeParams);
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isValidSuiNSName(string $value): bool
    {
        if (strlen($value) > Constants::MAX_SUI_NS_NAME_LENGTH) {
            return false;
        }
        if (str_contains($value, '@')) {
            return (bool) preg_match(Constants::SUI_NS_NAME_REGEX, $value);
        }
        return (bool)preg_match(Constants::SUI_NS_DOMAIN_REGEX, $value);
    }


    /**
     * @param string $name
     * @param string $format
     * @return string
     */
    public static function normalizeSuiNSName(string $name, string $format = 'at'): string
    {
        $lowerCase = strtolower($name);
        $parts = [];
        if (str_contains($lowerCase, '@')) {
            if (!preg_match(Constants::SUI_NS_NAME_REGEX, $lowerCase)) {
                throw new \Exception(sprintf('Invalid SuiNS name %s', $name));
            }
            [$labels, $domain] = explode('@', $lowerCase);
            $parts = [...($labels ? explode('.', $labels) : []), $domain];
        } else {
            if (!preg_match(Constants::SUI_NS_DOMAIN_REGEX, $lowerCase)) {
                throw new \Exception(sprintf('Invalid SuiNS name %s', $name));
            }
            $parts = explode('.', $lowerCase);
            array_pop($parts);
        }
        if ('dot' === $format) {
            return sprintf('%s.sui', implode('.', $parts));
        }
        return sprintf('%s@%s', implode('.', array_slice($parts, 0, -1)), end($parts));
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidNamedPackage(string $name): bool
    {
        $parts = explode(Constants::NAME_SEPARATOR, $name);
        if (count($parts) < 2 || count($parts) > 3) {
            return false;
        }
        [$org, $app, $version] = $parts;
        if ($version && !preg_match(Constants::VERSION_REGEX, $version)) {
            return false;
        }
        if (!self::isValidSuiNSName($org)) {
            return false;
        }
        return preg_match(Constants::NAME_PATTERN, $app) && strlen($app) < Constants::MAX_APP_SIZE;
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isValidNamedType(string $type): bool
    {
        $splitType = preg_split('/::|<|>|,/', $type);
        if (!is_array($splitType)) {
            return false;
        }
        foreach ($splitType as $t) {
            if (str_contains($t, Constants::NAME_SEPARATOR) && !self::isValidNamedPackage($t)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $value
     * @return array<int<0,max>,float|int>
     */
    public static function fromHex(string $value): array
    {
        $normalized = str_starts_with($value, '0x') ? substr($value, 2) : $value;
        if (0 !== strlen($normalized) % 2) {
            $normalized = '0' . $normalized;
        }
        if (!preg_match('/^[0-9a-fA-F]+$/', $normalized)) {
            throw new \InvalidArgumentException('Invalid hex string');
        }
        $intArr = [];
        for ($i = 0; $i < strlen($normalized); $i += 2) {
            $intArr[] = hexdec(substr($normalized, $i, 2));
        }
        return $intArr;
    }

    /**
     * @param array<int>|string $bytes
     * @return string
     */
    public static function toHex(array|string $bytes): string
    {
        if (is_string($bytes)) {
            $unpack = unpack('C*', $bytes);
            $bytes = array_values($unpack ? $unpack : []);
        }
        $hex = '';
        foreach ($bytes as $byte) {
            $hex .= str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
        }
        return $hex;
    }

    /**
     * @param array<int>|string $bytes
     * @return string
     */
    public static function toBase64(array|string $bytes): string
    {
        if (is_string($bytes)) {
            $unpack = unpack('C*', $bytes);
            $bytes = array_values($unpack ? $unpack : []);
        }
        return base64_encode(implode(array_map('chr', $bytes)));
    }

    /**
     * @param string $base64
     * @return array<int>
     */
    public static function fromBase64(string $base64): array
    {
        $unpack = unpack('C*', base64_decode($base64) ?: '');
        return array_values($unpack ? $unpack : []);
    }

    /**
     * Helper utility: write number as an ULEB array.
     * Original code is taken from: https://www.npmjs.com/package/uleb128 (no longer exists)
     *
     * @param int $num The number to encode
     * @return array<int> The ULEB encoded array
     */
    public static function ulebEncode(int $num): array
    {
        $arr = [];
        $len = 0;

        if (0 === $num) {
            return [0];
        }

        while ($num > 0) {
            $arr[$len] = $num & 0x7f;
            if ($num >>= 7) {
                $arr[$len] |= 0x80;
            }
            $len += 1;
        }

        return $arr;
    }

    /**
     * Helper utility: decode ULEB as an array of numbers.
     * Original code is taken from: https://www.npmjs.com/package/uleb128 (no longer exists)
     *
     * @param array<int>|array<int, int> $arr The ULEB encoded array
     * @return array{value: int, length: int} The decoded value and length
     */
    public static function ulebDecode(array $arr): array
    {
        $total = 0;
        $shift = 0;
        $len = 0;

        while (true) {
            $byte = $arr[$len];
            $len += 1;
            $total |= ($byte & 0x7f) << $shift;
            if (0 === ($byte & 0x80)) {
                break;
            }
            $shift += 7;
        }

        return [
            'value' => $total,
            'length' => $len,
        ];
    }

    /**
     * Encode data with either 'hex', 'base58', or 'base64'.
     *
     * @param array<int> $data Data to encode
     * @param string $encoding Encoding to use: base58, base64, or hex
     * @return string Encoded value
     * @throws \Exception If unsupported encoding is provided
     */
    public static function encodeStr(array $data, string $encoding): string
    {
        return match ($encoding) {
            'base58' => self::toBase58($data),
            'base64' => base64_encode(implode(array_map('chr', $data))),
            'hex' => self::toHex($data),
            default => throw new \Exception('Unsupported encoding, supported values are: base58, base64, hex'),
        };
    }

    /**
     * Decode either 'base58', 'base64', or 'hex' data.
     *
     * @param string $data Data to decode
     * @param string $encoding Encoding to use: base58, base64, or hex
     * @return array<int> Decoded value
     * @throws \Exception If unsupported encoding is provided
     */
    public static function decodeStr(string $data, string $encoding): array
    {
        return match ($encoding) {
            'base58' => self::fromBase58($data),
            'base64' => array_values(unpack('C*', base64_decode($data) ?: '') ?: []),
            'hex' => self::fromHex($data),
            default => throw new \Exception('Unsupported encoding, supported values are: base58, base64, hex'),
        };
    }

    /**
     * Split a string containing generic parameters.
     *
     * @param string $str String to split
     * @param array{string, string} $genericSeparators Separators for generic parameters, defaults to ['<', '>']
     * @return array<string> Array of split tokens
     */
    public static function splitGenericParameters(
        string $str,
        array $genericSeparators = ['<', '>']
    ): array {
        [$left, $right] = $genericSeparators;
        $tokens = [];
        $word = '';
        $nestedAngleBrackets = 0;

        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            if ($left === $char) {
                $nestedAngleBrackets++;
            }
            if ($right === $char) {
                $nestedAngleBrackets--;
            }
            if (0 === $nestedAngleBrackets && ',' === $char) {
                $tokens[] = trim($word);
                $word = '';
                continue;
            }
            $word .= $char;
        }

        $tokens[] = trim($word);

        return $tokens;
    }

    /**
     * @param string $type
     * @param array<int> $data
     * @return array<int>
     */
    public static function hashTypedData(string $type, array $data): array
    {
        if (!extension_loaded('sodium')) {
            throw new \RuntimeException('The sodium extension is required for Blake2b hashing');
        }

        // Convert type tag to bytes
        $typeTagBytes = array_map(
            fn($char) => ord($char),
            str_split($type . '::')
        );

        // Combine type tag and data
        $dataWithTag = array_merge($typeTagBytes, $data);

        // Convert array to binary string
        $binaryData = implode(array_map('chr', $dataWithTag));

        // Hash using Blake2b with 32 byte output length
        $hash = sodium_crypto_generichash($binaryData, '', 32);

        // Convert hash back to array of integers
        return array_values(unpack('C*', $hash) ?: []);
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isBase64(string $value): bool
    {
        return 1 === preg_match('/^[A-Za-z0-9+\/=]+$/', $value);
    }
}
