<?php

namespace Tests\Unit;

use App\Models\MinorRegistrationRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use PHPUnit\Framework\TestCase;

class MinorRegistrationPayloadCastTest extends TestCase
{
    protected function tearDown(): void
    {
        Model::encryptUsing(null);

        parent::tearDown();
    }

    public function test_payload_is_encrypted_and_decoded_as_an_array(): void
    {
        Model::encryptUsing(new Encrypter(str_repeat('a', 32), 'AES-256-CBC'));
        $payload = ['first_name' => 'Private', 'birthdate' => '2012-01-01'];
        $request = new MinorRegistrationRequest;
        $request->minor_payload = $payload;

        $this->assertStringNotContainsString('Private', $request->getAttributes()['minor_payload']);
        $this->assertSame($payload, $request->minor_payload);
    }
}
