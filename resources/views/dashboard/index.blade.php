<x-layouts.app>
<script>
  window.__auth  = @json(auth()->check());
  window.__csrf  = '{{ csrf_token() }}';
  window.__user  = @json(auth()->check() ? auth()->user()->name : null);
</script>

<div
    x-data="promptBuilder()"
    x-init="init()"
    class="flex flex-col h-[100dvh] overflow-hidden bg-slate-950"
>

    {{-- ─────────────────────────────────────────────────────────────────────
         HEADER (full-width, always visible)
    ─────────────────────────────────────────────────────────────────────── --}}
    <header class="shrink-0 z-20 border-b border-slate-800 bg-slate-900/80 backdrop-blur px-3 sm:px-4 py-2.5">
        <div class="flex items-center justify-between gap-2">

            <div class="flex items-center gap-2 min-w-0">
                <a href="/" class="text-sm sm:text-base font-bold text-white tracking-tight hover:text-brand-400 transition-colors whitespace-nowrap">{{ __('ui.app_name') }}</a>
                <span class="hidden sm:inline text-xs text-slate-600">·</span>
                <span class="hidden sm:inline text-xs text-slate-500 whitespace-nowrap">{{ __('ui.tags_count', ['count' => number_format($totalTags)]) }}</span>
            </div>

            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                {{-- NSFW toggle --}}
                <button
                    @click="nsfwEnabled = !nsfwEnabled; loadSection(activeSection)"
                    :class="nsfwEnabled
                        ? 'bg-rose-600/90 border-rose-500 text-white'
                        : 'bg-slate-800 border-slate-700 text-slate-400 hover:text-slate-200'"
                    class="flex items-center gap-1 px-2 sm:px-3 py-1.5 rounded-md border text-[11px] sm:text-xs font-bold transition-all"
                >
                    <span x-text="nsfwEnabled ? '🔞' : '🔒'" class="sm:hidden"></span>
                    <span class="hidden sm:inline" x-text="nsfwEnabled ? '🔞 NSFW ON' : '🔒 NSFW OFF'"></span>
                </button>

                {{-- My Prompts (auth only) --}}
                @auth
                <a href="/my-prompts"
                   class="flex items-center gap-1 px-2 sm:px-3 py-1.5 rounded-md bg-slate-800 border border-slate-700 text-slate-300 hover:border-brand-500 hover:text-white transition-all">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    <span class="hidden sm:inline text-xs font-medium">My Prompts</span>
                </a>
                @endauth

                {{-- Clear (desktop only; mobile has it in the expanded sheet) --}}
                <button
                    @click="clearAll()"
                    x-show="Object.keys(selected).length > 0"
                    x-transition
                    class="hidden sm:block px-3 py-1.5 rounded-md bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-medium transition-colors border border-slate-600"
                >
                    {{ __('ui.clear_all') }}
                </button>
            </div>
        </div>
    </header>

    {{-- ─────────────────────────────────────────────────────────────────────
         MOBILE: horizontal section tabs (hidden on lg+)
    ─────────────────────────────────────────────────────────────────────── --}}
    <div class="lg:hidden shrink-0 flex gap-1.5 px-3 py-2 overflow-x-auto border-b border-slate-800 bg-slate-900/50" style="-webkit-overflow-scrolling:touch;scrollbar-width:none;">
        @foreach(['character','pose','outfit','scene'] as $sec)
        <button
            @click="switchSection('{{ $sec }}')"
            :class="activeSection === '{{ $sec }}'
                ? 'bg-brand-600 text-white border-brand-600'
                : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-white'"
            class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-semibold whitespace-nowrap transition-all"
        >
            {{ __('ui.sections.' . $sec) }}
            <span
                x-show="countBySection['{{ $sec }}'] > 0"
                x-text="countBySection['{{ $sec }}']"
                class="text-[10px] font-bold bg-white/20 rounded-full px-1.5 leading-none"
            ></span>
        </button>
        @endforeach
    </div>

    {{-- ─────────────────────────────────────────────────────────────────────
         MAIN ROW
    ─────────────────────────────────────────────────────────────────────── --}}
    <div class="flex-1 flex min-h-0">

        {{-- DESKTOP LEFT: section sidebar (hidden below lg) --}}
        <nav class="hidden lg:flex shrink-0 w-44 border-r border-slate-800 bg-slate-900/50 flex-col py-3 px-2 gap-0.5 overflow-y-auto">
            @foreach(['character','pose','outfit','scene'] as $sec)
            <button
                @click="switchSection('{{ $sec }}')"
                :class="activeSection === '{{ $sec }}'
                    ? 'bg-brand-600 text-white shadow'
                    : 'text-slate-400 hover:text-slate-200 hover:bg-white/6'"
                class="flex items-center justify-between w-full px-3 py-2.5 rounded-lg text-sm font-semibold transition-all text-left"
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
                <p class="text-[10px] text-slate-600 uppercase tracking-wider mb-1">Total</p>
                <p class="text-2xl font-bold text-brand-400" x-text="Object.keys(selected).length"></p>
            </div>
        </nav>

        {{-- CENTER: tag browser --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            {{-- Search --}}
            <div class="shrink-0 bg-slate-950/95 backdrop-blur border-b border-slate-800 px-3 sm:px-4 py-2">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        x-model.debounce.250ms="searchQuery"
                        @input="filterTags()"
                        placeholder="{{ __('ui.search_tags') }}"
                        class="w-full bg-slate-800/60 border border-slate-700/60 rounded-lg pl-9 pr-8 py-2 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/50 transition-colors"
                    >
                    <button x-show="searchQuery.length > 0" @click="searchQuery = ''; filterTags()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">✕</button>
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

            {{-- Tag groups --}}
            <div x-show="!loading" class="flex-1 overflow-y-auto divide-y divide-slate-800/50">
                <template x-for="(group, subsection) in groupedTags" :key="subsection">
                    <div x-data="{ open: true, showAll: false }">
                        <button
                            @click="open = !open"
                            class="flex items-center justify-between w-full px-3 sm:px-4 py-2.5 text-left hover:bg-white/3 transition-colors group"
                        >
                            <div class="flex items-center gap-2">
                                <svg :class="open ? 'rotate-90' : ''" class="w-3 h-3 text-slate-600 group-hover:text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                        <div x-show="open" class="px-3 sm:px-4 pb-3 pt-1">
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="tag in (showAll ? group : group.slice(0, 30))" :key="tag.name">
                                    <button
                                        @click="toggleTag(tag)"
                                        :class="tagPillClass(tag)"
                                        :title="formatPostCount(tag.post_count)"
                                        class="tag-pill"
                                    >
                                        <span x-text="tag.name"></span>
                                        <span x-show="tag.is_nsfw && !selected[tag.name]" class="text-[9px] opacity-50 ml-0.5">18+</span>
                                    </button>
                                </template>
                            </div>
                            <div x-show="group.length > 30" class="mt-2">
                                <button
                                    @click="showAll = !showAll"
                                    class="text-xs text-slate-500 hover:text-brand-400 transition-colors font-medium"
                                    x-text="showAll
                                        ? '{{ __('ui.show_less') }}'
                                        : '{{ __('ui.show_more', ['count' => ':count']) }}'.replace(':count', group.length - 30)"
                                ></button>
                            </div>
                            <p x-show="group.length === 0" class="text-xs text-slate-600 py-1">{{ __('ui.no_tags_found') }}</p>
                        </div>
                    </div>
                </template>

                <div x-show="Object.keys(groupedTags).length === 0 && !loading" class="py-16 text-center text-slate-500 text-sm">
                    {{ __('ui.no_tags_found') }}
                </div>

                {{-- Extra padding on mobile so content isn't hidden behind the bottom bar --}}
                <div class="lg:hidden h-2"></div>
            </div>
        </div>

        {{-- DESKTOP RIGHT: prompt panel (hidden below lg) --}}
        <aside class="hidden lg:flex shrink-0 w-72 border-l border-slate-800 bg-slate-900/40 flex-col">

            <div class="shrink-0 flex items-center justify-between px-4 py-2.5 border-b border-slate-800">
                <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider">{{ __('ui.prompt_label') }}</h2>
                <div class="flex items-center gap-1.5">
                    <button
                        @click="copyPrompt()"
                        :disabled="Object.keys(selected).length === 0"
                        :class="Object.keys(selected).length === 0 ? 'opacity-30 cursor-not-allowed' : 'hover:text-brand-400 hover:bg-slate-700'"
                        :title="copied ? '{{ __('ui.copied') }}' : '{{ __('ui.copy_prompt') }}'"
                        class="p-1.5 rounded-md text-slate-400 transition-all"
                    >
                        <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                        <svg x-show="copied" class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                    <button
                        @click="openSave()"
                        :disabled="Object.keys(selected).length === 0"
                        :class="Object.keys(selected).length === 0 ? 'opacity-30 cursor-not-allowed bg-slate-700' : 'bg-brand-600 hover:bg-brand-700'"
                        class="flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-md text-white transition-all"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                        Save
                    </button>
                </div>
            </div>

            <div class="shrink-0 px-3 py-3 border-b border-slate-800">
                <div
                    class="min-h-[5rem] max-h-32 overflow-y-auto bg-slate-900/60 border border-slate-700/50 rounded-lg px-3 py-2.5 text-xs leading-relaxed break-all select-all"
                    :class="promptText ? 'text-slate-200' : 'text-slate-600 italic'"
                    x-text="promptText || '{{ __('ui.prompt_placeholder') }}'"
                ></div>
            </div>

            <div class="flex-1 overflow-y-auto px-3 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ __('ui.selected_tags') }}</p>
                    <span
                        x-show="Object.keys(selected).length > 0"
                        x-text="Object.keys(selected).length"
                        class="text-[10px] font-bold text-brand-400 bg-brand-600/20 rounded-full px-1.5 py-0.5"
                    ></span>
                </div>
                <div class="flex flex-wrap gap-1">
                    <template x-for="(info, tag) in selected" :key="tag">
                        <span
                            :class="info.is_nsfw ? 'bg-rose-900/40 text-rose-300 border-rose-800/60' : 'bg-brand-600/20 text-brand-300 border-brand-700/40'"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border"
                        >
                            <span x-text="tag"></span>
                            <button @click="removeTag(tag)" class="text-[9px] opacity-60 hover:opacity-100 hover:text-red-400 ml-0.5">✕</button>
                        </span>
                    </template>
                </div>
            </div>
        </aside>
    </div>

    {{-- ─────────────────────────────────────────────────────────────────────
         MOBILE: bottom prompt bar (hidden on lg+)
    ─────────────────────────────────────────────────────────────────────── --}}
    <div class="lg:hidden shrink-0 border-t border-slate-800 bg-slate-900/95 backdrop-blur">

        {{-- Collapsed bar (always visible on mobile) --}}
        <div
            x-show="!promptOpen"
            class="flex items-center gap-2 px-3 py-2.5"
            @click="if(Object.keys(selected).length > 0) promptOpen = true"
            :class="Object.keys(selected).length > 0 ? 'cursor-pointer' : ''"
        >
            <div class="flex-1 min-w-0">
                <span x-show="Object.keys(selected).length === 0" class="text-xs text-slate-600">Select tags to build your prompt</span>
                <div x-show="Object.keys(selected).length > 0" class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-brand-400 whitespace-nowrap" x-text="Object.keys(selected).length + ' tag' + (Object.keys(selected).length !== 1 ? 's' : '')"></span>
                    <span class="text-[11px] text-slate-500 truncate" x-text="promptText"></span>
                </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button
                    x-show="Object.keys(selected).length > 0"
                    @click.stop="copyPrompt()"
                    class="p-2 rounded-lg text-slate-400 hover:text-brand-400 hover:bg-slate-800 transition-all active:scale-95"
                    title="Copy"
                >
                    <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                    </svg>
                    <svg x-show="copied" class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
                <button
                    x-show="Object.keys(selected).length > 0"
                    @click.stop="openSave()"
                    class="px-3 py-1.5 rounded-lg bg-brand-600 hover:bg-brand-700 active:scale-95 text-white text-xs font-semibold transition-all"
                >Save</button>
                <button
                    x-show="Object.keys(selected).length > 0"
                    @click.stop="promptOpen = true"
                    class="p-2 text-slate-500 hover:text-slate-300"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Expanded sheet --}}
        <div
            x-show="promptOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-3"
            style="display:none"
            class="flex flex-col max-h-[60vh]"
        >
            {{-- Sheet header --}}
            <div class="shrink-0 flex items-center justify-between px-3 py-2.5 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Your Prompt</span>
                    <span x-text="Object.keys(selected).length" class="text-[10px] font-bold text-brand-400 bg-brand-600/20 rounded-full px-1.5 py-0.5 leading-none"></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <button @click="clearAll()" x-show="Object.keys(selected).length > 0" class="px-2.5 py-1 text-[11px] text-slate-400 hover:text-red-400 border border-slate-700 rounded-md transition-colors">Clear</button>
                    <button @click="copyPrompt()" x-show="Object.keys(selected).length > 0" class="p-1.5 text-slate-400 hover:text-brand-400 transition-colors">
                        <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                        <svg x-show="copied" class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                    <button @click="openSave()" x-show="Object.keys(selected).length > 0" class="px-3 py-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-lg transition-colors">Save</button>
                    <button @click="promptOpen = false" class="p-1.5 text-slate-500 hover:text-slate-300 ml-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Prompt text --}}
            <div class="shrink-0 px-3 py-3 border-b border-slate-800">
                <div
                    class="bg-slate-900/60 border border-slate-700/50 rounded-lg px-3 py-2.5 text-xs leading-relaxed break-all select-all max-h-24 overflow-y-auto"
                    :class="promptText ? 'text-slate-200' : 'text-slate-600 italic'"
                    x-text="promptText || '{{ __('ui.prompt_placeholder') }}'"
                ></div>
            </div>

            {{-- Selected tags --}}
            <div class="flex-1 overflow-y-auto px-3 py-3">
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="(info, tag) in selected" :key="tag">
                        <span
                            :class="info.is_nsfw ? 'bg-rose-900/40 text-rose-300 border-rose-800/60' : 'bg-brand-600/20 text-brand-300 border-brand-700/40'"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border"
                        >
                            <span x-text="tag"></span>
                            <button @click="removeTag(tag)" class="text-[9px] opacity-60 hover:opacity-100 hover:text-red-400 ml-0.5">✕</button>
                        </span>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────────────────────────────
         SAVE MODAL (bottom sheet on mobile, centered dialog on desktop)
    ─────────────────────────────────────────────────────────────────────── --}}
    <div
        x-show="saveOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="saveOpen = false"
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
            {{-- Modal header --}}
            <div class="shrink-0 flex items-center justify-between px-5 py-4 border-b border-slate-800">
                <h3 class="text-base font-bold text-white">Save Prompt</h3>
                <button @click="saveOpen = false" class="text-slate-500 hover:text-slate-300 p-1 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Error banner (siempre visible, sin scroll) --}}
            <div x-show="saveError && !saveSuccess" class="px-5 pt-3">
                <div class="flex items-start gap-2 bg-rose-500/15 border border-rose-500/40 rounded-lg px-3 py-2.5">
                    <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                    <p class="text-sm text-rose-300 leading-snug" x-text="saveError"></p>
                </div>
            </div>

            {{-- Success --}}
            <div x-show="saveSuccess" class="px-5 py-10 flex flex-col items-center gap-4 text-center">
                <div class="w-14 h-14 rounded-full bg-green-500/20 flex items-center justify-center">
                    <svg class="w-7 h-7 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <p class="text-white font-bold text-lg" x-text="saveMode === 'update' ? 'Prompt updated!' : 'Prompt saved!'"></p>
                    <p class="text-slate-400 text-sm mt-1" x-text="saveMode === 'update' ? 'Your changes have been saved.' : 'Your prompt is ready in My Prompts.'"></p>
                </div>
                <div class="flex gap-2 w-full mt-2">
                    <button
                        type="button"
                        @click="saveOpen = false; saveSuccess = false"
                        class="flex-1 py-2.5 rounded-lg border border-slate-700 text-slate-300 text-sm font-medium hover:border-slate-500 transition-all"
                    >Keep editing</button>
                    <a
                        href="/my-prompts"
                        class="flex-1 py-2.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold text-center transition-all"
                    >View in My Prompts</a>
                </div>
            </div>

            {{-- Form --}}
            <form x-show="!saveSuccess" @submit.prevent="submitSave()" class="overflow-y-auto flex-1">
                <div class="px-5 py-5 space-y-4">

                    {{-- Mode selector (solo si viene de My Prompts) --}}
                    <div x-show="loadedPromptId" class="grid grid-cols-2 gap-1.5 p-1 bg-slate-800/60 rounded-xl border border-slate-700/50">
                        <button
                            type="button"
                            @click="saveMode = 'update'; saveName = loadedPromptName"
                            :class="saveMode === 'update' ? 'bg-brand-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
                            class="py-2 rounded-lg text-xs font-semibold transition-all"
                        >Update existing</button>
                        <button
                            type="button"
                            @click="saveMode = 'new'; saveName = ''"
                            :class="saveMode === 'new' ? 'bg-brand-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
                            class="py-2 rounded-lg text-xs font-semibold transition-all"
                        >Save as new</button>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Prompt name <span class="text-rose-400">*</span></label>
                        <input
                            type="text"
                            x-model="saveName"
                            placeholder="e.g. Elegant Fantasy Character"
                            maxlength="120"
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/50 transition-colors"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Save which part?</label>
                        <div class="grid grid-cols-5 gap-1">
                            <template x-for="sec in ['full','character','pose','outfit','scene']" :key="sec">
                                <button
                                    type="button"
                                    @click="saveSection = sec"
                                    :class="saveSection === sec ? 'bg-brand-600 border-brand-500 text-white' : 'bg-slate-800 border-slate-700 text-slate-400 hover:border-slate-500'"
                                    class="py-2 rounded-md border text-[10px] font-semibold uppercase tracking-wide transition-all capitalize"
                                    x-text="sec"
                                ></button>
                            </template>
                        </div>
                        <div class="mt-2 bg-slate-800/60 border border-slate-700/50 rounded-lg px-3 py-2 text-[11px] text-slate-400 leading-relaxed break-all max-h-16 overflow-y-auto">
                            <span x-text="saveSectionPrompt || 'No tags selected for this section.'"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Result image <span class="text-slate-500 font-normal">(optional, max 1 MB)</span>
                        </label>

                        {{-- Imagen existente (solo en modo update) --}}
                        <template x-if="saveMode === 'update' && loadedImagePath && !saveImageName">
                            <div class="mb-2">
                                <div class="relative rounded-lg overflow-hidden border border-slate-700/60 bg-slate-800/60 aspect-[3/4] max-h-52 mx-auto" style="width: fit-content; min-width: 100%">
                                    <img :src="loadedImagePath" class="w-full h-full object-contain bg-slate-900">
                                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent px-2 py-1.5">
                                        <span class="text-[10px] text-slate-300 font-medium">Current image — will be kept if you don't upload a new one</span>
                                    </div>
                                </div>
                                <p class="mt-1.5 text-[10px] text-amber-400/80 leading-snug">
                                    If your prompt changed significantly, consider uploading a new result image.
                                </p>
                            </div>
                        </template>

                        <label class="flex flex-col items-center justify-center w-full h-20 border-2 border-dashed border-slate-700 rounded-lg cursor-pointer hover:border-brand-500/60 hover:bg-brand-600/5 transition-all active:scale-[.98]">
                            <template x-if="!saveImageName">
                                <div class="text-center">
                                    <svg class="w-5 h-5 text-slate-600 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-xs text-slate-500" x-text="(saveMode === 'update' && loadedImagePath) ? 'Tap to replace image' : 'Tap to choose image'"></span>
                                </div>
                            </template>
                            <template x-if="saveImageName">
                                <div class="text-center px-4">
                                    <span class="text-xs text-brand-400 font-medium block truncate" x-text="saveImageName"></span>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Tap to change</p>
                                </div>
                            </template>
                            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="handleImageUpload($event)" x-ref="imageInput">
                        </label>
                        <p x-show="saveImageError" x-text="saveImageError" class="mt-1 text-xs text-rose-400"></p>
                    </div>

                    <div x-show="saveImageName" class="bg-slate-800/60 border border-slate-700/50 rounded-lg p-3">
                        <label class="flex gap-2.5 cursor-pointer">
                            <input type="checkbox" x-model="saveDisclaimer" class="mt-0.5 shrink-0 accent-brand-500">
                            <span class="text-[11px] text-slate-400 leading-relaxed">
                                I certify that I have the rights to upload this image. Geekguayaco is not responsible for the intellectual property of uploaded images and may remove any image without notice if it violates copyright or usage policies.
                            </span>
                        </label>
                    </div>

                    <div class="flex gap-2 pb-1">
                        <button type="button" @click="saveOpen = false" class="flex-1 py-3 sm:py-2 rounded-lg border border-slate-700 text-slate-400 text-sm font-medium hover:border-slate-500 hover:text-slate-200 transition-all">Cancel</button>
                        <button
                            type="button"
                            @click="submitSave()"
                            class="flex-1 py-3 sm:py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold transition-all flex items-center justify-center gap-2 active:scale-[.98]"
                        >
                            <svg x-show="savePending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            <span x-text="savePending ? 'Saving…' : (saveMode === 'update' ? 'Update Prompt' : 'Save as New')"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
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
        promptOpen:        false,
        countBySection:    { character: 0, pose: 0, outfit: 0, scene: 0 },
        countBySubsection: {},

        // Loaded from My Prompts
        loadedPromptId:   null,
        loadedPromptName: '',
        loadedImagePath:  '',

        saveOpen:        false,
        saveMode:        'new',   // 'update' | 'new'
        saveName:        '',
        saveSection:     'full',
        saveImage:       null,
        saveImageName:   '',
        saveImageError:  '',
        saveDisclaimer:  false,
        savePending:     false,
        saveSuccess:     false,
        saveError:       '',

        get promptText() {
            return Object.keys(this.selected).join(', ');
        },

        get saveSectionPrompt() {
            if (this.saveSection === 'full') return this.promptText;
            return Object.entries(this.selected)
                .filter(([_, info]) => info.section === this.saveSection)
                .map(([tag]) => tag)
                .join(', ');
        },

        async init() {
            // Restore pending save (after login redirect)
            const pending = sessionStorage.getItem('pendingSave');
            if (pending) {
                try {
                    const data = JSON.parse(pending);
                    this.selected = data.selected || {};
                    Object.entries(this.selected).forEach(([, info]) => {
                        if (info.section)    this.countBySection[info.section]    = (this.countBySection[info.section] || 0) + 1;
                        if (info.subsection) this.countBySubsection[info.subsection] = (this.countBySubsection[info.subsection] || 0) + 1;
                    });
                    sessionStorage.removeItem('pendingSave');
                    this.$nextTick(() => { this.saveOpen = true; });
                } catch (_) {}
            }

            // Pre-load prompt from My Prompts "Open in Builder"
            const builderLoad = sessionStorage.getItem('builderLoad');
            if (builderLoad) {
                try {
                    const { names, promptId, promptName, imagePath } = JSON.parse(builderLoad);
                    sessionStorage.removeItem('builderLoad');
                    if (names) await this.loadFromNames(names);
                    if (promptId) {
                        this.loadedPromptId   = promptId;
                        this.loadedPromptName = promptName || '';
                        this.loadedImagePath  = imagePath  || '';
                    }
                } catch (_) {}
            }

            this.loadSection(this.activeSection);
        },

        async loadFromNames(names) {
            try {
                const res  = await fetch('/api/tags/resolve?names=' + encodeURIComponent(names));
                const tags = await res.json();
                tags.forEach(tag => {
                    this.selected = {
                        ...this.selected,
                        [tag.name]: { is_nsfw: tag.is_nsfw, section: tag.section, subsection: tag.subsection }
                    };
                    this.countBySection[tag.section]    = (this.countBySection[tag.section] || 0) + 1;
                    this.countBySubsection[tag.subsection] = (this.countBySubsection[tag.subsection] || 0) + 1;
                });
            } catch (_) {}
        },

        async loadSection(section) {
            this.loading     = true;
            this.groupedTags = {};
            this.searchQuery = '';
            try {
                const params = new URLSearchParams({ section, nsfw: this.nsfwEnabled ? '1' : '0' });
                const res    = await fetch('/api/tags?' + params);
                this.allTags = await res.json();
                this.applyFilter();
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        applyFilter() {
            const q      = this.searchQuery.toLowerCase().trim();
            const tags   = q ? this.allTags.filter(t => t.name.includes(q)) : this.allTags;
            const groups = {};
            tags.forEach(tag => {
                if (!groups[tag.subsection]) groups[tag.subsection] = [];
                groups[tag.subsection].push(tag);
            });
            this.groupedTags = groups;
        },

        filterTags() { this.applyFilter(); },

        switchSection(section) {
            this.activeSection = section;
            this.promptOpen    = false;
            this.loadSection(section);
        },

        toggleTag(tag) {
            if (this.selected[tag.name]) {
                this.removeTag(tag.name);
            } else {
                this.selected = {
                    ...this.selected,
                    [tag.name]: { is_nsfw: tag.is_nsfw, section: this.activeSection, subsection: tag.subsection }
                };
                this.countBySection[this.activeSection]++;
                this.countBySubsection[tag.subsection] = (this.countBySubsection[tag.subsection] || 0) + 1;
            }
        },

        removeTag(name) {
            if (!this.selected[name]) return;
            const info = this.selected[name];
            const copy = { ...this.selected };
            delete copy[name];
            this.selected = copy;
            if (this.countBySection[info.section] > 0)              this.countBySection[info.section]--;
            if ((this.countBySubsection[info.subsection] || 0) > 0) this.countBySubsection[info.subsection]--;
        },

        clearAll() {
            this.selected          = {};
            this.countBySection    = { character: 0, pose: 0, outfit: 0, scene: 0 };
            this.countBySubsection = {};
            this.promptOpen        = false;
        },

        async copyPrompt() {
            if (!this.promptText) return;
            await navigator.clipboard.writeText(this.promptText);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },

        openSave() {
            if (Object.keys(this.selected).length === 0) return;
            if (!window.__auth) {
                sessionStorage.setItem('pendingSave', JSON.stringify({ selected: this.selected }));
                window.location = '/login';
                return;
            }
            this.saveMode       = this.loadedPromptId ? 'update' : 'new';
            this.saveName       = this.loadedPromptId ? this.loadedPromptName : '';
            this.saveSection    = 'full';
            this.saveImage      = null;
            this.saveImageName  = '';
            this.saveImageError = '';
            this.saveDisclaimer = false;
            this.savePending    = false;
            this.saveError      = '';
            this.saveSuccess    = false;
            this.saveOpen       = true;
        },

        handleImageUpload(e) {
            const file = e.target.files[0];
            if (!file) { this.saveImage = null; this.saveImageName = ''; return; }
            if (file.size > 1024 * 1024) {
                this.saveImageError = 'Image must be under 1 MB.';
                e.target.value = '';
                this.saveImage = null; this.saveImageName = '';
                return;
            }
            const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if (!allowed.includes(file.type)) {
                this.saveImageError = 'Only JPEG, PNG, GIF or WebP are allowed.';
                e.target.value = '';
                this.saveImage = null; this.saveImageName = '';
                return;
            }
            this.saveImageError = '';
            this.saveImage      = file;
            this.saveImageName  = file.name;
        },

        async submitSave() {
            this.savePending = false;
            this.saveError = '';
            if (!this.saveName.trim()) {
                this.saveError = 'Please enter a name for the prompt.';
                return;
            }
            if (this.saveImageName && !this.saveDisclaimer) {
                this.saveError = 'Please accept the image disclaimer to continue.';
                return;
            }
            if (!this.saveSectionPrompt.trim()) {
                this.saveError = 'No tags selected for the chosen section.';
                return;
            }

            this.savePending = true;

            const fd = new FormData();
            fd.append('_token',      window.__csrf);
            fd.append('name',        this.saveName.trim());
            fd.append('section',     this.saveSection);
            fd.append('prompt_text', this.saveSectionPrompt);
            if (this.saveImage)     fd.append('image',      this.saveImage);
            if (this.saveImageName) fd.append('disclaimer', '1');

            let url;
            if (this.saveMode === 'update' && this.loadedPromptId) {
                url = '/prompts/' + this.loadedPromptId;
                fd.append('_method', 'PATCH');
            } else {
                url = '/prompts';
            }

            try {
                const res  = await fetch(url, {
                    method:  'POST',
                    headers: { 'Accept': 'application/json' },
                    body:    fd,
                });
                if (res.ok) {
                    if (this.saveMode === 'update') {
                        this.loadedPromptName = this.saveName.trim();
                        const data = await res.json().catch(() => ({}));
                        if (data.image_path) this.loadedImagePath = '/storage/' + data.image_path;
                    }
                    this.saveSuccess = true;
                } else {
                    const data = await res.json().catch(() => ({}));
                    this.saveError = '[' + res.status + '] ' + (data.message || JSON.stringify(data).slice(0, 120) || 'Could not save.');
                }
            } catch (e) {
                this.saveError = 'Network error: ' + e.message;
            } finally {
                this.savePending = false;
            }
        },

        tagPillClass(tag) {
            const sel = !!this.selected[tag.name];
            if (tag.is_nsfw) return sel ? 'tag-pill-nsfw-selected' : 'tag-pill-nsfw';
            return sel ? 'tag-pill-selected' : 'tag-pill-default';
        },

        subsectionLabel(key) {
            return SUBSECTION_LABELS[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
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
