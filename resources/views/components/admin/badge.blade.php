@props(['tone' => 'slate'])
<span {{ $attributes->merge(['class' => 'badge tone-'.$tone]) }}>{{ $slot }}</span>
