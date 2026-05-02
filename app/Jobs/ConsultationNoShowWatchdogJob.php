<?php

namespace App\Jobs;

use App\Models\ConsultationSession;
use App\Services\ConsultationService;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Auto-refund watchdog for online consultations.
 *
 * Per spec ("Refund Policy Logic / Auto Refund Cases"):
 *   - vet no-show >10 min after match → auto refund
 *   - vet cancels                    → auto refund (handled at cancel time)
 *   - connection fail repeatedly     → auto refund (handled in reportConnectionFailure)
 *
 * Scheduled every minute (via routes/console.php). Scans matched-but-not-joined
 * sessions whose vet_no_show_check_at has passed. Idempotent — already-failed
 * sessions are filtered out by the scan.
 */
class ConsultationNoShowWatchdogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ConsultationService $consultationService, PaymentService $paymentService): void
    {
        $candidates = ConsultationSession::query()
            ->where('status', 'matched')
            ->whereNull('vet_joined_at')
            ->whereNotNull('vet_no_show_check_at')
            ->where('vet_no_show_check_at', '<=', now())
            ->limit(50)
            ->get();

        foreach ($candidates as $session) {
            try {
                $expired = $consultationService->expireIfVetNoShow($session);
                if (!$expired) {
                    continue;
                }

                // Auto-refund the held payment, if any.
                if ($session->payment_id) {
                    $payment = \App\Models\Payment::find($session->payment_id);
                    if ($payment && $payment->isPaid()) {
                        try {
                            $paymentService->refund($payment, null, 'auto_refund: vet_no_show_10min');
                        } catch (\Throwable $e) {
                            Log::error('Auto-refund failed after vet no-show', [
                                'session_uuid' => $session->uuid,
                                'payment_uuid' => $payment->uuid,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('No-show watchdog error for session', [
                    'session_uuid' => $session->uuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
