<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_returns_the_same_singleton_row(): void
    {
        PlatformSetting::query()->delete();

        $first = PlatformSetting::current();
        $second = PlatformSetting::current();

        $this->assertSame(1, $first->id);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('platform_settings', 1);
    }
}
