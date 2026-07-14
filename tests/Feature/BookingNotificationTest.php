<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\VendorAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_creation_queues_notification()
    {
        Queue::fake();

        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test']);
        $user = User::factory()->create();
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'ACTIVE',
            'booking_capacity_mode' => VendorAccount::BOOKING_SINGLE_RESOURCE,
        ]);

        $slot = \App\Models\VendorSlot::create([
            'vendor_account_id' => $vendor->id,
            'slug' => 'test-slot',
            'label' => 'Test Slot',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'vendor_account_id' => $vendor->id,
            'vendor_slot_id' => $slot->id,
            'prestashop_order_id' => '12345',
            'prestashop_order_line_id' => '1',
            'start_time' => now(),
            'end_time' => now()->addHours(2),
            'status' => Booking::STATUS_CONFIRMED,
            'event_date' => now()->format('Y-m-d'),
        ]);

        Queue::assertPushed(\App\Jobs\SendVendorNewBookingEmail::class);
    }
}
