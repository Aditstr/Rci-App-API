<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\LegalCase;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('case.{id}', function ($user, $id) {
    $case = LegalCase::find($id);
    if (! $case) {
        return false;
    }
    // Only the client who created the case, or the assigned expert, can join the channel
    return (int) $user->id === (int) $case->client_id || (int) $user->id === (int) $case->expert_id;
});
