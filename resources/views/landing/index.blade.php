<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Geekguayaco — Danbooru Prompt Builder</title>
  <meta name="description" content="Visual prompt builder for AI image generation. Browse 2,700+ Danbooru tags by character, pose, outfit and scene. Free tool, no sign-up required.">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    @keyframes float {
      0%,100% { transform: translateY(0); }
      50%      { transform: translateY(18px); }
    }
    .blob { animation: float 8s ease-in-out infinite; }
    .blob-2 { animation: float 10s ease-in-out infinite 2s; }
    .feature-card { transition: transform .2s, border-color .2s; }
    .feature-card:hover { transform: translateY(-4px); border-color: rgba(99,102,241,.5); }
    .gallery-card { transition: transform .2s, box-shadow .2s; }
    .gallery-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(99,102,241,.25); }
  </style>
</head>
<body class="bg-[#0a0e27] text-slate-200 antialiased" style="font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">

{{-- ── NAVIGATION ──────────────────────────────────────────────────────── --}}
<nav
  x-data="{ open: false }"
  class="sticky top-0 z-50 border-b border-indigo-900/30 bg-[#0a0e27]/95 backdrop-blur-md"
>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12">
    <div class="flex items-center justify-between h-14 sm:h-16">

      {{-- Logo --}}
      <a href="/" class="flex items-center gap-1.5 text-lg sm:text-xl font-bold tracking-tight text-white hover:opacity-90 transition-opacity">
        <span class="bg-gradient-to-br from-indigo-400 to-purple-500 bg-clip-text text-transparent">⚡</span>
        Geekguayaco
      </a>

      {{-- Desktop nav --}}
      <div class="hidden md:flex items-center gap-6 lg:gap-8">
        <a href="#features" class="text-sm text-indigo-300 hover:text-white transition-colors">Features</a>
        <a href="#gallery"  class="text-sm text-indigo-300 hover:text-white transition-colors">Gallery</a>
        <a href="#community" class="text-sm text-indigo-300 hover:text-white transition-colors">Community</a>
      </div>

      <div class="hidden md:flex items-center gap-3">
        @auth
          <span class="text-sm text-indigo-300">Hi, {{ auth()->user()->name }}</span>
          <a href="/builder" class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-semibold hover:opacity-90 transition-opacity">Open Builder</a>
          <form method="POST" action="/logout">@csrf
            <button type="submit" class="px-4 py-2 rounded-lg border border-indigo-500/40 text-indigo-300 text-sm font-medium hover:bg-indigo-500/10 transition-colors">Log Out</button>
          </form>
        @else
          <a href="/login"    class="px-4 py-2 rounded-lg border border-indigo-500 text-indigo-400 text-sm font-medium hover:bg-indigo-500/10 transition-colors">Log In</a>
          <a href="/register" class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-semibold hover:opacity-90 transition-opacity">Sign Up</a>
        @endauth
      </div>

      {{-- Mobile: auth CTA + hamburger --}}
      <div class="flex md:hidden items-center gap-2">
        @auth
          <a href="/builder" class="px-3 py-1.5 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-semibold">Builder</a>
        @else
          <a href="/register" class="px-3 py-1.5 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-semibold">Sign Up</a>
        @endauth

        <button @click="open = !open" class="p-2 rounded-lg text-indigo-300 hover:text-white hover:bg-white/5 transition-colors" aria-label="Menu">
          <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          <svg x-show="open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>
  </div>

  {{-- Mobile menu --}}
  <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="md:hidden border-t border-indigo-900/30 bg-[#0a0e27]/98 px-4 py-4 space-y-1">
    <a href="#features"  @click="open=false" class="block px-3 py-2.5 rounded-lg text-sm text-indigo-300 hover:text-white hover:bg-white/5 transition-colors">Features</a>
    <a href="#gallery"   @click="open=false" class="block px-3 py-2.5 rounded-lg text-sm text-indigo-300 hover:text-white hover:bg-white/5 transition-colors">Gallery</a>
    <a href="#community" @click="open=false" class="block px-3 py-2.5 rounded-lg text-sm text-indigo-300 hover:text-white hover:bg-white/5 transition-colors">Community</a>
    <div class="pt-2 border-t border-indigo-900/30 flex flex-col gap-2">
      @auth
        <a href="/builder" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-center bg-gradient-to-r from-indigo-500 to-purple-600 text-white">Open Builder</a>
        <form method="POST" action="/logout">@csrf
          <button type="submit" class="w-full px-3 py-2.5 rounded-lg text-sm text-indigo-300 border border-indigo-500/30 hover:bg-white/5 transition-colors">Log Out</button>
        </form>
      @else
        <a href="/login"    class="block px-3 py-2.5 rounded-lg text-sm text-center border border-indigo-500 text-indigo-400 hover:bg-indigo-500/10 transition-colors">Log In</a>
        <a href="/register" class="block px-3 py-2.5 rounded-lg text-sm text-center font-medium bg-gradient-to-r from-indigo-500 to-purple-600 text-white">Sign Up Free</a>
      @endauth
    </div>
  </div>
</nav>

{{-- ── HERO ──────────────────────────────────────────────────────────────── --}}
<section class="relative overflow-hidden px-4 py-16 sm:py-24 lg:py-32 text-center bg-gradient-to-br from-indigo-950/40 to-purple-950/30">

  {{-- decorative blobs --}}
  <div class="blob pointer-events-none absolute -top-24 -right-16 w-64 h-64 sm:w-96 sm:h-96 rounded-full bg-gradient-radial from-indigo-600/20 to-transparent opacity-60"></div>
  <div class="blob-2 pointer-events-none absolute -bottom-16 -left-12 w-52 h-52 sm:w-80 sm:h-80 rounded-full bg-gradient-radial from-purple-600/15 to-transparent opacity-60"></div>

  <div class="relative z-10 max-w-3xl mx-auto">
    <div class="inline-block px-3 py-1 mb-5 rounded-full border border-indigo-500/30 bg-indigo-500/10 text-indigo-300 text-xs sm:text-sm font-medium">
      🎨 Free tool
    </div>

    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-4 sm:mb-6 bg-gradient-to-br from-slate-100 to-indigo-300 bg-clip-text text-transparent">
      Build Epic Prompts<br>for AI Image Generation
    </h1>

    <p class="text-base sm:text-lg lg:text-xl text-slate-400 max-w-2xl mx-auto mb-8 sm:mb-10">
      Browse <strong class="text-indigo-300">{{ number_format($totalTags) }}+ Danbooru tags</strong> organized by character, pose, outfit and scene. Click to build — copy and generate.
    </p>

    <div class="flex flex-col sm:flex-row gap-3 justify-center mb-10 sm:mb-14">
      <a href="/builder" class="px-7 py-3 sm:py-3.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold text-base hover:opacity-90 active:scale-95 transition-all shadow-lg shadow-indigo-900/40">
        Start Building Now →
      </a>
      @guest
      <a href="/register" class="px-7 py-3 sm:py-3.5 rounded-xl border border-indigo-500/40 text-indigo-300 font-medium text-base hover:bg-indigo-500/10 transition-colors">
        Create Free Account
      </a>
      @endguest
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-2 sm:gap-4 max-w-lg mx-auto">
      <div class="p-3 sm:p-5 bg-indigo-600/10 border border-indigo-600/20 rounded-xl">
        <div class="text-xl sm:text-3xl font-bold text-indigo-400">{{ number_format($totalTags) }}+</div>
        <div class="text-[11px] sm:text-sm text-indigo-300 mt-0.5 sm:mt-1">Tags</div>
      </div>
      <div class="p-3 sm:p-5 bg-purple-600/10 border border-purple-600/20 rounded-xl">
        <div class="text-xl sm:text-3xl font-bold text-purple-400">∞</div>
        <div class="text-[11px] sm:text-sm text-purple-300 mt-0.5 sm:mt-1">Combinations</div>
      </div>
      <div class="p-3 sm:p-5 bg-indigo-600/10 border border-indigo-600/20 rounded-xl">
        <div class="text-xl sm:text-3xl font-bold text-indigo-400">100%</div>
        <div class="text-[11px] sm:text-sm text-indigo-300 mt-0.5 sm:mt-1">Free</div>
      </div>
    </div>
  </div>
</section>

{{-- ── FEATURES ──────────────────────────────────────────────────────────── --}}
<section id="features" class="bg-[#0f1535] px-4 py-14 sm:py-20 lg:py-24">
  <div class="max-w-6xl mx-auto">
    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-center text-slate-100 mb-2 sm:mb-3">Why Geekguayaco?</h2>
    <p class="text-center text-indigo-300 text-sm sm:text-base mb-10 sm:mb-14">Everything you need to craft the perfect prompt, in one place.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      @foreach([
        ['🎨', 'Visual Tag Browser', 'Click tags to add them. See post counts so you know how popular each one is. No memorizing syntax.', 'indigo'],
        ['💾', 'Save Your Prompts', 'Create a free account and save prompts — the full build or by section: character, outfit, pose or scene.', 'purple'],
        ['⚡', 'Instant Search', 'Find any tag in milliseconds with live filtering across all 52 subsections.', 'indigo'],
        ['🖼️', 'Share with Image', 'Upload the image your prompt generated and share it publicly so the community can like and copy it.', 'purple'],
        ['🔞', 'Full NSFW Support', 'Toggle adult content on or off with one click. All Danbooru tag categories are available.', 'indigo'],
        ['🤝', 'Community Gallery', 'Discover prompts from other users, like your favorites and copy them to the builder in one click.', 'purple'],
      ] as [$icon, $title, $body, $color])
      <div class="feature-card p-5 sm:p-7 rounded-2xl border
        {{ $color === 'purple'
            ? 'bg-gradient-to-br from-purple-900/20 to-purple-900/5 border-purple-800/25'
            : 'bg-gradient-to-br from-indigo-900/20 to-indigo-900/5 border-indigo-800/25' }}">
        <div class="text-3xl mb-3">{{ $icon }}</div>
        <h3 class="text-base sm:text-lg font-semibold text-slate-100 mb-2">{{ $title }}</h3>
        <p class="text-sm text-slate-400 leading-relaxed">{{ $body }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ── COMMUNITY GALLERY (prompts públicos reales) ───────────────────────── --}}
<section id="community" class="px-4 py-14 sm:py-20 lg:py-24 bg-gradient-to-br from-indigo-950/40 to-purple-950/30">
  <div class="max-w-6xl mx-auto">
    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-center text-slate-100 mb-2 sm:mb-3">Community Gallery</h2>
    <p class="text-center text-indigo-300 text-sm sm:text-base mb-10 sm:mb-14">Top prompts shared by our users</p>

    @if($publicPrompts->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
      @foreach($publicPrompts as $p)
      <div class="gallery-card bg-indigo-950/40 border border-indigo-900/40 rounded-2xl overflow-hidden cursor-pointer flex flex-col">
        @if($p->image_path)
        <div class="aspect-video bg-slate-800 overflow-hidden">
          <img src="/storage/{{ $p->image_path }}" alt="{{ $p->name }}" class="w-full h-full object-cover" loading="lazy">
        </div>
        @else
        <div class="aspect-video bg-gradient-to-br from-indigo-900/40 to-purple-900/20 flex items-center justify-center text-4xl">🎨</div>
        @endif
        <div class="flex-1 flex flex-col p-3 sm:p-4">
          <h3 class="text-sm font-semibold text-slate-200 mb-1 line-clamp-1">{{ $p->name }}</h3>
          <p class="text-xs text-slate-500 mb-2 leading-relaxed line-clamp-2">{{ $p->prompt_text }}</p>
          @if($p->user)
          <p class="text-[11px] text-indigo-400 mb-2">by {{ $p->user->name }}</p>
          @endif
          <div class="mt-auto flex items-center justify-between">
            <span class="text-xs text-slate-600">♥ {{ $p->likes_count }}</span>
            <button
              onclick="navigator.clipboard.writeText({{ json_encode($p->prompt_text) }}); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy',2000)"
              class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:opacity-90 active:scale-95 transition-all"
            >Copy</button>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    <div class="text-center mt-8 sm:mt-10">
      <a href="/builder" class="inline-block px-7 py-3 rounded-xl border-2 border-indigo-500 text-indigo-400 font-semibold text-sm hover:bg-indigo-500/10 transition-colors">
        Open Builder →
      </a>
    </div>

    @else
    {{-- Placeholder hasta que haya prompts públicos --}}
    <div id="communityContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5"></div>
    <div class="text-center mt-8 sm:mt-10">
      <a href="/builder" class="inline-block px-7 py-3 rounded-xl border-2 border-indigo-500 text-indigo-400 font-semibold text-sm hover:bg-indigo-500/10 transition-colors">
        Be the first to share →
      </a>
    </div>
    @endif
  </div>
</section>

{{-- ── CTA ───────────────────────────────────────────────────────────────── --}}
<section class="px-4 py-14 sm:py-20 lg:py-24 bg-gradient-to-br from-indigo-600 to-purple-700 text-center">
  <div class="max-w-2xl mx-auto">
    <h2 class="text-2xl sm:text-4xl font-bold text-white mb-3 sm:mb-4">Ready to Create?</h2>
    <p class="text-sm sm:text-lg text-white/85 mb-7 sm:mb-8">Jump into the builder — it's free, no sign-up required to start.</p>
    <a href="/builder" class="inline-block px-8 py-3 sm:py-3.5 rounded-xl bg-white text-indigo-600 font-bold text-sm sm:text-base hover:opacity-90 active:scale-95 transition-all shadow-lg">
      Start Building Free →
    </a>
  </div>
</section>

{{-- ── FOOTER ────────────────────────────────────────────────────────────── --}}
<footer class="bg-[#050812] border-t border-indigo-900/20 px-4 py-8 sm:py-10 text-center text-slate-600">
  <p class="text-sm mb-4">© {{ date('Y') }} Geekguayaco. Made with ❤️ for the AI art community.</p>
  <div class="flex flex-wrap gap-4 sm:gap-6 justify-center text-sm">
    <a href="#" class="text-indigo-400/70 hover:text-indigo-300 transition-colors">Privacy</a>
    <a href="#" class="text-indigo-400/70 hover:text-indigo-300 transition-colors">Terms</a>
    <a href="#" class="text-indigo-400/70 hover:text-indigo-300 transition-colors">Contact</a>
  </div>
</footer>

<script>
// Placeholder community cards when no public prompts exist yet
const placeholderItems = [
  { title: 'Neon Cyberpunk',   tags: '1girl, cyberpunk, neon_lights, city, rain',    likes: 0 },
  { title: 'Fantasy Queen',    tags: '1girl, crown, throne_room, castle, elegant',   likes: 0 },
  { title: 'Cozy School Day',  tags: '1girl, school_uniform, sitting, smile, classroom', likes: 0 },
  { title: 'Epic Action',      tags: '1girl, katana, dynamic_pose, battle, dark_fantasy', likes: 0 },
];
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('communityContainer');
  if (!el) return;
  el.innerHTML = placeholderItems.map(item => {
    const escaped = item.tags.replace(/'/g, "\\'");
    return `<div class="gallery-card bg-indigo-950/40 border border-purple-900/40 rounded-2xl overflow-hidden">
      <div class="aspect-video bg-gradient-to-br from-purple-900/40 to-indigo-900/20 flex items-center justify-center text-4xl">🌟</div>
      <div class="p-3 sm:p-4">
        <h3 class="text-sm font-semibold text-slate-200 mb-1">${item.title}</h3>
        <p class="text-xs text-slate-500 mb-2 leading-relaxed line-clamp-2">${item.tags}</p>
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-600">♥ example</span>
          <button onclick="navigator.clipboard.writeText('${escaped}');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',2000)"
                  class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gradient-to-r from-purple-600 to-pink-600 text-white hover:opacity-90 transition-all">Copy</button>
        </div>
      </div>
    </div>`;
  }).join('');
});
</script>

</body>
</html>
