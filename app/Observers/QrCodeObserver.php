<?php

namespace App\Observers;

use App\Enums\QrCodeStatus;
use App\Models\QrCode;

class QrCodeObserver
{
    /**
     * Handle the QrCode "updating" event.
     * Manages primary auto-promotion when status changes or location is unlinked.
     */
    public function updating(QrCode $qrCode): void
    {
        $wasPrimary = $qrCode->getOriginal('is_primary');
        $oldLocationId = $qrCode->getOriginal('merchant_location_id');
        $oldStatus = $qrCode->getOriginal('status');

        // If being set as primary, ensure mutual exclusion
        if ($qrCode->is_primary && ! $wasPrimary && $qrCode->merchant_location_id) {
            QrCode::where('merchant_location_id', $qrCode->merchant_location_id)
                ->where('id', '!=', $qrCode->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        // If status changed away from Linked and was primary → auto-promote
        if ($wasPrimary && $qrCode->status !== QrCodeStatus::Linked && $oldStatus === QrCodeStatus::Linked) {
            $qrCode->is_primary = false;
            $this->autoPromoteNext($oldLocationId, $qrCode->id);
        }

        // If merchant_location_id cleared and was primary → auto-promote
        if ($wasPrimary && $qrCode->merchant_location_id === null && $oldLocationId !== null) {
            $qrCode->is_primary = false;
            $this->autoPromoteNext($oldLocationId, $qrCode->id);
        }

        // If location changed and was primary → auto-promote at old location, reset at new
        if ($wasPrimary && $qrCode->merchant_location_id !== $oldLocationId
            && $oldLocationId !== null && $qrCode->merchant_location_id !== null) {
            $qrCode->is_primary = false;
            $this->autoPromoteNext($oldLocationId, $qrCode->id);
        }

        // Cannot be primary if status is not Linked
        if ($qrCode->is_primary && $qrCode->status !== QrCodeStatus::Linked) {
            $qrCode->is_primary = false;
        }

        // Cannot be primary without a location
        if ($qrCode->is_primary && ! $qrCode->merchant_location_id) {
            $qrCode->is_primary = false;
        }
    }

    /**
     * Handle the QrCode "deleting" event.
     */
    public function deleting(QrCode $qrCode): void
    {
        if ($qrCode->is_primary && $qrCode->merchant_location_id) {
            $this->autoPromoteNext($qrCode->merchant_location_id, $qrCode->id);
        }
    }

    /**
     * Auto-promote the next oldest linked QR code at the given location.
     */
    private function autoPromoteNext(int $locationId, int $excludeId): void
    {
        $next = QrCode::where('merchant_location_id', $locationId)
            ->where('id', '!=', $excludeId)
            ->where('status', QrCodeStatus::Linked)
            ->orderBy('created_at', 'asc')
            ->first();

        if ($next) {
            // Direct update to avoid recursive observer calls
            QrCode::withoutEvents(fn () => $next->update(['is_primary' => true]));
        }
    }
}
