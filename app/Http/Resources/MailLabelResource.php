<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Mail;

class MailLabelResource extends JsonResource
{
    public function toArray($request)
    {
        $userId = auth()->id();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'color' => $this->color,
            'unreadCount' => Mail::whereHas('labels', function($query) {
                $query->where('mail_labels.id', $this->id);
            })
            ->where('is_unread', true)
            ->whereHas('to', function($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->count(),
        ];
    }
}
