@props(['name', 'bag' => 'default', 'id' => null])
<span @if($id) id="{{ $id }}" @endif class="field-error" @if(!$errors->getBag($bag)->has($name)) hidden @endif>{{ $errors->getBag($bag)->first($name) }}</span>
