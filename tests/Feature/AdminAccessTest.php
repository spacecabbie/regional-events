<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_open_the_event_form(): void
    {
        $response = $this->get('/admin/events/create');

        $response->assertRedirect();
        $this->assertStringContainsString('/admin/login', (string) $response->headers->get('Location'));
    }

    public function test_an_admin_can_open_the_event_form(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin/events/create')->assertOk();
    }
}
