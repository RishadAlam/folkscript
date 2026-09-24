@props(['user', 'size' => 'normal'])
<span {{ $attributes->merge(['class' => 'avatar avatar-'.$size]) }} aria-hidden="true">
@if($user->avatar)<img src="{{ str_starts_with($user->avatar, 'http') || str_starts_with($user->avatar, '/') ? $user->avatar : '/storage/'.$user->avatar }}" alt="" width="44" height="44">@else{{ mb_substr($user->name, 0, 1) }}{{ mb_substr(explode(' ', $user->name)[1] ?? '', 0, 1) }}@endif
</span>
