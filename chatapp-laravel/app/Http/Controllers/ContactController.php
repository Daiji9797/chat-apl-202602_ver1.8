<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $userId = $request->attributes->get('userId');
        $input = $request->json()->all();

        $name = $input['name'] ?? null;
        $email = $input['email'] ?? null;
        $subject = $input['subject'] ?? null;
        $message = $input['message'] ?? null;

        if (!$name || !$email || !$subject || !$message) {
            return ApiResponse::error('Missing required fields: name, email, subject, message', 400);
        }

        $contactMessage = ContactMessage::create([
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'status' => 'new',
        ]);

        return ApiResponse::success([
            'success' => true,
            'message' => 'Contact message submitted successfully',
            'data' => $contactMessage,
        ]);
    }
}
