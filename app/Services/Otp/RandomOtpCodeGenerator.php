<?php

namespace App\Services\Otp;

use App\Contracts\OtpCodeGenerator;

class RandomOtpCodeGenerator implements OtpCodeGenerator
{
    public function generate(): string
    {
        return (string) random_int(100000, 999999);
    }
}