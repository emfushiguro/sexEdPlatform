<?php

namespace Tests\Unit\Support;

use App\Support\AccountEmail;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\TestCase;

class AccountEmailTest extends TestCase
{
    public function test_normalize_trims_and_lowercases_without_provider_canonicalization(): void
    {
        self::assertSame('person+tag@gmail.com', AccountEmail::normalize('  Person+Tag@Gmail.com  '));
        self::assertSame('per.son@gmail.com', AccountEmail::normalize('Per.Son@gmail.com'));
        self::assertNotSame(
            AccountEmail::normalize('person@gmail.com'),
            AccountEmail::normalize('person+tag@gmail.com'),
        );
    }

    public function test_fingerprint_is_stable_and_does_not_expose_the_email(): void
    {
        $fingerprint = AccountEmail::fingerprint('  Person@Example.com ');

        self::assertSame($fingerprint, AccountEmail::fingerprint('person@example.com'));
        self::assertSame(64, strlen($fingerprint));
        self::assertStringNotContainsString('person@example.com', $fingerprint);
    }

    public function test_duplicate_users_email_exception_is_detected_without_matching_other_errors(): void
    {
        $previous = new \PDOException('Duplicate entry', 1062);
        $previous->errorInfo = ['23000', 1062, 'Duplicate entry for key users_email_unique'];
        $duplicate = new QueryException('mysql', 'insert into users', [], $previous);

        $other = new QueryException('mysql', 'insert into users', [], new \PDOException('Connection lost', 2006));

        self::assertTrue(AccountEmail::isUsersEmailUniqueViolation($duplicate));
        self::assertFalse(AccountEmail::isUsersEmailUniqueViolation($other));
    }
}
