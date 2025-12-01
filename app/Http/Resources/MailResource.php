<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\MailSenderResource;
use App\Http\Resources\MailAttachmentResource;
use App\Http\Resources\MailLabelResource;

class MailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
        'folder' => $this->folder,
        'subject' => $this->subject,
        'message' => $this->message,
        'isUnread' => $this->is_unread,
        'from' => new MailSenderResource($this->whenLoaded('from')),
        'to' => MailSenderResource::collection($this->whenLoaded('to')),
        'labelIds' => $this->whenLoaded('labels', fn () => $this->labels->pluck('id')),
        'isStarred' => $this->is_starred,
        'isImportant' => $this->is_important,
        'createdAt' => $this->created_at,
        'attachments' => MailAttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
