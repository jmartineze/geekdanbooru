<x-layouts.app>
<div
    x-data="promptBuilder()"
    x-init="loadSection(activeSection)"
    class="flex flex-col h-screen overflow-hidden"
>
    {{-- ── HEADER ──────────────────────────────────────────────────────── --}}
    <header class="shrink-0 border-b border-slate-800 bg-slate-900/70 backdrop-blur px-4 py-2.5 z-20">
        <div class="max-w-screen-2xl mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h1 class="text-base font-bold text-white tracking-tight">{{ __('ui.app_name') }}</h1>
                <span class="text-xs text-slate-600">·</span>
                <span class="text-xs text-slate-500">{{ __('ui.tags_count', ['count' => number_format($totalTags)]) }}</span>
            </div>

            <div class="flex items-center gap-2">
                {{-- NSFW Toggle --}}
                <button
                    @click="nsfwEnabled = !nsfwEnabled; loadSection(activeSection)"
                    :class="nsfwEnabled
                        ? 'bg-rose-600/90 border-rose-500 text-white'
                        : 'bg-slate-800/80 border-slate-700 text-slate-400 hover:text-slate-200 hover:border-slate-500'"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-md border text-xs font-bold transition-all"
                >
                    <span x-text="nsfwEnabled ? '🔞 NSFW ON' : '🔒 NSFW OFF'"></span>
                </button>

                {{-- Clear all --}}
                <button
                    @click="clearAll()"
                    x-show="Object.keys(selected).length > 0"
                    x-transition
                    class="px-3 py-1.5 rounded-md bg-slate-700/80 hover:bg-slate-600 text-slate-300 text-xs font-medium transition-colors border border-slate-600"
                >
                    {{ __('ui.clear_all') }}
                </button>
            </div>
        </div>
    </header>

    {{-- ── BODY ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-1 min-h-0">

        {{-- ── LEFT NAV: section tabs ──────────────────────────────────── --}}
        <nav class="shrink-0 w-40 border-r border-slate-800 bg-slate-900/50 flex flex-col py-3 px-2 gap-0.5 overflow-y-auto">
            @foreach(['character','pose','outfit','scene'] as $sec)
            <button
                @click="switchSection('{{ $sec }}')"
                :class="activeSection === '{{ $sec }}'
                    ? 'bg-brand-600 text-white shadow'
                    : 'text-slate-400 hover:text-slate-200 hover:bg-white/6'"
                class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-sm font-semibold transition-all text-left"
            >
                <span>{{ __('ui.sections.' . $sec) }}</span>
                <span
                    x-show="countBySection['{{ $sec }}'] > 0"
                    x-text="countBySection['{{ $sec }}']"
                    class="text-[10px] font-bold bg-white/20 rounded-full px-1.5 py-0.5 leading-none"
                ></span>
            </button>
            @endforeach

            <div class="mt-auto pt-3 border-t border-slate-800/60 px-1">
                <p class="text-[10px] text-slate-600 uppercase tracking-wider mb-1">Total tags</p>
                <p class="text-xl font-bold text-brand-400" x-text="Object.keys(selected).length"></p>
            </div>
        </nav>

        {{-- ── CENTER: tag browser ─────────────────────────────────────── --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            {{-- Sticky search --}}
            <div class="shrink-0 sticky top-0 z-10 bg-slate-950/95 backdrop-blur border-b border-slate-800 px-4 py-2">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        x-model.debounce.250ms="searchQuery"
                        @input="filterTags()"
                        placeholder="{{ __('ui.search_tags') }}"
                        class="w-full bg-slate-800/60 border border-slate-700/60 rounded-lg pl-9 pr-3 py-1.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/50 transition-colors"
                    >
                    <button
                        x-show="searchQuery.length > 0"
                        @click="searchQuery = ''; filterTags()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300"
                    >✕</button>
                </div>
            </div>

            {{-- Loading --}}
            <div x-show="loading" class="flex-1 flex items-center justify-center text-slate-500 text-sm gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                {{ __('ui.loading') }}
            </div>

            {{-- Tag groups (accordions) --}}
            <div x-show="!loading" class="flex-1 overflow-y-auto divide-y divide-slate-800/50">
                <template x-for="(group, subsection) in groupedTags" :key="subsection">
                    <div x-data="{ open: true, showAll: false }">

                        {{-- Accordion header --}}
                        <button
                            @click="open = !open"
                            class="flex items-center justify-between w-full px-4 py-2.5 text-left hover:bg-white/3 transition-colors group"
                        >
                            <div class="flex items-center gap-2">
                                <svg
                                    :class="open ? 'rotate-90' : ''"
                                    class="w-3 h-3 text-slate-600 group-hover:text-slate-400 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                                <span class="text-xs font-semibold text-slate-400 group-hover:text-slate-200 transition-colors uppercase tracking-wider" x-text="subsectionLabel(subsection)"></span>
                                <span class="text-[10px] text-slate-600" x-text="'(' + group.length + ')'"></span>
                            </div>
                            <span
                                x-show="(countBySubsection[subsection] || 0) > 0"
                                x-text="(countBySubsection[subsection] || 0) + ' selected'"
                                class="text-[10px] font-semibold text-brand-400"
                            ></span>
                        </button>

                        {{-- Tag pills --}}
                        <div x-show="open" class="px-4 pb-3 pt-1">
                            <div class="flex flex-wrap gap-1.5">
                                <template
                                    x-for="(tag, idx) in (showAll ? group : group.slice(0, 30))"
                                    :key="tag.name"
                                >
                                    <button
                                        @click="toggleTag(tag)"
                                        :class="tagPillClass(tag)"
                                        :title="formatPostCount(tag.post_count)"
                                        class="tag-pill"
                                    >
                                        <span x-text="tag.name"></span>
                                        <span
                                            x-show="tag.is_nsfw && !selected[tag.name]"
                                            class="text-[9px] opacity-50 ml-0.5"
                                        >18+</span>
                                    </button>
                                </template>
                            </div>

                            {{-- Show more / less --}}
                            <div x-show="group.length > 30" class="mt-2">
                                <button
                                    @click="showAll = !showAll"
                                    class="text-xs text-slate-500 hover:text-brand-400 transition-colors font-medium"
                                    x-text="showAll
                                        ? '{{ __('ui.show_less') }}'
                                        : '{{ __('ui.show_more', ['count' => ':count']) }}'.replace(':count', group.length - 30)"
                                ></button>
                            </div>

                            <p
                                x-show="group.length === 0"
                                class="text-xs text-slate-600 py-1"
                            >{{ __('ui.no_tags_found') }}</p>
                        </div>
                    </div>
                </template>

                <div
                    x-show="Object.keys(groupedTags).length === 0 && !loading"
                    class="py-16 text-center text-slate-500 text-sm"
                >{{ __('ui.no_tags_found') }}</div>
            </div>
        </div>

        {{-- ── RIGHT: Prompt panel ─────────────────────────────────────── --}}
        <aside class="shrink-0 w-72 border-l border-slate-800 bg-slate-900/40 flex flex-col">

            {{-- Header --}}
            <div class="shrink-0 flex items-center justify-between px-4 py-2.5 border-b border-slate-800">
                <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider">{{ __('ui.prompt_label') }}</h2>
                <button
                    @click="copyPrompt()"
                    :disabled="Object.keys(selected).length === 0"
                    :class="Object.keys(selected).length === 0
                        ? 'opacity-30 cursor-not-allowed bg-slate-700'
                        : 'bg-brand-600 hover:bg-brand-700 cursor-pointer'"
                    class="px-3 py-1 text-xs font-semibold rounded-md text-white transition-all"
                >
                    <span x-text="copied ? '✓ {{ __('ui.copied') }}' : '{{ __('ui.copy_prompt') }}'"></span>
                </button>
            </div>

            {{-- Prompt textarea display --}}
            <div class="shrink-0 px-3 py-3 border-b border-slate-800">
                <div
                    class="min-h-[5rem] max-h-32 overflow-y-auto bg-slate-900/60 border border-slate-700/50 rounded-lg px-3 py-2.5 text-xs leading-relaxed break-all select-all"
                    :class="promptText ? 'text-slate-200' : 'text-slate-600 italic'"
                    x-text="promptText || '{{ __('ui.prompt_placeholder') }}'"
                ></div>
            </div>

            {{-- Selected tags list --}}
            <div class="flex-1 overflow-y-auto px-3 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        {{ __('ui.selected_tags') }}
                    </p>
                    <span
                        x-show="Object.keys(selected).length > 0"
                        x-text="Object.keys(selected).length"
                        class="text-[10px] font-bold text-brand-400 bg-brand-600/20 rounded-full px-1.5 py-0.5 leading-none"
                    ></span>
                </div>

                <div class="flex flex-wrap gap-1">
                    <template x-for="(info, tag) in selected" :key="tag">
                        <span
                            :class="info.is_nsfw
                                ? 'bg-rose-900/40 text-rose-300 border-rose-800/60'
                                : 'bg-brand-600/20 text-brand-300 border-brand-700/40'"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border"
                        >
                            <span x-text="tag"></span>
                            <button
                                @click="removeTag(tag)"
                                class="text-[9px] opacity-60 hover:opacity-100 hover:text-red-400 transition-all ml-0.5"
                            >✕</button>
                        </span>
                    </template>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
const SUBSECTION_LABELS = @json(__('ui.subsections'));

function promptBuilder() {
    return {
        activeSection:     'character',
        nsfwEnabled:       false,
        loading:           false,
        searchQuery:       '',
        allTags:           [],
        groupedTags:       {},
        selected:          {},
        copied:            false,
        countBySection:    { character: 0, pose: 0, outfit: 0, scene: 0 },
        countBySubsection: {},

        get promptText() {
            return Object.keys(this.selected).join(', ');
        },

        async loadSection(section) {
            this.loading     = true;
            this.groupedTags = {};
            this.searchQuery = '';

            try {
                const params = new URLSearchParams({
                    section: section,
                    nsfw:    this.nsfwEnabled ? '1' : '0',
                });
                const res    = await fetch('/api/tags?' + params.toString());
                this.allTags = await res.json();
                this.applyFilter();
            } catch (e) {
                console.error('Failed to load tags:', e);
            } finally {
                this.loading = false;
            }
        },

        applyFilter() {
            const q    = this.searchQuery.toLowerCase().trim();
            const tags = q
                ? this.allTags.filter(t => t.name.includes(q))
                : this.allTags;

            // Group by subsection, preserving API order (sorted by post_count DESC)
            const groups = {};
            tags.forEach(tag => {
                if (!groups[tag.subsection]) groups[tag.subsection] = [];
                groups[tag.subsection].push(tag);
            });
            this.groupedTags = groups;
        },

        filterTags() {
            this.applyFilter();
        },

        switchSection(section) {
            this.activeSection = section;
            this.loadSection(section);
        },

        toggleTag(tag) {
            if (this.selected[tag.name]) {
                this.removeTag(tag.name);
            } else {
                this.selected = {
                    ...this.selected,
                    [tag.name]: {
                        is_nsfw:    tag.is_nsfw,
                        section:    this.activeSection,
                        subsection: tag.subsection,
                    }
                };
                this.countBySection[this.activeSection]++;
                this.countBySubsection[tag.subsection] =
                    (this.countBySubsection[tag.subsection] || 0) + 1;
            }
        },

        removeTag(name) {
            if (!this.selected[name]) return;
            const info = this.selected[name];
            const copy = { ...this.selected };
            delete copy[name];
            this.selected = copy;
            if (this.countBySection[info.section] > 0)     this.countBySection[info.section]--;
            if ((this.countBySubsection[info.subsection] || 0) > 0) this.countBySubsection[info.subsection]--;
        },

        clearAll() {
            this.selected          = {};
            this.countBySection    = { character: 0, pose: 0, outfit: 0, scene: 0 };
            this.countBySubsection = {};
        },

        async copyPrompt() {
            if (!this.promptText) return;
            await navigator.clipboard.writeText(this.promptText);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },

        tagPillClass(tag) {
            const sel = !!this.selected[tag.name];
            if (tag.is_nsfw) return sel ? 'tag-pill-nsfw-selected' : 'tag-pill-nsfw';
            return sel ? 'tag-pill-selected' : 'tag-pill-default';
        },

        subsectionLabel(key) {
            return SUBSECTION_LABELS[key]
                || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        formatPostCount(n) {
            if (!n) return '';
            if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M posts';
            if (n >= 1_000)     return (n / 1_000).toFixed(0) + 'K posts';
            return n + ' posts';
        },
    };
}
</script>
</x-layouts.app>
