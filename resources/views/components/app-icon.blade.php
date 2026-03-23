@props([
    'name',
    'style' => 'o',
    'class' => 'w-5 h-5',
])

@php
    $component = 'heroicon-' . $style . '-' . $name;
@endphp

<x-dynamic-component :component="$component" :class="$class" />