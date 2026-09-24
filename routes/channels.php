<?php
use Illuminate\Support\Facades\Broadcast;
Broadcast::channel('App.Models.User.{id}', fn (\App\Models\User $user, int $id) => $user->id === $id && !$user->suspended_at);
