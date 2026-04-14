<?php

namespace App\Support\Permissions;

final class PermissionOverrideDelta
{
    /**
     * @param array<int, string> $inherited
     * @param array<int, string> $additions
     * @param array<int, string> $removals
     *
     * @return array<int, string>
     */
    public static function apply(array $inherited, array $additions, array $removals): array
    {
        $normalizedInherited = self::normalize($inherited);
        $normalizedAdditions = self::normalize($additions);
        $normalizedRemovals = self::normalize($removals);

        return array_values(array_diff(
            array_unique(array_merge($normalizedInherited, $normalizedAdditions)),
            $normalizedRemovals,
        ));
    }

    /**
     * @param array<int, string> $permissions
     *
     * @return array<int, string>
     */
    public static function normalize(array $permissions): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $permissions,
        ))));
    }
}
