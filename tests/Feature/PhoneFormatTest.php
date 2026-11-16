<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_kenyan_numbers_in_any_form_become_one(): void
    {
        foreach (['0712345678', '712345678', '254712345678', '+254 712 345678', '0712 345 678', '+254-712-345-678'] as $raw) {
            $this->assertSame('+254712345678', Phone::normal($raw), $raw);
        }
        $this->assertSame('+254110345678', Phone::normal('0110345678'));
        $this->assertSame('+255758556562', Phone::normal('+255758556562'));
        $this->assertSame('28026398', Phone::normal('28026398'));
        $this->assertNull(Phone::normal('  '));
    }

    public function test_a_saved_phone_is_stored_in_the_one_format(): void
    {
        $u = User::factory()->create(['phone' => '0722 596 985']);
        $this->assertSame('+254722596985', $u->fresh()->phone);
    }

    public function test_search_finds_the_number_however_it_is_typed(): void
    {
        User::factory()->create(['phone' => '0722596985']);
        foreach (['0722596985', '0722 596', '254722596', '+254 722 596 985', '596985'] as $q) {
            $this->assertSame(1, User::where('phone', 'like', Phone::like($q))->count(), $q);
        }
        $this->assertSame('%Ann%', Phone::like('Ann'));
    }
}
