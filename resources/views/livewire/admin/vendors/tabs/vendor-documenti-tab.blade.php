<div class="space-y-6">
    @if (session()->has('message'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Form (Modifica/Rifiuto) in cima se attivo -->
    @if($editingDocumentId || $rejectingDocumentId)
        <div class="space-y-6">
            @if($editingDocumentId)
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-900">Modifica Metadati Documento</h3>
                        <button wire:click="$set('editingDocumentId', null)" class="text-slate-400 hover:text-slate-600">
                            <x-app-icon name="x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                    
                    <form wire:submit="updateDocument" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Tipo</label>
                                <select wire:model="edit_type" class="mt-1 block w-full rounded-lg border-slate-200 shadow-sm focus:border-slate-400 focus:ring-slate-400 text-sm">
                                    @foreach(\App\Models\VendorDocument::TYPES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('edit_type') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Titolo (opzionale)</label>
                                <input type="text" wire:model="edit_title" class="mt-1 block w-full rounded-lg border-slate-200 shadow-sm focus:border-slate-400 focus:ring-slate-400 text-sm">
                                @error('edit_title') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Scadenza (opzionale)</label>
                                <input type="date" wire:model="edit_expires_at" class="mt-1 block w-full rounded-lg border-slate-200 shadow-sm focus:border-slate-400 focus:ring-slate-400 text-sm">
                                @error('edit_expires_at') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="inline-flex justify-center rounded-lg bg-slate-900 py-2 px-6 text-sm font-medium text-white hover:bg-slate-800 transition">
                                Salva
                            </button>
                            <button type="button" wire:click="$set('editingDocumentId', null)" class="inline-flex justify-center rounded-lg border border-slate-200 bg-white py-2 px-6 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                                Annulla
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if($rejectingDocumentId)
                <div class="bg-white p-5 rounded-xl border border-amber-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-amber-900">Rifiuta Documento</h3>
                        <button wire:click="$set('rejectingDocumentId', null)" class="text-amber-400 hover:text-amber-600">
                            <x-app-icon name="x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                    
                    <form wire:submit="rejectDocument" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-amber-800">Motivazione del rifiuto (obbligatoria)</label>
                            <textarea wire:model="review_note" rows="3" class="mt-1 block w-full rounded-lg border-amber-200 shadow-sm focus:border-amber-400 focus:ring-amber-400 text-sm" placeholder="Spiega al vendor perché il documento non va bene..."></textarea>
                            @error('review_note') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="inline-flex justify-center rounded-lg bg-amber-600 py-2 px-6 text-sm font-medium text-white hover:bg-amber-700 transition">
                                Conferma Rifiuto
                            </button>
                            <button type="button" wire:click="$set('rejectingDocumentId', null)" class="inline-flex justify-center rounded-lg border border-amber-200 bg-amber-50 py-2 px-6 text-sm font-medium text-amber-800 hover:bg-amber-100 transition">
                                Annulla
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    @endif

    <!-- Lista Documenti -->
    <div class="space-y-4">
        @if($documents->isEmpty())
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-8 text-center w-full">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 mb-4">
                    <x-app-icon name="document" class="w-6 h-6 text-slate-400" />
                </div>
                <h3 class="text-sm font-medium text-slate-900">Nessun documento</h3>
                <p class="mt-1 text-sm text-slate-500">Il vendor non ha ancora caricato alcun documento.</p>
            </div>
        @else
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden w-full">
                <ul class="divide-y divide-slate-100">
                    @foreach($documents as $doc)
                        @php
                            $isExpired = $doc->expires_at && $doc->expires_at->isPast();
                        @endphp
                        <li class="p-5 hover:bg-slate-50/50 transition flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-1">
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
                                        <x-app-icon name="document" class="w-3.5 h-3.5" />
                                        <span class="truncate max-w-[200px]" title="{{ $doc->original_filename }}">{{ $doc->original_filename }}</span>
                                        <span class="text-slate-400">({{ number_format($doc->size_bytes / 1024 / 1024, 2) }} MB)</span>
                                    </div>
                                    
                                    @if($doc->expires_at)
                                        <div class="flex items-center gap-1">
                                            <x-app-icon name="calendar" class="w-3.5 h-3.5" />
                                            Scadenza: <span class="{{ $isExpired ? 'text-rose-600 font-medium' : 'text-slate-700 font-medium' }}">{{ $doc->expires_at->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                    
                                    <div class="flex items-center gap-1">
                                        <x-app-icon name="clock" class="w-3.5 h-3.5" />
                                        Caricato il: <span class="text-slate-700">{{ $doc->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>

                                @if($doc->status === 'REJECTED' && $doc->review_note)
                                    <div class="mt-3 p-3 rounded-lg bg-rose-50 border border-rose-100 text-sm text-rose-800">
                                        <span class="font-semibold block mb-0.5">Motivazione Rifiuto:</span>
                                        {{ $doc->review_note }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <a href="{{ route('vendor-documents.download', $doc) }}" target="_blank" 
                                   class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition border border-indigo-200">
                                    Scarica
                                </a>
                                
                                <button wire:click="startEditDocument({{ $doc->id }})" 
                                        class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-lg text-slate-700 bg-white hover:bg-slate-50 transition border border-slate-200">
                                    Modifica
                                </button>

                                @if($doc->status === 'PENDING' || $doc->status === 'REJECTED')
                                    <button wire:click="approveDocument({{ $doc->id }})" 
                                            class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-lg text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition border border-emerald-200">
                                        Approva
                                    </button>
                                @endif
                                
                                @if($doc->status === 'PENDING' || $doc->status === 'APPROVED')
                                    <button wire:click="startRejectDocument({{ $doc->id }})" 
                                            class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-lg text-amber-700 bg-amber-50 hover:bg-amber-100 transition border border-amber-200">
                                        Rifiuta
                                    </button>
                                @endif

                                <button wire:confirm="Sei sicuro di voler eliminare questo documento?" wire:click="deleteDocument({{ $doc->id }})" 
                                        class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-lg text-rose-700 bg-white hover:bg-rose-50 transition border border-rose-200">
                                    Elimina
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
