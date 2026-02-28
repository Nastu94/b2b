{{-- resources/views/livewire/vendor/offering-content-card.blade.php --}}
<div class="rounded-xl border border-gray-200 bg-white p-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h4 class="text-sm font-semibold text-gray-900">{{ $offering->name }}</h4>
            <p class="mt-1 text-xs text-gray-500">
                Inserisci descrizione e immagini per questo servizio.
            </p>
        </div>

        @if($profile->is_published)
            <span class="text-[11px] px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">
                PUBBLICATO
            </span>
        @else
            <span class="text-[11px] px-2 py-1 rounded-full bg-yellow-50 text-yellow-700 border border-yellow-100">
                IN BOZZA
            </span>
        @endif
    </div>

    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-medium text-gray-700">Titolo</label>
            <input type="text" wire:model.defer="title" class="mt-1 w-full rounded border-gray-300" />
        </div>

        <div>
            <label class="text-xs font-medium text-gray-700">Descrizione breve</label>
            <input type="text" wire:model.defer="short_description" class="mt-1 w-full rounded border-gray-300" />
        </div>

        <div class="md:col-span-2">
            <label class="text-xs font-medium text-gray-700">Descrizione completa</label>
            <textarea wire:model.defer="description" rows="4" class="mt-1 w-full rounded border-gray-300"></textarea>
        </div>

        <div>
            <label class="text-xs font-medium text-gray-700">Cover</label>
            <input type="file" wire:model="cover" class="mt-1 block w-full text-sm" />
            @error('cover')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror

            @if($profile->cover_image_path)
                <img
                    class="mt-3 rounded-lg border"
                    src="{{ route('media.public', ['path' => $profile->cover_image_path]) }}"
                    alt="Cover"
                >

                <button
                    type="button"
                    wire:click="removeCover"
                    wire:loading.attr="disabled"
                    class="mt-2 text-xs text-red-600 hover:underline disabled:opacity-50"
                >
                    Rimuovi cover
                </button>
            @endif
        </div>

        <div>
            <label class="text-xs font-medium text-gray-700">Gallery (max 8)</label>
            <input type="file" wire:model="gallery" multiple class="mt-1 block w-full text-sm" />
            @error('gallery')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
            @error('gallery.*')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- gallery preview --}}
    @if($profile->images->count())
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach($profile->images as $img)
                <div class="relative">
                    <img
                        class="rounded-lg border"
                        src="{{ route('media.public', ['path' => $img->path]) }}"
                        alt=""
                    >
                    <button
                        type="button"
                        wire:click="deleteImage({{ $img->id }})"
                        wire:loading.attr="disabled"
                        class="absolute top-1 right-1 text-xs bg-red-600 text-white px-2 py-1 rounded disabled:opacity-50"
                    >
                        X
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-4 flex items-center justify-end">
        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            class="px-4 py-2 rounded bg-indigo-600 text-white text-sm disabled:opacity-50"
        >
            Salva
        </button>
    </div>
</div>