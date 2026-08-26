<?php

namespace Tests\Unit\Helpers;

use App\Helpers\CryptoHelper;
use Illuminate\Contracts\Encryption\DecryptException;
use Tests\TestCase;

class CryptoHelperTest extends TestCase
{
    public function test_it_encrypts_and_decrypts_a_value(): void
    {
        $plainText = '+91 98765 43210';

        $cipherText = CryptoHelper::encrypt($plainText);

        $this->assertNotSame($plainText, $cipherText);
        $this->assertSame($plainText, CryptoHelper::decrypt($cipherText));
    }

    public function test_it_preserves_null_values(): void
    {
        $this->assertNull(CryptoHelper::encrypt(null));
        $this->assertNull(CryptoHelper::decrypt(null));
    }

    public function test_it_rejects_tampered_ciphertext(): void
    {
        $this->expectException(DecryptException::class);

        CryptoHelper::decrypt('tampered-value');
    }
}
