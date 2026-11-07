<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_spanish_validation_messages_are_used(): void
    {
        app()->setLocale('es');

        $validator = Validator::make(
            ['email' => ''],
            ['email' => 'required|email'],
        );

        $this->assertSame(
            'El campo correo electrónico es obligatorio.',
            $validator->errors()->first('email'),
        );
    }
}
