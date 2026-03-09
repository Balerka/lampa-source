<?php

namespace Tests\Feature;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_successful_response(): void
    {
        config()->set('session.driver', 'array');

        $this->get(route('home'))->assertOk();
    }

    public function test_add_page_requires_authenticated_session(): void
    {
        config()->set('session.driver', 'array');

        $this->get(route('lampa.add'))->assertRedirect(route('login'));
    }

    public function test_add_page_uses_logged_in_user_email(): void
    {
        config()->set('session.driver', 'array');

        $user = User::factory()->create([
            'email' => 'viewer@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('lampa.add'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('add')
                ->where('email', 'viewer@example.com')
                ->where('ttl', 300)
            );
    }
}
