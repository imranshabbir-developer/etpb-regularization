<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The portal opens in Urdu and can be switched to English from the sign-in
 * page or the top bar. The choice lives in the session.
 */
class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_portal_opens_in_urdu_by_default(): void
    {
        config(['app.locale' => 'ur']);

        $this->get('/login')
            ->assertOk()
            ->assertSee('<html lang="ur" dir="rtl">', false)
            ->assertSee('لاگ اِن');
    }

    public function test_the_visitor_can_switch_to_english_and_back(): void
    {
        config(['app.locale' => 'ur']);

        $this->get('/language/en')->assertRedirect();
        $this->get('/login')->assertSee('<html lang="en" dir="ltr">', false)->assertSee('Sign in');

        $this->get('/language/ur')->assertRedirect();
        $this->get('/login')->assertSee('<html lang="ur" dir="rtl">', false);
    }

    public function test_an_unsupported_language_is_not_found(): void
    {
        $this->get('/language/fr')->assertNotFound();
    }

    public function test_the_language_choice_survives_signing_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->post('/logout')
            ->assertRedirect();

        $this->assertSame('en', session('locale'));
    }

    public function test_error_pages_follow_the_chosen_language(): void
    {
        $this->app->setLocale('ur');
        $this->get('/no-such-page')->assertNotFound()->assertSee('صفحہ نہیں ملا');

        $this->app->setLocale('en');
        $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found');
    }

    public function test_every_urdu_translation_keeps_its_placeholders(): void
    {
        $strings = json_decode(file_get_contents(lang_path('ur.json')), true, flags: JSON_THROW_ON_ERROR);
        $broken = [];

        foreach ($strings as $english => $urdu) {
            preg_match_all('/:([a-z_]+)/i', $english, $m);
            foreach (array_unique($m[1]) as $name) {
                if (! preg_match('/:'.preg_quote($name, '/').'(?![a-z_])/i', $urdu)) {
                    $broken[] = "{$english}  (missing :{$name})";
                }
            }
        }

        $this->assertSame([], $broken, "Urdu strings missing placeholders:\n".implode("\n", $broken));
    }
}
