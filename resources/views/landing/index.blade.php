<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Geekguayaco — Danbooru Prompt Builder</title>
  <meta name="description" content="The most intuitive visual prompt builder for AI image generation. Browse 2,700+ Danbooru tags organized by character, pose, outfit and scene. Free tool.">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50%       { transform: translateY(20px); }
    }
    @keyframes pulse-slow {
      0%, 100% { opacity: 0.3; }
      50%       { opacity: 0.5; }
    }
    .nav-link { transition: color .2s; }
    .nav-link:hover { color: #e0e7ff; }
    .feature-card { transition: transform .2s, border-color .2s; }
    .feature-card:hover { transform: translateY(-4px); border-color: rgba(99,102,241,.5); }
    .gallery-card { transition: transform .2s, box-shadow .2s; }
    .gallery-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(99,102,241,.25); }
    .btn-primary { transition: opacity .2s, transform .2s; }
    .btn-primary:hover { opacity: .9; transform: translateY(-1px); }
    .btn-outline { transition: background .2s, color .2s; }
    .btn-outline:hover { background: rgba(99,102,241,.15); }
    .like-btn { transition: color .2s; }
    .like-btn:hover { color: #f472b6; }
  </style>
</head>
<body style="margin:0;padding:0;background:#0a0e27;color:#e0e7ff;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;line-height:1.6;">

<!-- ── NAVIGATION ──────────────────────────────────────────────────────── -->
<nav style="display:flex;justify-content:space-between;align-items:center;padding:1.25rem 3rem;border-bottom:1px solid rgba(99,102,241,.12);background:rgba(10,14,39,.95);backdrop-filter:blur(12px);position:sticky;top:0;z-index:100;">
  <a href="/" style="display:flex;align-items:center;gap:.4rem;font-size:1.4rem;font-weight:700;letter-spacing:-.5px;text-decoration:none;color:inherit;">
    <span style="background:linear-gradient(135deg,#6366f1,#a855f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">⚡</span>
    <span>Geekguayaco</span>
  </a>

  <div style="display:flex;gap:2rem;align-items:center;">
    <a href="#features" class="nav-link" style="font-size:.9rem;color:#a5b4fc;text-decoration:none;">Features</a>
    <a href="#gallery"  class="nav-link" style="font-size:.9rem;color:#a5b4fc;text-decoration:none;">Gallery</a>
    <a href="#community" class="nav-link" style="font-size:.9rem;color:#a5b4fc;text-decoration:none;">Community</a>
    {{-- Future auth --}}
    <button style="padding:.55rem 1.3rem;border:1px solid #6366f1;border-radius:6px;background:transparent;color:#6366f1;font-weight:500;cursor:pointer;font-size:.9rem;" class="btn-outline">Log In</button>
    <button style="padding:.55rem 1.3rem;border:none;border-radius:6px;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;font-weight:600;cursor:pointer;font-size:.9rem;" class="btn-primary">Sign Up</button>
  </div>
</nav>

<!-- ── HERO ────────────────────────────────────────────────────────────── -->
<section style="padding:6rem 3rem;text-align:center;background:linear-gradient(135deg,rgba(99,102,241,.07),rgba(168,85,247,.07));position:relative;overflow:hidden;">

  {{-- decorative blobs --}}
  <div style="position:absolute;width:420px;height:420px;background:radial-gradient(circle,rgba(99,102,241,.18) 0%,transparent 70%);border-radius:50%;top:-120px;right:-80px;animation:float 7s ease-in-out infinite;pointer-events:none;"></div>
  <div style="position:absolute;width:320px;height:320px;background:radial-gradient(circle,rgba(168,85,247,.12) 0%,transparent 70%);border-radius:50%;bottom:-60px;left:-60px;animation:float 9s ease-in-out infinite 1.5s;pointer-events:none;"></div>

  <div style="position:relative;z-index:2;max-width:860px;margin:0 auto;">

    <div style="display:inline-block;padding:.35rem 1rem;border:1px solid rgba(99,102,241,.35);border-radius:999px;font-size:.8rem;color:#a5b4fc;margin-bottom:1.5rem;background:rgba(99,102,241,.08);">
      🎨 Free tool — no credit card required
    </div>

    <h1 style="font-size:3.4rem;font-weight:700;line-height:1.2;margin-bottom:1.25rem;background:linear-gradient(135deg,#e0e7ff 0%,#a5b4fc 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
      Build Epic Prompts<br>for AI Image Generation
    </h1>

    <p style="font-size:1.2rem;color:#cbd5e1;max-width:680px;margin:0 auto 2.5rem;">
      Browse <strong style="color:#a5b4fc;">{{ number_format($totalTags) }}+ Danbooru tags</strong> organized by character, pose, outfit and scene. Click to build — copy and generate.
    </p>

    <div style="display:flex;gap:1rem;justify-content:center;margin-bottom:3.5rem;">
      <a href="/builder" style="padding:1rem 2.2rem;border:none;border-radius:8px;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;font-weight:600;font-size:1rem;cursor:pointer;text-decoration:none;display:inline-block;" class="btn-primary">
        Start Building Now →
      </a>
    </div>

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;max-width:680px;margin:0 auto;">
      <div style="padding:1.25rem;background:rgba(99,102,241,.1);border-radius:12px;border:1px solid rgba(99,102,241,.2);">
        <div style="font-size:2rem;font-weight:700;color:#6366f1;">{{ number_format($totalTags) }}+</div>
        <div style="font-size:.85rem;color:#a5b4fc;margin-top:.2rem;">Tags in database</div>
      </div>
      <div style="padding:1.25rem;background:rgba(168,85,247,.1);border-radius:12px;border:1px solid rgba(168,85,247,.2);">
        <div style="font-size:2rem;font-weight:700;color:#a855f7;">∞</div>
        <div style="font-size:.85rem;color:#e9d5ff;margin-top:.2rem;">Possible combinations</div>
      </div>
      <div style="padding:1.25rem;background:rgba(99,102,241,.1);border-radius:12px;border:1px solid rgba(99,102,241,.2);">
        <div style="font-size:2rem;font-weight:700;color:#6366f1;">100%</div>
        <div style="font-size:.85rem;color:#a5b4fc;margin-top:.2rem;">Free to use</div>
      </div>
    </div>

  </div>
</section>

<!-- ── FEATURES ────────────────────────────────────────────────────────── -->
<section id="features" style="padding:5rem 3rem;background:#0f1535;">
  <div style="max-width:1200px;margin:0 auto;">
    <h2 style="font-size:2.2rem;font-weight:700;text-align:center;margin-bottom:.75rem;color:#e0e7ff;">Why Geekguayaco?</h2>
    <p style="text-align:center;color:#a5b4fc;margin-bottom:3rem;font-size:1rem;">Everything you need to craft the perfect prompt, in one place.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.75rem;">

      <div class="feature-card" style="padding:2rem;background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(99,102,241,.04));border:1px solid rgba(99,102,241,.2);border-radius:14px;">
        <div style="font-size:2.2rem;margin-bottom:1rem;">🎨</div>
        <h3 style="font-size:1.15rem;font-weight:600;margin-bottom:.5rem;">Visual Tag Browser</h3>
        <p style="color:#a5b4fc;font-size:.9rem;">Click tags to add them. See post counts so you know how popular each one is. No memorizing syntax.</p>
      </div>

      <div class="feature-card" style="padding:2rem;background:linear-gradient(135deg,rgba(168,85,247,.1),rgba(168,85,247,.04));border:1px solid rgba(168,85,247,.2);border-radius:14px;">
        <div style="font-size:2.2rem;margin-bottom:1rem;">💾</div>
        <h3 style="font-size:1.15rem;font-weight:600;margin-bottom:.5rem;">Save Your Prompts</h3>
        <p style="color:#e9d5ff;font-size:.9rem;">Create a free account and save prompts — the full build or individual sections like character, outfit, pose or scene.</p>
      </div>

      <div class="feature-card" style="padding:2rem;background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(99,102,241,.04));border:1px solid rgba(99,102,241,.2);border-radius:14px;">
        <div style="font-size:2.2rem;margin-bottom:1rem;">⚡</div>
        <h3 style="font-size:1.15rem;font-weight:600;margin-bottom:.5rem;">Instant Search</h3>
        <p style="color:#a5b4fc;font-size:.9rem;">Find any tag in milliseconds with live filtering across all 52 subsections.</p>
      </div>

      <div class="feature-card" style="padding:2rem;background:linear-gradient(135deg,rgba(168,85,247,.1),rgba(168,85,247,.04));border:1px solid rgba(168,85,247,.2);border-radius:14px;">
        <div style="font-size:2.2rem;margin-bottom:1rem;">🖼️</div>
        <h3 style="font-size:1.15rem;font-weight:600;margin-bottom:.5rem;">Share with Image</h3>
        <p style="color:#e9d5ff;font-size:.9rem;">Upload the image your prompt generated and share it publicly so the community can like and copy it.</p>
      </div>

      <div class="feature-card" style="padding:2rem;background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(99,102,241,.04));border:1px solid rgba(99,102,241,.2);border-radius:14px;">
        <div style="font-size:2.2rem;margin-bottom:1rem;">🔞</div>
        <h3 style="font-size:1.15rem;font-weight:600;margin-bottom:.5rem;">Full NSFW Support</h3>
        <p style="color:#a5b4fc;font-size:.9rem;">Toggle adult content on or off with one click. All Danbooru tag categories are available.</p>
      </div>

      <div class="feature-card" style="padding:2rem;background:linear-gradient(135deg,rgba(168,85,247,.1),rgba(168,85,247,.04));border:1px solid rgba(168,85,247,.2);border-radius:14px;">
        <div style="font-size:2.2rem;margin-bottom:1rem;">🤝</div>
        <h3 style="font-size:1.15rem;font-weight:600;margin-bottom:.5rem;">Community Gallery</h3>
        <p style="color:#e9d5ff;font-size:.9rem;">Discover prompts from other users, like your favorites and copy them to the builder in one click.</p>
      </div>

    </div>
  </div>
</section>

<!-- ── SITE GALLERY ────────────────────────────────────────────────────── -->
<section id="gallery" style="padding:5rem 3rem;background:linear-gradient(135deg,rgba(99,102,241,.07),rgba(168,85,247,.07));">
  <div style="max-width:1200px;margin:0 auto;">
    <h2 style="font-size:2.2rem;font-weight:700;text-align:center;margin-bottom:.75rem;color:#e0e7ff;">Featured Gallery</h2>
    <p style="text-align:center;color:#a5b4fc;margin-bottom:3rem;font-size:1rem;">Hand-picked prompts from our team</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;" id="galleryContainer"></div>

    <div style="text-align:center;margin-top:2.5rem;">
      <a href="/builder" style="padding:.75rem 2rem;border:2px solid #6366f1;border-radius:8px;background:transparent;color:#6366f1;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;" class="btn-outline">
        Open Builder →
      </a>
    </div>
  </div>
</section>

<!-- ── COMMUNITY GALLERY ───────────────────────────────────────────────── -->
<section id="community" style="padding:5rem 3rem;background:#0f1535;">
  <div style="max-width:1200px;margin:0 auto;">
    <h2 style="font-size:2.2rem;font-weight:700;text-align:center;margin-bottom:.75rem;color:#e0e7ff;">Community Prompts</h2>
    <p style="text-align:center;color:#a5b4fc;margin-bottom:3rem;font-size:1rem;">Top-liked prompts shared by our users</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;" id="communityContainer"></div>

    <div style="text-align:center;margin-top:2.5rem;">
      <button style="padding:.75rem 2rem;border:2px solid #a855f7;border-radius:8px;background:transparent;color:#a855f7;font-weight:600;cursor:pointer;" class="btn-outline">
        Explore Community
      </button>
    </div>
  </div>
</section>

<!-- ── CTA ─────────────────────────────────────────────────────────────── -->
<section style="padding:5rem 3rem;background:linear-gradient(135deg,#6366f1,#a855f7);text-align:center;">
  <div style="max-width:680px;margin:0 auto;">
    <h2 style="font-size:2.4rem;font-weight:700;margin-bottom:1rem;color:#fff;">Ready to Create?</h2>
    <p style="font-size:1.1rem;color:rgba(255,255,255,.88);margin-bottom:2rem;">Jump into the builder — it's free, no sign-up required to start.</p>
    <a href="/builder" style="padding:1rem 2.5rem;border:none;border-radius:8px;background:#fff;color:#6366f1;font-weight:700;font-size:1rem;cursor:pointer;text-decoration:none;display:inline-block;" class="btn-primary">
      Start Building Free →
    </a>
  </div>
</section>

<!-- ── FOOTER ──────────────────────────────────────────────────────────── -->
<footer style="padding:2.5rem 3rem;background:#050812;border-top:1px solid rgba(99,102,241,.1);text-align:center;color:#64748b;">
  <p style="margin-bottom:1rem;">© {{ date('Y') }} Geekguayaco. Made with ❤️ for the AI art community.</p>
  <div style="display:flex;gap:2rem;justify-content:center;font-size:.9rem;">
    <a href="#" style="color:#a5b4fc;text-decoration:none;" class="nav-link">Privacy</a>
    <a href="#" style="color:#a5b4fc;text-decoration:none;" class="nav-link">Terms</a>
    <a href="#" style="color:#a5b4fc;text-decoration:none;" class="nav-link">Contact</a>
  </div>
</footer>

<script>
// ── Sample gallery data (will be replaced by real DB data) ────────────────
const galleryItems = [
  { title: 'Elegant Mystery',    tags: '1girl, long_hair, purple_eyes, evening_gown, moonlight',  likes: 234 },
  { title: 'Epic Action',        tags: '1girl, katana, dynamic_pose, battle, dark_fantasy',        likes: 189 },
  { title: 'Cozy School Day',    tags: '1girl, school_uniform, sitting, smile, classroom',         likes: 156 },
  { title: 'Fantasy Sorceress',  tags: '1girl, magic_circle, glowing, wizard_hat, forest',        likes: 312 },
];

const communityItems = [
  { title: 'Neon Cyberpunk',   tags: '1girl, cyberpunk, neon_lights, city, rain',       likes: 542, author: 'NeonArtist'    },
  { title: 'Vintage Portrait', tags: '1girl, vintage, soft_lighting, dress, garden',    likes: 423, author: 'RetroCreative' },
  { title: 'Fantasy Queen',    tags: '1girl, crown, throne_room, castle, elegant',      likes: 678, author: 'FantasyMaster' },
  { title: 'Casual & Cute',    tags: '1girl, hoodie, smile, park, cherry_blossoms',     likes: 501, author: 'CuteCollector' },
];

function galleryCard(item) {
  return `
    <div class="gallery-card" style="padding:1.25rem;background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.18);border-radius:14px;cursor:pointer;">
      <div style="width:100%;height:180px;background:linear-gradient(135deg,rgba(99,102,241,.25),rgba(168,85,247,.15));border-radius:10px;margin-bottom:1rem;display:flex;align-items:center;justify-content:center;font-size:2.5rem;">🎨</div>
      <h3 style="font-size:1rem;font-weight:600;margin-bottom:.4rem;color:#e0e7ff;">${item.title}</h3>
      <p style="font-size:.8rem;color:#a5b4fc;margin-bottom:1rem;line-height:1.5;">${item.tags}</p>
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <span class="like-btn" style="color:#64748b;font-size:.85rem;cursor:pointer;">♥ ${item.likes}</span>
        <button onclick="navigator.clipboard.writeText('${item.tags}')" style="padding:.45rem .9rem;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;border:none;border-radius:6px;font-size:.78rem;cursor:pointer;font-weight:600;">Copy</button>
      </div>
    </div>`;
}

function communityCard(item) {
  return `
    <div class="gallery-card" style="padding:1.25rem;background:rgba(168,85,247,.08);border:1px solid rgba(168,85,247,.18);border-radius:14px;cursor:pointer;">
      <div style="width:100%;height:180px;background:linear-gradient(135deg,rgba(168,85,247,.25),rgba(99,102,241,.15));border-radius:10px;margin-bottom:1rem;display:flex;align-items:center;justify-content:center;font-size:2.5rem;">🌟</div>
      <h3 style="font-size:1rem;font-weight:600;margin-bottom:.4rem;color:#e0e7ff;">${item.title}</h3>
      <p style="font-size:.8rem;color:#e9d5ff;margin-bottom:.5rem;line-height:1.5;">${item.tags}</p>
      <p style="font-size:.75rem;color:#a5b4fc;margin-bottom:1rem;">by ${item.author}</p>
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <span class="like-btn" style="color:#64748b;font-size:.85rem;cursor:pointer;">♥ ${item.likes}</span>
        <button onclick="navigator.clipboard.writeText('${item.tags}')" style="padding:.45rem .9rem;background:linear-gradient(135deg,#a855f7,#ec4899);color:#fff;border:none;border-radius:6px;font-size:.78rem;cursor:pointer;font-weight:600;">Copy</button>
      </div>
    </div>`;
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('galleryContainer').innerHTML    = galleryItems.map(galleryCard).join('');
  document.getElementById('communityContainer').innerHTML  = communityItems.map(communityCard).join('');
});
</script>

</body>
</html>
