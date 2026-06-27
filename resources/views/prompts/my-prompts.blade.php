<x-layouts.app>
<div class="min-h-screen bg-slate-950 py-8 px-4">
    <div class="max-w-5xl mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-white">My Prompts</h1>
                <p class="text-slate-400 text-sm mt-0.5">{{ $prompts->count() }} saved prompt{{ $prompts->count() !== 1 ? 's' : '' }}</p>
            </div>
            <a href="/builder"
               class="flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Prompt
            </a>
        </div>

        @if(session('success'))
        <div class="mb-6 bg-green-900/40 border border-green-700/50 text-green-300 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
        @endif

        @if($prompts->isEmpty())
        <div class="text-center py-24">
            <svg class="w-14 h-14 text-slate-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
            </svg>
            <p class="text-slate-400 text-base font-medium">No saved prompts yet</p>
            <p class="text-slate-600 text-sm mt-1">Build something in the <a href="/builder" class="text-brand-400 hover:underline">prompt builder</a> and save it here.</p>
        </div>
        @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($prompts as $prompt)
            <div
                x-data="{
                    isPublic:   @js((bool)$prompt->is_public),
                    deleting:   false,
                    copied:     false,
                    saving:     false,
                    error:      '',
                    id:         {{ $prompt->id }},
                    csrf:       '{{ csrf_token() }}',
                    promptText: @js($prompt->prompt_text),

                    // Edit modal state
                    editOpen:       false,
                    editName:       @js($prompt->name),
                    editImage:      null,
                    editImageName:  '',
                    editImageError: '',
                    editDisclaimer: false,
                    editPreview:    @js($prompt->image_path ? '/storage/' . $prompt->image_path : null),
                    editSaving:     false,
                    editError:      '',

                    // ── helpers ────────────────────────────────────────────

                    async patch(data) {
                        const fd = new FormData();
                        fd.append('_token',  this.csrf);
                        fd.append('_method', 'PATCH');
                        Object.entries(data).forEach(([k, v]) => { if (v !== undefined) fd.append(k, v); });
                        return fetch('/prompts/' + this.id, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: fd,
                        });
                    },

                    async togglePublic() {
                        this.saving   = true;
                        this.error    = '';
                        const was     = this.isPublic;
                        this.isPublic = !this.isPublic;
                        try {
                            const res  = await this.patch({ is_public: this.isPublic ? '1' : '0' });
                            const body = await res.json().catch(() => ({}));
                            if (!res.ok) { this.isPublic = was; this.error = res.status + ': ' + (body.message || 'Error'); }
                        } catch(e) { this.isPublic = was; this.error = 'Network error.'; }
                        this.saving = false;
                    },

                    async deletePrompt() {
                        this.deleting = true;
                        this.error    = '';
                        try {
                            const fd = new FormData();
                            fd.append('_token',  this.csrf);
                            fd.append('_method', 'DELETE');
                            const res = await fetch('/prompts/' + this.id, {
                                method: 'POST',
                                headers: { 'Accept': 'application/json' },
                                body: fd,
                            });
                            if (res.ok) { $el.remove(); }
                            else        { this.error = 'Could not delete.'; this.deleting = false; }
                        } catch(e) { this.error = 'Network error.'; this.deleting = false; }
                    },

                    async copyPrompt() {
                        await navigator.clipboard.writeText(this.promptText);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    },

                    openInBuilder() {
                        sessionStorage.setItem('builderLoad', JSON.stringify({
                            names:      this.promptText,
                            promptId:   this.id,
                            promptName: this.editName,
                            imagePath:  this.editPreview,
                        }));
                        window.location = '/builder';
                    },

                    // ── edit modal ─────────────────────────────────────────

                    openEdit() {
                        this.editName       = @js($prompt->name);
                        this.editImage      = null;
                        this.editImageName  = '';
                        this.editImageError = '';
                        this.editDisclaimer = false;
                        this.editError      = '';
                        this.editOpen       = true;
                    },

                    handleEditImage(e) {
                        const file = e.target.files[0];
                        if (!file) return;
                        if (file.size > 1024 * 1024) {
                            this.editImageError = 'Image must be under 1 MB.';
                            e.target.value = '';
                            return;
                        }
                        const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
                        if (!allowed.includes(file.type)) {
                            this.editImageError = 'Only JPEG, PNG, GIF or WebP allowed.';
                            e.target.value = '';
                            return;
                        }
                        this.editImageError = '';
                        this.editImage      = file;
                        this.editImageName  = file.name;
                        this.editPreview    = URL.createObjectURL(file);
                    },

                    async submitEdit() {
                        if (!this.editName.trim()) return;
                        if (this.editImage && !this.editDisclaimer) return;
                        this.editSaving = true;
                        this.editError  = '';
                        try {
                            const payload = { name: this.editName.trim() };
                            if (this.editImage) {
                                payload.image      = this.editImage;
                                payload.disclaimer = '1';
                            }
                            const res  = await this.patch(payload);
                            const body = await res.json().catch(() => ({}));
                            if (res.ok) {
                                if (body.image_path) this.editPreview = '/storage/' + body.image_path;
                                this.editOpen = false;
                            } else {
                                this.editError = body.message || 'Could not save.';
                            }
                        } catch(e) { this.editError = 'Network error.'; }
                        this.editSaving = false;
                    },
                }"
                class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden flex flex-col"
            >
                {{-- Image --}}
                <div class="aspect-[3/4] bg-slate-800 overflow-hidden relative">
                    <template x-if="editPreview">
                        <img :src="editPreview" alt="" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!editPreview">
                        <div class="w-full h-full bg-slate-800/50 flex items-center justify-center">
                            <svg class="w-10 h-10 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </template>
                </div>

                {{-- Body --}}
                <div class="flex-1 flex flex-col p-4 gap-3">

                    {{-- Name + edit button --}}
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-sm font-semibold text-white leading-snug">{{ $prompt->name }}</h3>
                        <button @click="openEdit()" class="shrink-0 p-1 text-slate-600 hover:text-slate-300 transition-colors" title="Edit">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Badges --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full
                            {{ match($prompt->section) {
                                'character' => 'bg-blue-900/40 text-blue-300 border border-blue-800/50',
                                'pose'      => 'bg-purple-900/40 text-purple-300 border border-purple-800/50',
                                'outfit'    => 'bg-amber-900/40 text-amber-300 border border-amber-800/50',
                                'scene'     => 'bg-emerald-900/40 text-emerald-300 border border-emerald-800/50',
                                default     => 'bg-slate-800 text-slate-400 border border-slate-700',
                            } }}">{{ ucfirst($prompt->section) }}</span>
                        <span class="text-[10px] text-slate-600">{{ $prompt->created_at->format('M j, Y') }}</span>
                    </div>

                    {{-- Prompt preview --}}
                    <p class="text-[11px] text-slate-500 leading-relaxed line-clamp-3">{{ $prompt->prompt_text }}</p>

                    <p x-show="error" x-text="error" class="text-[10px] text-rose-400"></p>

                    {{-- Actions --}}
                    <div class="mt-auto flex items-center justify-between pt-1">

                        {{-- Public toggle --}}
                        <button
                            @click="togglePublic()"
                            :disabled="saving"
                            :class="isPublic
                                ? 'bg-green-900/40 border-green-700/50 text-green-400'
                                : 'bg-slate-800 border-slate-700 text-slate-500 hover:text-slate-300'"
                            class="flex items-center gap-1 px-2.5 py-1 rounded-full border text-[10px] font-semibold transition-all"
                        >
                            <span x-text="isPublic ? '🌐 Public' : '🔒 Private'"></span>
                        </button>

                        <div class="flex items-center gap-1">
                            {{-- Open in Builder --}}
                            <button
                                @click="openInBuilder()"
                                title="Open in Builder"
                                class="p-1.5 rounded-md text-slate-500 hover:text-brand-400 hover:bg-slate-800 transition-all"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </button>

                            {{-- Copy --}}
                            <button
                                @click="copyPrompt()"
                                :title="copied ? 'Copied!' : 'Copy prompt'"
                                class="p-1.5 rounded-md text-slate-500 hover:text-brand-400 hover:bg-slate-800 transition-all"
                            >
                                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                </svg>
                                <svg x-show="copied" class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>

                            {{-- Delete --}}
                            <button
                                @click="if(confirm('Delete this prompt?')) deletePrompt()"
                                :disabled="deleting"
                                class="p-1.5 rounded-md text-slate-500 hover:text-rose-400 hover:bg-rose-900/20 transition-all"
                                title="Delete"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ── EDIT MODAL ─────────────────────────────────────────── --}}
                <div
                    x-show="editOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @keydown.escape.window="editOpen = false"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/70 backdrop-blur-sm"
                    style="display:none"
                >
                    <div
                        @click.stop
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        class="w-full sm:max-w-md bg-slate-900 border-t sm:border border-slate-700 rounded-t-2xl sm:rounded-2xl shadow-2xl flex flex-col max-h-[92dvh]"
                    >
                        {{-- Header --}}
                        <div class="shrink-0 flex items-center justify-between px-5 py-4 border-b border-slate-800">
                            <h3 class="text-base font-bold text-white">Edit Prompt</h3>
                            <button @click="editOpen = false" class="p-1 text-slate-500 hover:text-slate-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitEdit()" class="overflow-y-auto flex-1">
                            <div class="px-5 py-5 space-y-4">

                                {{-- Name --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Name <span class="text-rose-400">*</span></label>
                                    <input
                                        type="text"
                                        x-model="editName"
                                        maxlength="120"
                                        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/50 transition-colors"
                                        required
                                        x-init="$nextTick(() => $el.focus())"
                                    >
                                </div>

                                {{-- Image --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                        Image <span class="text-slate-500 font-normal">(optional, max 1 MB — replaces current)</span>
                                    </label>

                                    {{-- Preview current / new --}}
                                    <div x-show="editPreview" class="mb-2 rounded-xl overflow-hidden aspect-[3/4] max-w-[140px] bg-slate-800">
                                        <img :src="editPreview" class="w-full h-full object-cover">
                                    </div>

                                    <label class="flex items-center justify-center gap-2 w-full h-16 border-2 border-dashed border-slate-700 rounded-lg cursor-pointer hover:border-brand-500/60 hover:bg-brand-600/5 transition-all active:scale-[.98]">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-xs text-slate-500" x-text="editImageName || 'Tap to choose image'"></span>
                                        <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="handleEditImage($event)">
                                    </label>
                                    <p x-show="editImageError" x-text="editImageError" class="mt-1 text-xs text-rose-400"></p>
                                </div>

                                {{-- Disclaimer --}}
                                <div x-show="editImage" class="bg-slate-800/60 border border-slate-700/50 rounded-lg p-3">
                                    <label class="flex gap-2.5 cursor-pointer">
                                        <input type="checkbox" x-model="editDisclaimer" class="mt-0.5 shrink-0 accent-brand-500">
                                        <span class="text-[11px] text-slate-400 leading-relaxed">
                                            I certify that I have the rights to upload this image. Geekguayaco may remove it without notice if it violates copyright or usage policies.
                                        </span>
                                    </label>
                                </div>

                                {{-- Open in Builder --}}
                                <div class="pt-1 border-t border-slate-800">
                                    <button
                                        type="button"
                                        @click="editOpen = false; openInBuilder()"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-brand-600/50 text-brand-400 text-sm font-medium hover:bg-brand-600/10 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                        Open in Builder to edit tags
                                    </button>
                                </div>

                                <p x-show="editError" x-text="editError" class="text-xs text-rose-400"></p>

                                {{-- Actions --}}
                                <div class="flex gap-2">
                                    <button type="button" @click="editOpen = false" class="flex-1 py-3 sm:py-2.5 rounded-lg border border-slate-700 text-slate-400 text-sm font-medium hover:border-slate-500 transition-all">Cancel</button>
                                    <button
                                        type="submit"
                                        :disabled="editSaving || !editName.trim() || (editImage && !editDisclaimer)"
                                        :class="(editSaving || !editName.trim() || (editImage && !editDisclaimer)) ? 'opacity-40 cursor-not-allowed' : 'hover:bg-brand-700'"
                                        class="flex-1 py-3 sm:py-2.5 rounded-lg bg-brand-600 text-white text-sm font-semibold transition-all flex items-center justify-center gap-2"
                                    >
                                        <svg x-show="editSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                        </svg>
                                        <span x-text="editSaving ? 'Saving…' : 'Save changes'"></span>
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
</x-layouts.app>
