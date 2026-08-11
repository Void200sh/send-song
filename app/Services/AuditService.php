<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * ─── CATAT AKSI ADMIN KE AUDIT LOG ───
     * Dipanggil di setiap aksi penting admin (hapus/ban/resolve/pin/export, dll).
     * Menyimpan snapshot nama admin + IP + user agent, jadi aman walau user dihapus.
     *
     * @param  array<string, mixed>  $details
     */
    public function log(string $action, ?string $targetType = null, string|int|null $targetId = null, array $details = [], ?Request $request = null): void
    {
        try {
            $request ??= request();
            $user = Auth::user();

            AuditLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId !== null ? (string) $targetId : null,
                'details' => $details ?: null,
                'ip_address' => $request ? SpamDetectionService::clientIp($request) : null,
                'user_agent' => $request ? substr((string) $request->userAgent(), 0, 300) : null,
            ]);
        } catch (\Throwable $e) {
            // Logging tidak boleh menjatuhkan aplikasi
            report($e);
        }
    }

    /**
     * ─── CATAT PERCOBAAN LOGIN (sukses / gagal) ───
     */
    public function logLogin(string $status, string $email, ?User $user = null, ?Request $request = null): void
    {
        try {
            $request ??= request();

            $ip = $request ? SpamDetectionService::clientIp($request) : null;

            // Login "mencurigakan": sukses dari IP yang belum pernah dipakai user ini sebelumnya
            // (kecuali ini login pertama user sama sekali — kemungkinan besar memang pemiliknya)
            $isSuspicious = false;
            if ($status === 'success' && $user && $ip) {
                $priorSuccesses = LoginLog::where('user_id', $user->id)
                    ->where('status', 'success')
                    ->count();
                $everUsedIp = LoginLog::where('user_id', $user->id)
                    ->where('status', 'success')
                    ->where('ip_address', $ip)
                    ->exists();
                $isSuspicious = $priorSuccesses > 0 && ! $everUsedIp;
            }

            LoginLog::create([
                'user_id' => $user?->id,
                'email' => $email,
                'status' => $status,
                'ip_address' => $ip,
                'user_agent' => $request ? substr((string) $request->userAgent(), 0, 300) : null,
                'is_suspicious' => $isSuspicious,
                'is_new' => true,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
