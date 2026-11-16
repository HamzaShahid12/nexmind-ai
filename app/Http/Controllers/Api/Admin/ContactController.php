<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        return ContactMessage::query()
            ->when($request->query('unread'), fn ($q) => $q->where('is_read', false))
            ->latest()->paginate($request->integer('per_page', 20));
    }

    public function show(ContactMessage $message)
    {
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return response()->json(['data' => $message]);
    }

    public function update(Request $request, ContactMessage $message)
    {
        $message->update($request->validate(['is_read' => ['required', 'boolean']]));

        return response()->json(['data' => $message]);
    }

    /** Sends a plain-text reply with the configured mailer and records that it was replied to. */
    public function reply(Request $request, ContactMessage $message)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        try {
            Mail::raw($data['body'], function ($mail) use ($message) {
                $mail->to($message->email, $message->name)->subject('Re: '.$message->subject);
            });
        } catch (Throwable $e) {
            Log::error('Contact reply could not be sent', ['reason' => class_basename($e)]);

            return response()->json(['message' => 'The reply could not be sent. Check the mail configuration.'], 502);
        }

        $message->update(['replied_at' => now(), 'is_read' => true]);
        ActivityLogger::log('admin.contact.replied', $message);

        return response()->json(['data' => $message]);
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return response()->json(['message' => 'Message deleted.']);
    }
}
