<?php

namespace Tests\Unit;

use App\Support\WhatsApp;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    public function test_builds_an_encoded_click_to_chat_url(): void
    {
        $url = WhatsApp::url('+52 (55) 1234-5678', 'Hola, me interesa la terraza & jardín.');

        $this->assertSame(
            'https://wa.me/525512345678?text='.rawurlencode('Hola, me interesa la terraza & jardín.'),
            $url,
        );
    }

    public function test_accepts_an_international_prefix_and_omits_the_message_when_empty(): void
    {
        $this->assertSame('https://wa.me/5215555555555', WhatsApp::url('0052 1 555 555 5555'));
    }

    public function test_rejects_malformed_phone_numbers(): void
    {
        $this->assertNull(WhatsApp::url(null));
        $this->assertNull(WhatsApp::url(''));
        $this->assertNull(WhatsApp::url('abc'));
        $this->assertNull(WhatsApp::url('12345'));
        $this->assertNull(WhatsApp::url('1234567890123456'));
    }
}
