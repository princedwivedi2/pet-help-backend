<?php

namespace App\Contracts;

interface OtpCodeGenerator
{
    public function generate(): string;
}