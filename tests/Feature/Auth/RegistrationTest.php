<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
        $eventType = \App\Models\EventType::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'privacy_accepted' => 'on',
            'contract_accepted' => 'on',
            'account_type' => 'COMPANY',
            'company_name' => 'Test Company',
            'vat_number' => '12345678901',
            'booking_capacity_mode' => 'single_resource',
            'category_id' => $category->id,
            'event_type_ids' => [$eventType->id],
            'legal_city' => 'Milan',
            'legal_postal_code' => '20100',
            'legal_address_line1' => 'Via Test 1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
