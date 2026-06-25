<x-layouts.app>
<div
    x-data="myPrompts()"
    x-init="init()"
    class="min-h-screen bg-slate-950 py-8 px-4"
>
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

        {{-- Flash success --}}
        @if(session('success'))
        <div class="mb-6 bg-green-900/40 border border-green-700/50 text-green-300 rounded-lg px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
        @endif

        {{-- Empty state --}}
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
                    editing: false,
                    editName: @js($prompt->name),
                    isPublic: @js($prompt->is_public),
                    deleting: false,
                    copied: false,
                    saving: false,
                    error: '',
                    promptId: {{ $prompt->id }},

                    async togglePublic() {
                        this.saving = true;
                        this.error  = '';
                        this.isPublic = !this.isPublic;
                        const res = await fetch('/prompts/' + this.promptId, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ is_public: this.isPublic }),
                        });
                        if (!res.ok) { this.isPublic = !this.isPublic; this.error = 'Could not update.'; }
                        this.saving = false;
                    },

                    async saveName() {
                        if (!this.editName.trim()) return;
                        this.saving = true;
                        this.error  = '';
                        const res = await fetch('/prompts/' + this.promptId, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ name: this.editName.trim() }),
                        });
                        if (res.ok) { this.editing = false; }
                        else        { this.error = 'Could not save.'; }
                        this.saving = false;
                    },

                    async deletePrompt() {
                        this.deleting = true;
                        const res = await fetch('/prompts/' + this.promptId, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        });
                        if (res.ok) { $el.closest('[x-data]').remove(); }
                        else        { this.error = 'Could not delete.'; this.deleting = false; }
                    },

                    async copyPrompt() {
                        await navigator.clipboard.writeText(@js($prompt->prompt_text));
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    },
                }"
                class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden flex flex-col"
            >
                {{-- Image --}}
                @if($prompt->image_path)
                <div class="aspect-video bg-slate-800 overflow-hidden">
                    <img src="{{ Storage::url($prompt->image_path) }}"
                         alt="{{ $prompt->name }}"
                         class="w-full h-full object-cover">
                </div>
                @else
                <div class="aspect-video bg-slate-800/50 flex items-center justify-center">
                    <svg class="w-10 h-10 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                @endif

                {{-- Body --}}
                <div class="flex-1 flex flex-col p-4 gap-3">

                    {{-- Name (edit inline) --}}
                    <div>
                        <template x-if="!editing">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-sm font-semibold text-white leading-snug" x-text="editName"></h3>
                                <button @click="editing = true" class="shrink-0 text-slate-600 hover:text-slate-300 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="editing">
                            <div class="flex gap-1.5">
                                <input
                                    type="text"
                                    x-model="editName"
                                    @keydown.enter="saveName()"
                                    @keydown.escape="editing = false"
                                    maxlength="120"
                                    class="flex-1 bg-slate-800 border border-slate-600 rounded-md px-2 py-1 text-xs text-slate-200 focus:outline-none focus:border-brand-500"
                                    x-init="$nextTick(() => $el.focus())"
                                >
                                <button @click="saveName()" :disabled="saving" class="px-2 py-1 bg-brand-600 hover:bg-brand-700 text-white text-xs rounded-md transition-colors">✓</button>
                                <button @click="editing = false" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs rounded-md transition-colors">✕</button>
                            </div>
                        </template>
                    </div>

                    {{-- Meta badges --}}
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
                    <p class="text-[11px] text-slate-500 leading-relaxed line-clamp-3">
                        {{ $prompt->prompt_text }}
                    </p>

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
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<script>
function myPrompts() {
    return {
        init() {}
    };
}
</script>
</x-layouts.app>
