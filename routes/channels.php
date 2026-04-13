<?php

use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. Given a channel name and a callback, Laravel will
| return the result of the callback as the channel's presence or authorization.
|
*/

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === $id;
});

/*
 * SOS real-time channel.
 * Only the request owner and the assigned vet may subscribe.
 */
Broadcast::channel('sos.{uuid}', function (User $user, string $uuid) {
    $sos = SosRequest::where('uuid', $uuid)->first();

    if (!$sos) {
        return false;
    }

    // Owner of the SOS request
    if ($user->id === $sos->user_id) {
        return true;
    }

    // Assigned vet
    if ($user->isVet() && $user->vetProfile && $user->vetProfile->id === $sos->assigned_vet_id) {
        return true;
    }

    return false;
});
