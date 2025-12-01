<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use ApiResponse;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send a push notification to a user (Admin only)
     * POST /notifications/send
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = User::find($request->user_id);

        if (!$user->hasExpoPushToken()) {
            return $this->error('User does not have a valid push notification token', 400);
        }

        $log = $this->notificationService->sendPush(
            $user,
            $request->title,
            $request->message,
            $request->data ?? []
        );

        return $this->success([
            'notification_id' => $log->id,
            'status' => $log->status,
            'message' => 'Notification queued for delivery',
        ], 'Notification sent');
    }

    /**
     * Send notification to multiple users (Admin only)
     * POST /notifications/send-bulk
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1|max:100',
            'user_ids.*' => 'uuid|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $users = User::whereIn('id', $request->user_ids)
            ->whereNotNull('expo_push_token')
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            if ($user->hasExpoPushToken()) {
                $this->notificationService->sendPush(
                    $user,
                    $request->title,
                    $request->message,
                    $request->data ?? []
                );
                $sent++;
            } else {
                $failed++;
            }
        }

        return $this->success([
            'queued' => $sent,
            'skipped' => $failed,
            'total_users' => count($request->user_ids),
        ], 'Bulk notifications queued');
    }

    /**
     * Update user's Expo push token
     * POST /notifications/register-token
     */
    public function registerToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'expo_push_token' => 'required|string',
            'device_type' => 'nullable|in:ios,android',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        if (!$this->notificationService->validateExpoToken($request->expo_push_token)) {
            return $this->error('Invalid Expo push token format', 400);
        }

        $user = $request->user();
        $user->update([
            'expo_push_token' => $request->expo_push_token,
            'device_type' => $request->device_type,
        ]);

        return $this->success([
            'expo_push_token' => $user->expo_push_token,
            'device_type' => $user->device_type,
        ], 'Push token registered successfully');
    }

    /**
     * Remove user's Expo push token
     * DELETE /notifications/unregister-token
     */
    public function unregisterToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'expo_push_token' => null,
            'device_type' => null,
        ]);

        return $this->success(null, 'Push token removed successfully');
    }

    /**
     * Get user's notification history
     * GET /notifications/history
     */
    public function history(Request $request): JsonResponse
    {
        $notifications = NotificationLog::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->success($notifications, 'Notification history retrieved');
    }

    /**
     * Get notification by ID
     * GET /notifications/{id}
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $notification = NotificationLog::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$notification) {
            return $this->error('Notification not found', 404);
        }

        return $this->success($notification, 'Notification retrieved');
    }
}
