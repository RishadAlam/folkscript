@props(['name', 'size' => 20])
<i data-lucide="{{ $name }}" {{ $attributes->merge(['class' => 'icon']) }} style="width:{{ $size }}px;height:{{ $size }}px" aria-hidden="true"></i>
