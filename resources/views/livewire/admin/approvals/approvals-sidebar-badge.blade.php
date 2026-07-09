<span wire:poll.30s>
    @if($count > 0)
        <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
            {{ $count }}
        </span>
    @endif
</span>
