<?php

namespace App\Jobs;

use App\Mail\NuovaPrenotazioneVendor;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendVendorNewBookingEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $bookingId)
    {
        //
    }

    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $booking = Booking::with(['vendorAccount.user', 'offering', 'vendorSlot'])->find($this->bookingId);

        if (!$booking) {
            Log::warning('SendVendorNewBookingEmail terminated without sending: booking not found.', [
                'booking_id' => $this->bookingId,
            ]);
            return;
        }

        $email = $booking->vendorAccount?->notificationEmail();

        if (!$email) {
            Log::warning('SendVendorNewBookingEmail terminated without sending: no notification email found for vendor.', [
                'booking_id' => $this->bookingId,
                'vendor_account_id' => $booking->vendor_account_id,
            ]);
            return;
        }

        Mail::to($email)->send(new NuovaPrenotazioneVendor($booking));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendVendorNewBookingEmail failed definitively.', [
            'booking_id' => $this->bookingId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
