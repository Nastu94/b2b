<div class="bg-white border border-slate-200 rounded-xl shadow-sm mt-8 overflow-hidden">
    <div class="p-6 border-b border-slate-100 bg-white">
        <h2 class="text-lg font-semibold text-slate-900">Documenti Autorizzativi</h2>
        <p class="text-sm text-slate-500 mt-1">Gestisci le tue licenze, autorizzazioni e assicurazioni necessarie per operare sulla piattaforma.</p>
    </div>

    <div class="p-6 space-y-8 bg-slate-50/30">
        @if (session()->has('message'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                {{ session('message') }}
            </div>
        @endif

        <!-- Form Upload -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                    <x-app-icon name="plus" class="w-4 h-4 text-slate-600" />
                </div>
                Aggiungi nuovo documento
            </h3>
            
            <form wire:submit.prevent="uploadDocument" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tipo *</label>
                        <select wire:model="type" class="block w-full rounded-lg border-slate-200 shadow-sm focus:border-slate-400 focus:ring-slate-400 text-sm">
                            <option value="">-- Seleziona --</option>
                            @foreach(\App\Models\VendorDocument::TYPES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Titolo (opzionale)</label>
                        <input type="text" wire:model="title" placeholder="Es. Licenza Limousine" class="block w-full rounded-lg border-slate-200 shadow-sm focus:border-slate-400 focus:ring-slate-400 text-sm">
                        @error('title') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Scadenza (opzionale)</label>
                        <input type="date" wire:model="expires_at" class="block w-full rounded-lg border-slate-200 shadow-sm focus:border-slate-400 focus:ring-slate-400 text-sm">
                        @error('expires_at') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">File *</label>
                        <input type="file" wire:model="document_file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 border border-slate-200 rounded-lg bg-white">
                        <div wire:loading wire:target="document_file" class="text-xs text-indigo-600 mt-1">Caricamento in corso...</div>
                        <p class="text-[11px] text-slate-400 mt-1">PDF, JPG, PNG, WEBP. Max 10MB.</p>
                        @error('document_file') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="inline-flex justify-center items-center gap-2 rounded-lg bg-slate-900 py-2.5 px-6 text-sm font-medium text-white hover:bg-slate-800 transition shadow-sm">
                        <x-app-icon name="arrow-up-tray" class="w-4 h-4" />
                        Carica Documento
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista Documenti -->
        <div>
            <h3 class="text-sm font-semibold text-slate-900 mb-4">I tuoi documenti caricati</h3>
            
            @if($documents->isEmpty())
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-8 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 mb-3">
                        <x-app-icon name="document" class="w-6 h-6 text-slate-400" />
                    </div>
                    <p class="text-sm text-slate-500 font-medium">Non hai ancora caricato alcun documento autorizzativo.</p>
                    <p class="text-xs text-slate-400 mt-1">Usa il modulo qui sopra per aggiungere il tuo primo file.</p>
                </div>
            @else
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                    <ul class="divide-y divide-slate-100">
                        @foreach($documents as $doc)
                            @php
                                $isExpired = $doc->expires_at && $doc->expires_at->isPast();
                            @endphp
                            <li class="p-5 hover:bg-slate-50/50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-1.5">
                                        <h4 class="text-sm font-semibold text-slate-900 truncate">
                                            {{ \App\Models\VendorDocument::TYPES[$doc->type] ?? $doc->type }}
                                        </h4>
                                        @if($isExpired)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-rose-100 text-rose-800 border border-rose-200">
                                                SCADUTO
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium border
                                                {{ $doc->status === 'APPROVED' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : '' }}
                                                {{ $doc->status === 'PENDING' ? 'bg-amber-50 text-amber-700 border-amber-200' : '' }}
                                                {{ $doc->status === 'REJECTED' ? 'bg-rose-50 text-rose-700 border-rose-200' : '' }}
                                            ">
                                                {{ $doc->status === 'PENDING' ? 'IN ATTESA' : ($doc->status === 'APPROVED' ? 'APPROVATO' : 'RIFIUTATO') }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if($doc->title)
                                        <p class="text-sm text-slate-600 mb-2">{{ $doc->title }}</p>
                                    @endif
                                    
                                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
                                        <div class="flex items-center gap-1">
                                            <x-app-icon name="document" class="w-3.5 h-3.5 text-slate-400" />
                                            <span class="truncate max-w-[200px]" title="{{ $doc->original_filename }}">{{ $doc->original_filename }}</span>
                                        </div>
                                        
                                        @if($doc->expires_at)
                                            <div class="flex items-center gap-1">
                                                <x-app-icon name="calendar" class="w-3.5 h-3.5 text-slate-400" />
                                                Scadenza: <span class="{{ $isExpired ? 'text-rose-600 font-medium' : 'text-slate-700 font-medium' }}">{{ $doc->expires_at->format('d/m/Y') }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    @if($doc->status === 'REJECTED' && $doc->review_note)
                                        <div class="mt-3 p-3 rounded-lg bg-rose-50 border border-rose-100 text-sm text-rose-800">
                                            <span class="font-semibold block mb-0.5">Nota di rifiuto dall'amministratore:</span>
                                            {{ $doc->review_note }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ route('vendor-documents.download', $doc) }}" target="_blank" 
                                       class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-lg text-slate-700 bg-white hover:bg-slate-50 transition border border-slate-200 shadow-sm">
                                        <x-app-icon name="arrow-down-tray" class="w-3.5 h-3.5 mr-1.5" />
                                        Scarica
                                    </a>
                                    
                                    @if($doc->status === 'PENDING' || $doc->status === 'REJECTED')
                                        <button wire:confirm="Sei sicuro di voler eliminare questo documento?" wire:click="deleteDocument({{ $doc->id }})" 
                                                class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-lg text-rose-700 bg-white hover:bg-rose-50 transition border border-rose-200 shadow-sm">
                                            <x-app-icon name="trash" class="w-3.5 h-3.5 mr-1.5" />
                                            Elimina
                                        </button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
