<?php

namespace App\Submissions;

use App\Mail\EditEventMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RequestEditLink
{
    public function __invoke(string $email): void
    {
        $email = Str::lower(trim($email));

        Mail::to($email)->send(new EditEventMail($email));
    }
}
