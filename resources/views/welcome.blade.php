<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PetHelp &mdash; Your Pet's Safety, Always Within Reach</title>
    <meta name="description" content="PetHelp connects pet owners with trusted vets, emergency SOS support, health tracking, and a loving community.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:400,500,700,900|dm-sans:300,400,500,600,700&display=swap" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --orange:#F97316;--orange-light:#FED7AA;--orange-dark:#C2410C;
  --teal:#0D9488;--teal-light:#CCFBF1;--teal-dark:#0F766E;
  --cream:#FFFBF5;--cream-2:#FEF9EE;
  --charcoal:#1C1917;--muted:#78716C;--muted-light:#D6D3D1;
  --red:#EF4444;--red-light:#FEE2E2;
  --purple:#7C3AED;--green:#059669;--amber:#F59E0B;
}
html{scroll-behavior:smooth}
body{font-family:'DM Sans',ui-sans-serif,system-ui,sans-serif;background:var(--cream);color:var(--charcoal);line-height:1.6;overflow-x:hidden}
/* ---- NAV ---- */
.nav{position:fixed;top:0;left:0;right:0;z-index:100;padding:0 2rem;height:72px;display:flex;align-items:center;justify-content:space-between;background:rgba(255,251,245,.93);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid rgba(249,115,22,.1);transition:box-shadow .3s}
.nav.scrolled{box-shadow:0 4px 32px rgba(28,25,23,.08)}
.nav-logo{display:flex;align-items:center;gap:10px;font-family:'Playfair Display',serif;font-weight:700;font-size:1.4rem;color:var(--charcoal);text-decoration:none}
.nav-links{display:flex;align-items:center;gap:2rem;list-style:none}
.nav-links a{font-size:.9rem;font-weight:500;color:var(--muted);text-decoration:none;transition:color .2s;position:relative}
.nav-links a::after{content:'';position:absolute;bottom:-4px;left:0;right:0;height:2px;background:var(--orange);border-radius:2px;transform:scaleX(0);transform-origin:left;transition:transform .25s}
.nav-links a:hover{color:var(--charcoal)}
.nav-links a:hover::after{transform:scaleX(1)}
.nav-auth{display:flex;align-items:center;gap:.75rem}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;font-family:'DM Sans',sans-serif;font-weight:600;font-size:.9rem;padding:.65rem 1.4rem;border-radius:100px;text-decoration:none;cursor:pointer;border:none;transition:all .22s cubic-bezier(.34,1.56,.64,1)}
.btn:active{transform:scale(.97)}
.btn-ghost{background:transparent;color:var(--charcoal);border:1.5px solid var(--muted-light)}
.btn-ghost:hover{border-color:var(--orange);color:var(--orange);transform:translateY(-1px)}
.btn-primary{background:var(--orange);color:#fff;box-shadow:0 4px 20px rgba(249,115,22,.35)}
.btn-primary:hover{background:var(--orange-dark);box-shadow:0 6px 28px rgba(249,115,22,.45);transform:translateY(-2px)}
.btn-teal{background:var(--teal);color:#fff;box-shadow:0 4px 20px rgba(13,148,136,.3)}
.btn-teal:hover{background:var(--teal-dark);transform:translateY(-2px);box-shadow:0 6px 28px rgba(13,148,136,.4)}
.btn-outline-orange{background:transparent;color:var(--orange);border:2px solid var(--orange)}
.btn-outline-orange:hover{background:var(--orange);color:#fff;transform:translateY(-2px)}
.btn-lg{padding:.9rem 2rem;font-size:1rem}
.btn-xl{padding:1.1rem 2.4rem;font-size:1.05rem}
.hamburger{display:none;flex-direction:column;gap:5px;cursor:pointer;background:none;border:none}
.hamburger span{width:24px;height:2px;background:var(--charcoal);border-radius:2px;display:block}
/* ---- HERO ---- */
.hero{min-height:100vh;padding:110px 2rem 4rem;display:flex;align-items:center;position:relative;overflow:hidden}
.hero-blob-1{position:absolute;top:-120px;right:-180px;width:700px;height:700px;background:radial-gradient(ellipse,#FED7AA 0%,transparent 70%);border-radius:50%;animation:blobPulse 8s ease-in-out infinite}
.hero-blob-2{position:absolute;bottom:-100px;left:-150px;width:500px;height:500px;background:radial-gradient(ellipse,#CCFBF1 0%,transparent 70%);border-radius:50%;animation:blobPulse 10s ease-in-out infinite reverse}
.hero-dots{position:absolute;inset:0;background-image:radial-gradient(circle,rgba(249,115,22,.07) 1px,transparent 1px);background-size:36px 36px}
.hero-inner{max-width:1200px;margin:0 auto;width:100%;display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:3rem;position:relative;z-index:1}
.hero-badge{display:inline-flex;align-items:center;gap:6px;background:var(--orange-light);color:var(--orange-dark);font-size:.8rem;font-weight:600;padding:.35rem .9rem;border-radius:100px;margin-bottom:1.5rem;animation:fadeUp .6s both}
.hero-title{font-family:'Playfair Display',serif;font-size:clamp(2.8rem,5vw,4.2rem);font-weight:900;line-height:1.1;color:var(--charcoal);margin-bottom:1.25rem;animation:fadeUp .7s .1s both}
.hero-title .hl{color:var(--orange)}
.hero-sub{font-size:1.1rem;color:var(--muted);max-width:460px;line-height:1.75;margin-bottom:2rem;animation:fadeUp .7s .2s both}
.hero-actions{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:2.5rem;animation:fadeUp .7s .3s both}
.hero-proof{display:flex;align-items:center;gap:.75rem;animation:fadeUp .7s .4s both}
.avatars{display:flex}
.av{width:36px;height:36px;border-radius:50%;border:2.5px solid var(--cream);margin-left:-10px;display:flex;align-items:center;justify-content:center;font-size:1rem}
.avatars .av:first-child{margin-left:0}
.proof-text{font-size:.85rem;color:var(--muted)}
.proof-text strong{color:var(--charcoal);font-weight:600}
/* Hero illustration */
.hero-illus{position:relative;display:flex;align-items:center;justify-content:center;animation:fadeLeft .9s .2s both}
.float-card{position:absolute;background:#fff;border-radius:16px;padding:12px 16px;box-shadow:0 8px 32px rgba(28,25,23,.12);font-size:.8rem;font-weight:600;display:flex;align-items:center;gap:8px;animation:floatCard 4s ease-in-out infinite}
.fc1{top:40px;left:-20px;animation-delay:0s}
.fc2{bottom:60px;right:-10px;animation-delay:1.2s;background:var(--red-light)}
.fc3{top:32%;right:-30px;animation-delay:.6s;background:var(--teal-light)}
.cdot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.cdot-g{background:var(--green)}
.cdot-r{background:var(--red);animation:pulseDot 1.5s ease infinite}
.cdot-t{background:var(--teal)}
.fpaw{position:absolute;opacity:.15;font-size:1.5rem;animation:floatPaw 6s ease-in-out infinite}
.fpaw:nth-child(1){top:8%;left:8%;animation-delay:0s}
.fpaw:nth-child(2){top:18%;right:12%;animation-delay:1s;font-size:1rem}
.fpaw:nth-child(3){bottom:22%;left:4%;animation-delay:2s;font-size:2rem}
.fpaw:nth-child(4){bottom:12%;right:6%;animation-delay:.5s;font-size:1.2rem}
/* ---- STATS ---- */
.stats{background:var(--charcoal);padding:3.5rem 2rem;position:relative;overflow:hidden}
.stats::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(249,115,22,.1) 0%,transparent 50%)}
.stats-inner{max-width:1000px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:2rem;position:relative;z-index:1}
.stat-item{text-align:center}
.stat-num{font-family:'Playfair Display',serif;font-size:clamp(2rem,4vw,3rem);font-weight:900;color:var(--orange);line-height:1;margin-bottom:.4rem}
.stat-lbl{font-size:.9rem;color:rgba(255,251,245,.55);font-weight:400}
.stat-div{width:1px;background:rgba(255,255,255,.08);align-self:center;height:48px}
/* ---- SECTIONS COMMON ---- */
.features,.how-it-works,.guides-section,.cta-section{padding:7rem 2rem}
.vets-section{padding:7rem 2rem;background:linear-gradient(180deg,var(--teal-light) 0%,var(--cream) 100%)}
.testimonials{padding:7rem 2rem;background:var(--charcoal);position:relative;overflow:hidden}
.features,.guides-section{background:var(--cream-2)}
.how-it-works,.cta-section{background:var(--cream)}
.sec-hd{text-align:center;margin-bottom:4rem}
.sec-tag{display:inline-block;font-size:.78rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--teal);margin-bottom:.75rem}
.sec-title{font-family:'Playfair Display',serif;font-size:clamp(2rem,3.5vw,2.8rem);font-weight:800;color:var(--charcoal);line-height:1.2;margin-bottom:1rem}
.sec-sub{font-size:1.05rem;color:var(--muted);max-width:520px;margin:0 auto;line-height:1.7}
/* ---- FEATURES ---- */
.feat-grid{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem}
.feat-card{background:#fff;border-radius:20px;padding:2rem;border:1.5px solid rgba(249,115,22,.07);transition:all .3s cubic-bezier(.34,1.56,.64,1);cursor:default;position:relative;overflow:hidden}
.feat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--ca),var(--cb));transform:scaleX(0);transform-origin:left;transition:transform .35s}
.feat-card:hover{transform:translateY(-6px);box-shadow:0 20px 50px rgba(28,25,23,.09);border-color:transparent}
.feat-card:hover::before{transform:scaleX(1)}
.feat-icon{width:60px;height:60px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:1.2rem;font-size:1.7rem}
.feat-name{font-weight:700;font-size:1.05rem;color:var(--charcoal);margin-bottom:.5rem}
.feat-desc{font-size:.9rem;color:var(--muted);line-height:1.65}
.feat-link{display:inline-flex;align-items:center;gap:4px;font-size:.85rem;font-weight:600;margin-top:1rem;text-decoration:none;transition:gap .2s}
.feat-link:hover{gap:8px}
/* ---- SOS ---- */
.sos-sec{background:linear-gradient(135deg,#1C1917 0%,#292524 100%);padding:7rem 2rem;position:relative;overflow:hidden}
.sos-sec::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:800px;height:800px;background:radial-gradient(circle,rgba(239,68,68,.05) 0%,transparent 70%)}
.sos-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:5rem;position:relative;z-index:1}
.sos-sec .sec-tag{color:var(--red)}
.sos-sec .sec-title{color:#fff}
.sos-sec .sec-sub{color:rgba(255,251,245,.55);max-width:unset;text-align:left}
.sos-sec .sec-hd{text-align:left;margin-bottom:2rem}
.sos-ul{list-style:none;display:flex;flex-direction:column;gap:1rem;margin-bottom:2.5rem}
.sos-ul li{display:flex;align-items:flex-start;gap:12px;font-size:.95rem;color:rgba(255,251,245,.8)}
.sos-ico{width:24px;height:24px;flex-shrink:0;background:rgba(239,68,68,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;margin-top:1px}
.sos-scene{display:flex;align-items:center;justify-content:center;position:relative}
.sos-rings{position:absolute;width:220px;height:220px}
.sos-ring{position:absolute;inset:0;border-radius:50%;border:2px solid rgba(239,68,68,.45);animation:sosRing 2.5s ease-out infinite}
.sos-ring:nth-child(2){animation-delay:.7s}
.sos-ring:nth-child(3){animation-delay:1.4s}
.sos-btn{position:relative;z-index:2;width:160px;height:160px;border-radius:50%;background:linear-gradient(145deg,#EF4444,#DC2626);display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;border:none;box-shadow:0 0 0 8px rgba(239,68,68,.15),0 20px 60px rgba(239,68,68,.4);transition:all .2s;text-decoration:none}
.sos-btn:hover{transform:scale(1.06);box-shadow:0 0 0 12px rgba(239,68,68,.2),0 24px 70px rgba(239,68,68,.5)}
.sos-btn-txt{font-family:'DM Sans',sans-serif;font-size:2rem;font-weight:900;color:#fff;letter-spacing:.05em;line-height:1}
.sos-btn-sub{font-size:.72rem;color:rgba(255,255,255,.7);font-weight:500;margin-top:4px}
.sos-status{margin-top:2.5rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:1rem 1.4rem;display:flex;align-items:center;gap:1rem}
.sos-dot{width:10px;height:10px;border-radius:50%;background:var(--green);animation:pulseDot 2s ease infinite;flex-shrink:0}
.sos-st-txt{font-size:.85rem;color:rgba(255,251,245,.6)}
.sos-st-txt strong{color:rgba(255,251,245,.9)}
/* ---- STEPS ---- */
.steps-grid{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(3,1fr);gap:2rem;position:relative}
.steps-grid::before{content:'';position:absolute;top:48px;left:calc(16.66% + 48px);right:calc(16.66% + 48px);height:2px;background:linear-gradient(90deg,var(--orange),var(--teal));opacity:.25}
.step{text-align:center;padding:2rem 1.5rem}
.step-circle{width:96px;height:96px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2.5rem;position:relative}
.step-num{position:absolute;top:4px;right:4px;width:28px;height:28px;border-radius:50%;background:var(--orange);color:#fff;font-size:.75rem;font-weight:800;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif}
.step-title{font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;color:var(--charcoal);margin-bottom:.6rem}
.step-desc{font-size:.9rem;color:var(--muted);line-height:1.65}
/* ---- VETS ---- */
.vets-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-top:3.5rem}
.vet-card{background:#fff;border-radius:20px;padding:1.5rem;border:1.5px solid rgba(13,148,136,.1);transition:all .3s ease;display:flex;flex-direction:column;gap:1rem}
.vet-card:hover{transform:translateY(-5px);box-shadow:0 16px 48px rgba(13,148,136,.13);border-color:transparent}
.vet-hd{display:flex;align-items:center;gap:.85rem}
.vet-av{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0}
.vet-name{font-weight:700;font-size:1rem;color:var(--charcoal)}
.vet-spec{font-size:.8rem;color:var(--teal);font-weight:500}
.vet-badge{display:inline-flex;align-items:center;gap:4px;font-size:.72rem;font-weight:600;padding:3px 10px;border-radius:100px;align-self:flex-start}
.badge-avail{background:rgba(5,150,105,.1);color:var(--green)}
.badge-emg{background:var(--red-light);color:var(--red)}
.vet-stats{display:flex;gap:1rem}
.vet-stat{font-size:.82rem;color:var(--muted)}
.vet-stat strong{color:var(--charcoal);font-weight:600}
.vets-cta{text-align:center;margin-top:3rem}
/* ---- GUIDES ---- */
.guides-scroll{margin-top:3rem;overflow-x:auto;padding-bottom:1rem;cursor:grab;-webkit-overflow-scrolling:touch;scrollbar-width:none}
.guides-scroll::-webkit-scrollbar{display:none}
.guides-track{display:flex;gap:1.25rem;width:max-content;padding:0 calc(50vw - 600px)}
.guide-card{width:220px;border-radius:20px;padding:1.75rem 1.5rem;text-decoration:none;color:inherit;transition:all .3s cubic-bezier(.34,1.56,.64,1);flex-shrink:0;border:1.5px solid transparent}
.guide-card:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 16px 40px rgba(28,25,23,.09)}
.g-icon{font-size:2.2rem;margin-bottom:1rem;display:block}
.g-title{font-weight:700;font-size:.95rem;color:var(--charcoal);margin-bottom:.4rem}
.g-desc{font-size:.82rem;color:var(--muted);line-height:1.5}
/* ---- TESTIMONIALS ---- */
.testimonials::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--orange),var(--teal),var(--purple))}
.testimonials .sec-title{color:#fff}
.testimonials .sec-tag{color:var(--amber)}
.t-grid{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem}
.t-card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:20px;padding:2rem;transition:all .3s}
.t-card:hover{background:rgba(255,255,255,.08);border-color:rgba(249,115,22,.28);transform:translateY(-4px)}
.t-stars{display:flex;gap:3px;margin-bottom:1rem;color:var(--amber);font-size:.9rem}
.t-quote{font-size:.95rem;color:rgba(255,251,245,.78);line-height:1.7;margin-bottom:1.5rem;font-style:italic}
.t-quote::before{content:'"';font-size:1.2rem;color:var(--orange)}
.t-quote::after{content:'"';font-size:1.2rem;color:var(--orange)}
.t-author{display:flex;align-items:center;gap:.75rem}
.t-av{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.t-name{font-weight:700;font-size:.9rem;color:#fff}
.t-pet{font-size:.8rem;color:rgba(255,251,245,.45)}
/* ---- CTA ---- */
.cta-section{text-align:center;position:relative;overflow:hidden}
.cta-section::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:600px;height:400px;background:radial-gradient(ellipse,rgba(249,115,22,.09) 0%,transparent 70%)}
.cta-in{position:relative;z-index:1}
.cta-paws{font-size:2rem;display:block;margin-bottom:1rem;letter-spacing:.5rem;opacity:.3}
.cta-title{font-family:'Playfair Display',serif;font-size:clamp(2rem,4vw,3.2rem);font-weight:900;color:var(--charcoal);line-height:1.2;margin-bottom:1.25rem}
.cta-sub{font-size:1.05rem;color:var(--muted);max-width:500px;margin:0 auto 2.5rem;line-height:1.7}
.cta-actions{display:flex;align-items:center;justify-content:center;gap:1rem;flex-wrap:wrap}
.cta-note{font-size:.82rem;color:var(--muted);margin-top:1.25rem}
/* ---- FOOTER ---- */
.footer{background:#0C0A09;color:rgba(255,251,245,.55);padding:4rem 2rem 2rem}
.footer-in{max-width:1200px;margin:0 auto}
.footer-top{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:3rem;padding-bottom:3rem;border-bottom:1px solid rgba(255,255,255,.05)}
.footer-brand .nav-logo{color:#fff;margin-bottom:1rem}
.footer-brand p{font-size:.88rem;line-height:1.7;max-width:260px}
.fc-title{font-weight:700;letter-spacing:.05em;text-transform:uppercase;font-size:.78rem;color:rgba(255,251,245,.85);margin-bottom:1rem}
.f-links{list-style:none;display:flex;flex-direction:column;gap:.6rem}
.f-links a{font-size:.88rem;color:rgba(255,251,245,.45);text-decoration:none;transition:color .2s}
.f-links a:hover{color:var(--orange)}
.footer-bot{padding-top:2rem;display:flex;align-items:center;justify-content:space-between;font-size:.82rem}
.footer-bot-links{display:flex;gap:1.5rem}
.footer-bot-links a{color:rgba(255,251,245,.35);text-decoration:none}
.footer-bot-links a:hover{color:rgba(255,251,245,.65)}
/* ---- MOBILE MENU ---- */
.mob-menu{display:none;position:fixed;inset:0;z-index:200;background:var(--cream);padding:5rem 2rem 2rem;flex-direction:column;gap:1.5rem}
.mob-menu.open{display:flex}
.mob-close{position:absolute;top:1.25rem;right:1.25rem;font-size:1.5rem;cursor:pointer;background:none;border:none;color:var(--charcoal)}
.mob-menu a{font-size:1.4rem;font-weight:600;color:var(--charcoal);text-decoration:none;font-family:'Playfair Display',serif}
/* ---- SCROLL REVEAL ---- */
.reveal{opacity:0;transform:translateY(32px);transition:opacity .7s ease,transform .7s ease}
.reveal.visible{opacity:1;transform:translateY(0)}
.rd1{transition-delay:.1s}.rd2{transition-delay:.2s}.rd3{transition-delay:.3s}.rd4{transition-delay:.4s}
/* ---- KEYFRAMES ---- */
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeLeft{from{opacity:0;transform:translateX(32px)}to{opacity:1;transform:translateX(0)}}
@keyframes blobPulse{0%,100%{transform:scale(1) rotate(0)}50%{transform:scale(1.06) rotate(4deg)}}
@keyframes floatCard{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
@keyframes floatPaw{0%,100%{transform:translateY(0) rotate(-5deg)}50%{transform:translateY(-15px) rotate(5deg)}}
@keyframes sosRing{0%{transform:scale(1);opacity:.6}100%{transform:scale(2.5);opacity:0}}
@keyframes pulseDot{0%{box-shadow:0 0 0 0 currentColor}70%{box-shadow:0 0 0 8px transparent}100%{box-shadow:0 0 0 0 transparent}}
/* ---- RESPONSIVE ---- */
@media(max-width:1024px){
  .hero-inner{grid-template-columns:1fr;text-align:center}
  .hero-illus{display:none}
  .hero-sub{max-width:100%;margin-left:auto;margin-right:auto;text-align:center}
  .hero-actions,.hero-proof{justify-content:center}
  .hero-badge{justify-content:center}
  .stats-inner{grid-template-columns:repeat(2,1fr)}
  .stat-div{display:none}
  .feat-grid{grid-template-columns:repeat(2,1fr)}
  .sos-inner{grid-template-columns:1fr;text-align:center}
  .sos-sec .sec-hd,.sos-sec .sec-sub{text-align:center}
  .sos-ul{align-items:flex-start;max-width:400px;margin-left:auto;margin-right:auto}
  .steps-grid{grid-template-columns:1fr}
  .steps-grid::before{display:none}
  .vets-grid{grid-template-columns:repeat(2,1fr)}
  .t-grid{grid-template-columns:1fr}
  .footer-top{grid-template-columns:1fr 1fr;gap:2rem}
}
@media(max-width:640px){
  .nav-links,.nav-auth{display:none}
  .hamburger{display:flex}
  .nav{padding:0 1.25rem}
  .feat-grid,.vets-grid{grid-template-columns:1fr}
  .footer-top{grid-template-columns:1fr}
  .footer-bot{flex-direction:column;gap:1rem;text-align:center}
  .guides-track{padding:0 1.25rem}
  .t-grid{grid-template-columns:1fr}
}
    </style>
</head>
<body>

<!-- MOBILE MENU -->
<div class="mob-menu" id="mMenu">
  <button class="mob-close" onclick="closeMob()">&#x2715;</button>
  <a href="#">Features</a>
  <a href="#">Find Vets</a>
  <a href="#">Emergency Guides</a>
  <a href="#">Community</a>
  @if (Route::has('login'))
    @auth
      <a href="{{ url('/dashboard') }}">Dashboard</a>
    @else
      <a href="{{ route('login') }}">Log in</a>
      @if (Route::has('register'))
        <a href="{{ route('register') }}">Get Started</a>
      @endif
    @endauth
  @endif
</div>

<!-- NAV -->
<nav class="nav" id="nav">
  <a href="/" class="nav-logo"><span>&#x1F43E;</span> PetHelp</a>
  <ul class="nav-links">
    <li><a href="#">Features</a></li>
    <li><a href="#">Find Vets</a></li>
    <li><a href="#">Emergency Guides</a></li>
    <li><a href="#">Community</a></li>
  </ul>
  <div class="nav-auth">
    @if (Route::has('login'))
      @auth
        <a href="{{ url('/dashboard') }}" class="btn btn-ghost">Dashboard</a>
      @else
        <a href="{{ route('login') }}" class="btn btn-ghost">Log in</a>
        @if (Route::has('register'))
          <a href="{{ route('register') }}" class="btn btn-primary">Get Started &rarr;</a>
        @endif
      @endauth
    @else
      <a href="#" class="btn btn-ghost">Log in</a>
      <a href="#" class="btn btn-primary">Get Started &rarr;</a>
    @endif
  </div>
  <button class="hamburger" onclick="openMob()" aria-label="Open menu">
    <span></span><span></span><span></span>
  </button>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-blob-1"></div>
  <div class="hero-blob-2"></div>
  <div class="hero-dots"></div>
  <span class="fpaw" aria-hidden="true">&#x1F43E;</span>
  <span class="fpaw" aria-hidden="true">&#x1F43E;</span>
  <span class="fpaw" aria-hidden="true">&#x1F43E;</span>
  <span class="fpaw" aria-hidden="true">&#x1F43E;</span>
  <div class="hero-inner">
    <!-- Text -->
    <div>
      <div class="hero-badge"><span>&#x2728;</span> Trusted by 10,000+ pet parents worldwide</div>
      <h1 class="hero-title">Your Pet&rsquo;s Safety,<br><span class="hl">Always Within</span><br>Reach</h1>
      <p class="hero-sub">From emergency SOS to daily health tracking &mdash; PetHelp puts everything you need to keep your furry family safe, right in your hands.</p>
      <div class="hero-actions">
        @if (Route::has('register'))
          <a href="{{ route('register') }}" class="btn btn-primary btn-xl">&#x1F43E; Get Started Free</a>
        @else
          <a href="#" class="btn btn-primary btn-xl">&#x1F43E; Get Started Free</a>
        @endif
        <a href="#" class="btn btn-outline-orange btn-xl">Find a Vet</a>
      </div>
      <div class="hero-proof">
        <div class="avatars">
          <div class="av" style="background:#FED7AA">&#x1F436;</div>
          <div class="av" style="background:#CCFBF1">&#x1F431;</div>
          <div class="av" style="background:#EDE9FE">&#x1F430;</div>
          <div class="av" style="background:#FEE2E2">&#x1F426;</div>
        </div>
        <p class="proof-text"><strong>3,200+ pets</strong> protected this month</p>
      </div>
    </div>
    <!-- Illustration -->
    <div class="hero-illus">
      <svg width="520" height="480" viewBox="0 0 520 480" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Person sitting happily with their dog and cat">
        <!-- Warm background blob -->
        <ellipse cx="270" cy="260" rx="220" ry="200" fill="#FEF3C7" opacity="0.65"/>
        <!-- Ground -->
        <ellipse cx="260" cy="408" rx="215" ry="28" fill="#D1FAE5" opacity="0.55"/>
        <!-- Person torso -->
        <rect x="198" y="228" width="94" height="114" rx="26" fill="#7C3AED"/>
        <rect x="214" y="247" width="62" height="4" rx="2" fill="#6D28D9" opacity="0.45"/>
        <rect x="214" y="257" width="46" height="4" rx="2" fill="#6D28D9" opacity="0.45"/>
        <!-- Head -->
        <circle cx="245" cy="198" r="41" fill="#FBBF24"/>
        <!-- Hair -->
        <ellipse cx="245" cy="170" rx="39" ry="22" fill="#1C1917"/>
        <ellipse cx="219" cy="182" rx="13" ry="17" fill="#1C1917"/>
        <ellipse cx="271" cy="182" rx="13" ry="17" fill="#1C1917"/>
        <!-- Eyes -->
        <circle cx="233" cy="197" r="5.5" fill="#1C1917"/>
        <circle cx="257" cy="197" r="5.5" fill="#1C1917"/>
        <circle cx="235" cy="195" r="2" fill="white"/>
        <circle cx="259" cy="195" r="2" fill="white"/>
        <!-- Smile -->
        <path d="M233 212 Q245 223 257 212" stroke="#1C1917" stroke-width="3" stroke-linecap="round" fill="none"/>
        <!-- Cheeks -->
        <circle cx="220" cy="206" r="7.5" fill="#FECACA" opacity="0.65"/>
        <circle cx="270" cy="206" r="7.5" fill="#FECACA" opacity="0.65"/>
        <!-- Arms -->
        <rect x="172" y="238" width="28" height="68" rx="14" fill="#FBBF24"/>
        <rect x="290" y="238" width="28" height="68" rx="14" fill="#FBBF24"/>
        <!-- Legs -->
        <ellipse cx="202" cy="347" rx="36" ry="14" fill="#4C1D95"/>
        <ellipse cx="288" cy="347" rx="36" ry="14" fill="#4C1D95"/>
        <path d="M202 347 Q180 374 154 378" stroke="#4C1D95" stroke-width="18" stroke-linecap="round" fill="none"/>
        <path d="M288 347 Q310 374 336 378" stroke="#4C1D95" stroke-width="18" stroke-linecap="round" fill="none"/>
        <!-- Shoes -->
        <ellipse cx="154" cy="382" rx="22" ry="11" fill="#1C1917"/>
        <ellipse cx="336" cy="382" rx="22" ry="11" fill="#1C1917"/>
        <!-- DOG (left, sitting) -->
        <ellipse cx="142" cy="340" rx="44" ry="37" fill="#F59E0B"/>
        <circle cx="126" cy="294" r="33" fill="#F59E0B"/>
        <!-- Ears -->
        <ellipse cx="104" cy="288" rx="15" ry="23" fill="#D97706" transform="rotate(-20 104 288)"/>
        <ellipse cx="148" cy="288" rx="15" ry="23" fill="#D97706" transform="rotate(20 148 288)"/>
        <!-- Snout -->
        <ellipse cx="126" cy="307" rx="19" ry="13" fill="#FDE68A"/>
        <!-- Nose -->
        <ellipse cx="126" cy="302" rx="6.5" ry="4.5" fill="#1C1917"/>
        <!-- Dog eyes -->
        <circle cx="115" cy="293" r="5" fill="#1C1917"/>
        <circle cx="137" cy="293" r="5" fill="#1C1917"/>
        <circle cx="116" cy="291" r="2" fill="white"/>
        <circle cx="138" cy="291" r="2" fill="white"/>
        <!-- Tongue -->
        <path d="M120 314 Q126 323 132 314" fill="#EF4444"/>
        <!-- Raised paw -->
        <ellipse cx="96" cy="313" rx="15" ry="11" fill="#F59E0B" transform="rotate(-38 96 313)"/>
        <circle cx="88" cy="305" r="5.5" fill="#FDE68A"/>
        <circle cx="96" cy="302" r="5.5" fill="#FDE68A"/>
        <circle cx="104" cy="305" r="5.5" fill="#FDE68A"/>
        <!-- Tail -->
        <path d="M184 344 Q212 315 222 298 Q228 288 220 282" stroke="#D97706" stroke-width="10" stroke-linecap="round" fill="none"/>
        <!-- CAT (right, on lap area) -->
        <ellipse cx="362" cy="347" rx="40" ry="30" fill="#94A3B8"/>
        <circle cx="374" cy="308" r="27" fill="#94A3B8"/>
        <!-- Cat ears -->
        <polygon points="357,292 348,271 365,288" fill="#94A3B8"/>
        <polygon points="391,292 400,271 383,288" fill="#94A3B8"/>
        <polygon points="358,291 351,275 365,289" fill="#FBBF24" opacity="0.55"/>
        <polygon points="390,291 397,275 383,289" fill="#FBBF24" opacity="0.55"/>
        <!-- Cat eyes (slitted) -->
        <ellipse cx="363" cy="308" rx="5.5" ry="6.5" fill="#2D3748"/>
        <ellipse cx="385" cy="308" rx="5.5" ry="6.5" fill="#2D3748"/>
        <ellipse cx="363" cy="307" rx="2" ry="4" fill="#1C1917"/>
        <ellipse cx="385" cy="307" rx="2" ry="4" fill="#1C1917"/>
        <circle cx="364" cy="306" r="1.5" fill="white" opacity="0.7"/>
        <circle cx="386" cy="306" r="1.5" fill="white" opacity="0.7"/>
        <!-- Cat nose -->
        <polygon points="374,315 371,319 377,319" fill="#FDA4AF"/>
        <!-- Whiskers -->
        <line x1="348" y1="317" x2="366" y2="318" stroke="#64748B" stroke-width="1.5" stroke-linecap="round"/>
        <line x1="348" y1="321" x2="366" y2="320" stroke="#64748B" stroke-width="1.5" stroke-linecap="round"/>
        <line x1="400" y1="317" x2="382" y2="318" stroke="#64748B" stroke-width="1.5" stroke-linecap="round"/>
        <line x1="400" y1="321" x2="382" y2="320" stroke="#64748B" stroke-width="1.5" stroke-linecap="round"/>
        <!-- Cat mouth -->
        <path d="M370 321 Q374 325 378 321" stroke="#64748B" stroke-width="1.5" stroke-linecap="round" fill="none"/>
        <!-- Cat tail -->
        <path d="M400 362 Q425 350 430 328 Q433 314 422 307 Q413 301 408 310" stroke="#94A3B8" stroke-width="9" stroke-linecap="round" fill="none"/>
        <!-- Heart above dog -->
        <text x="88" y="258" font-size="24" fill="#EF4444" opacity="0.9">&#x2764;&#xFE0F;</text>
        <!-- Sparkles -->
        <text x="342" y="238" font-size="18" fill="#F59E0B" opacity="0.85">&#x2728;</text>
        <text x="168" y="192" font-size="15" fill="#7C3AED" opacity="0.7">&#x2605;</text>
        <text x="424" y="298" font-size="20" fill="#0D9488" opacity="0.75">&#x1F31F;</text>
      </svg>
      <!-- Floating info cards -->
      <div class="float-card fc1">
        <span class="cdot cdot-g"></span>
        <div>
          <div style="font-size:.72rem;color:var(--muted);font-weight:400">Vet Available</div>
          <div style="font-size:.85rem;color:var(--charcoal)">Dr. Priya &mdash; 0.8 km</div>
        </div>
      </div>
      <div class="float-card fc2">
        <span class="cdot cdot-r"></span>
        <div>
          <div style="font-size:.72rem;color:var(--muted);font-weight:400">SOS Response</div>
          <div style="font-size:.85rem;color:var(--charcoal)">3 vets notified &#x2713;</div>
        </div>
      </div>
      <div class="float-card fc3">
        <span style="font-size:1rem">&#x1F436;</span>
        <div>
          <div style="font-size:.72rem;color:var(--muted);font-weight:400">Buddy&rsquo;s Health</div>
          <div style="font-size:.85rem;color:var(--charcoal)">Vaccine due in 3 days</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS -->
<section class="stats">
  <div class="stats-inner">
    <div class="stat-item reveal">
      <div class="stat-num"><span class="counter" data-target="10000" data-suffix="+">0</span></div>
      <div class="stat-lbl">Pet parents trust PetHelp</div>
    </div>
    <div class="stat-div"></div>
    <div class="stat-item reveal rd1">
      <div class="stat-num"><span class="counter" data-target="150" data-suffix="+">0</span></div>
      <div class="stat-lbl">Verified vets in network</div>
    </div>
    <div class="stat-div"></div>
    <div class="stat-item reveal rd2">
      <div class="stat-num"><span class="counter" data-target="3200" data-suffix="+">0</span></div>
      <div class="stat-lbl">Pets protected this month</div>
    </div>
    <div class="stat-div"></div>
    <div class="stat-item reveal rd3">
      <div class="stat-num"><span class="counter" data-target="99" data-suffix="%">0</span></div>
      <div class="stat-lbl">Emergency response rate</div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="features">
  <div class="sec-hd reveal">
    <span class="sec-tag">What We Offer</span>
    <h2 class="sec-title">Everything Your Pet Needs,<br>All in One Place</h2>
    <p class="sec-sub">From urgent emergencies to everyday care &mdash; PetHelp is the complete platform for responsible pet parents.</p>
  </div>
  <div class="feat-grid">
    <div class="feat-card reveal" style="--ca:#EF4444;--cb:#F97316">
      <div class="feat-icon" style="background:#FEE2E2">&#x1F6A8;</div>
      <div class="feat-name">Emergency SOS</div>
      <div class="feat-desc">Instantly alert nearby vets with your location. Auto-dispatches the 3 closest available vets &mdash; no phone calls needed in a crisis.</div>
      <a href="#" class="feat-link" style="color:var(--red)">Learn more &rarr;</a>
    </div>
    <div class="feat-card reveal rd1" style="--ca:#F59E0B;--cb:#F97316">
      <div class="feat-icon" style="background:#FEF3C7">&#x1F43E;</div>
      <div class="feat-name">My Pets</div>
      <div class="feat-desc">Complete health profiles for all your pets &mdash; medical records, medications, vaccinations, photos and important notes in one place.</div>
      <a href="#" class="feat-link" style="color:var(--amber)">Manage pets &rarr;</a>
    </div>
    <div class="feat-card reveal rd2" style="--ca:#0D9488;--cb:#7C3AED">
      <div class="feat-icon" style="background:#CCFBF1">&#x1F3E5;</div>
      <div class="feat-name">Vet Finder</div>
      <div class="feat-desc">Discover 150+ verified vets near you. Filter by specialty, availability, emergency support, distance and genuine user ratings.</div>
      <a href="#" class="feat-link" style="color:var(--teal)">Find vets &rarr;</a>
    </div>
    <div class="feat-card reveal rd1" style="--ca:#7C3AED;--cb:#0D9488">
      <div class="feat-icon" style="background:#EDE9FE">&#x1F4D6;</div>
      <div class="feat-name">Emergency Guides</div>
      <div class="feat-desc">Step-by-step first aid guides for poisoning, seizures, injuries, choking and more &mdash; written by veterinary professionals.</div>
      <a href="#" class="feat-link" style="color:var(--purple)">Read guides &rarr;</a>
    </div>
    <div class="feat-card reveal rd2" style="--ca:#F97316;--cb:#F59E0B">
      <div class="feat-icon" style="background:#FEF3C7">&#x1F4AC;</div>
      <div class="feat-name">Community</div>
      <div class="feat-desc">Connect with fellow pet parents. Share tips, ask questions, post photos, and support each other through every milestone.</div>
      <a href="#" class="feat-link" style="color:var(--orange)">Join community &rarr;</a>
    </div>
    <div class="feat-card reveal rd3" style="--ca:#059669;--cb:#0D9488">
      <div class="feat-icon" style="background:#D1FAE5">&#x1F916;</div>
      <div class="feat-name">AI Pet Assistant</div>
      <div class="feat-desc">Chat with our AI pet health assistant anytime. Get instant answers, symptom checks and personalised care tips &mdash; 24/7.</div>
      <a href="#" class="feat-link" style="color:var(--green)">Ask AI &rarr;</a>
    </div>
  </div>
</section>

<!-- SOS SECTION -->
<section class="sos-sec">
  <div class="sos-inner">
    <div>
      <div class="sec-hd">
        <span class="sec-tag">&#x1F6A8; Emergency SOS</span>
        <h2 class="sec-title">When Every Second Counts</h2>
        <p class="sec-sub">A pet emergency is terrifying. PetHelp ensures that with one tap, help is already on its way to you.</p>
      </div>
      <ul class="sos-ul">
        <li><span class="sos-ico">&#x1F4CD;</span><span>Automatically captures your GPS location &mdash; no typing needed</span></li>
        <li><span class="sos-ico">&#x1F3E5;</span><span>Instantly notifies the 3 nearest available vets in real-time</span></li>
        <li><span class="sos-ico">&#x1F4CA;</span><span>Track response status live: pending &rarr; acknowledged &rarr; in progress</span></li>
        <li><span class="sos-ico">&#x1F4CB;</span><span>Full incident log automatically created for every emergency</span></li>
      </ul>
      <a href="#" class="btn btn-lg" style="background:#EF4444;color:#fff;box-shadow:0 4px 20px rgba(239,68,68,.4)">&#x1F6A8; Try the SOS Feature</a>
      <div class="sos-status">
        <div class="sos-dot" style="color:var(--green)"></div>
        <div class="sos-st-txt"><strong>Emergency network active</strong> &mdash; 47 vets currently available in your area</div>
      </div>
    </div>
    <div class="sos-scene reveal">
      <div class="sos-rings">
        <div class="sos-ring"></div>
        <div class="sos-ring"></div>
        <div class="sos-ring"></div>
      </div>
      <a href="#" class="sos-btn" aria-label="SOS Emergency Button">
        <span class="sos-btn-txt">SOS</span>
        <span class="sos-btn-sub">Tap in emergency</span>
      </a>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-it-works">
  <div class="sec-hd reveal">
    <span class="sec-tag">Simple Steps</span>
    <h2 class="sec-title">Getting Started is Effortless</h2>
    <p class="sec-sub">Three simple steps to protect every pet you love.</p>
  </div>
  <div class="steps-grid">
    <div class="step reveal">
      <div class="step-circle" style="background:#FEF3C7">
        <span>&#x1F43E;</span>
        <div class="step-num">1</div>
      </div>
      <h3 class="step-title">Create Your Pet&rsquo;s Profile</h3>
      <p class="step-desc">Register in seconds and add your pets &mdash; name, species, breed, medical history, photos. Up to 10 pets per account.</p>
    </div>
    <div class="step reveal rd1">
      <div class="step-circle" style="background:#CCFBF1">
        <span>&#x1F3E5;</span>
        <div class="step-num">2</div>
      </div>
      <h3 class="step-title">Connect with Trusted Vets</h3>
      <p class="step-desc">Browse 150+ verified vets near you, check their availability, ratings and specialties. Bookmark your favourites for quick access.</p>
    </div>
    <div class="step reveal rd2">
      <div class="step-circle" style="background:#EDE9FE">
        <span>&#x2764;&#xFE0F;</span>
        <div class="step-num">3</div>
      </div>
      <h3 class="step-title">Keep Them Safe Every Day</h3>
      <p class="step-desc">Get reminders for vaccines, medications and check-ups. In an emergency, hit SOS &mdash; help arrives within minutes.</p>
    </div>
  </div>
</section>

<!-- VETS -->
<section class="vets-section">
  <div style="max-width:1200px;margin:0 auto">
    <div class="sec-hd reveal">
      <span class="sec-tag">Verified Professionals</span>
      <h2 class="sec-title">Meet Our Vet Network</h2>
      <p class="sec-sub">Every vet on PetHelp is background-checked, licensed, and reviewed by real pet owners.</p>
    </div>
    <div class="vets-grid">
      <div class="vet-card reveal">
        <div class="vet-hd">
          <div class="vet-av" style="background:#FEF3C7">&#x1F9D1;&#x200D;&#x2695;&#xFE0F;</div>
          <div><div class="vet-name">Dr. Priya Sharma</div><div class="vet-spec">Small Animal Specialist</div></div>
        </div>
        <span class="vet-badge badge-avail">&#x25CF; Available Now</span>
        <div class="vet-stats">
          <div class="vet-stat">&#x2B50; <strong>4.9</strong></div>
          <div class="vet-stat">&#x1F4CD; <strong>0.8 km</strong></div>
          <div class="vet-stat">&#x2705; <strong>342 reviews</strong></div>
        </div>
      </div>
      <div class="vet-card reveal rd1">
        <div class="vet-hd">
          <div class="vet-av" style="background:#CCFBF1">&#x1F469;&#x200D;&#x2695;&#xFE0F;</div>
          <div><div class="vet-name">Dr. Rahul Mehra</div><div class="vet-spec">Emergency &amp; Critical Care</div></div>
        </div>
        <span class="vet-badge badge-emg">&#x1F6A8; 24/7 Emergency</span>
        <div class="vet-stats">
          <div class="vet-stat">&#x2B50; <strong>4.8</strong></div>
          <div class="vet-stat">&#x1F4CD; <strong>1.4 km</strong></div>
          <div class="vet-stat">&#x2705; <strong>218 reviews</strong></div>
        </div>
      </div>
      <div class="vet-card reveal rd2">
        <div class="vet-hd">
          <div class="vet-av" style="background:#EDE9FE">&#x1F9D1;&#x200D;&#x2695;&#xFE0F;</div>
          <div><div class="vet-name">Dr. Anjali Kaur</div><div class="vet-spec">Feline &amp; Exotic Animals</div></div>
        </div>
        <span class="vet-badge badge-avail">&#x25CF; Available Now</span>
        <div class="vet-stats">
          <div class="vet-stat">&#x2B50; <strong>5.0</strong></div>
          <div class="vet-stat">&#x1F4CD; <strong>2.1 km</strong></div>
          <div class="vet-stat">&#x2705; <strong>197 reviews</strong></div>
        </div>
      </div>
    </div>
    <div class="vets-cta reveal"><a href="#" class="btn btn-teal btn-lg">&#x1F5FA;&#xFE0F; Browse All 150+ Vets</a></div>
  </div>
</section>

<!-- EMERGENCY GUIDES -->
<section class="guides-section">
  <div class="sec-hd reveal">
    <span class="sec-tag">First Aid Knowledge</span>
    <h2 class="sec-title">Know What to Do Before<br>the Vet Arrives</h2>
    <p class="sec-sub">Free, vet-approved emergency guides for every situation.</p>
  </div>
  <div class="guides-scroll" id="gsScroll">
    <div class="guides-track">
      <a href="#" class="guide-card" style="background:#FEE2E2;border-color:#FECACA"><span class="g-icon">&#x1F915;</span><div class="g-title">Injuries &amp; Wounds</div><div class="g-desc">How to stop bleeding and stabilise your pet before the vet.</div></a>
      <a href="#" class="guide-card" style="background:#FEF3C7;border-color:#FDE68A"><span class="g-icon">&#x26A0;&#xFE0F;</span><div class="g-title">Poisoning</div><div class="g-desc">Identify toxins and the right steps when your pet ingests something dangerous.</div></a>
      <a href="#" class="guide-card" style="background:#EDE9FE;border-color:#DDD6FE"><span class="g-icon">&#x1F9E0;</span><div class="g-title">Seizures</div><div class="g-desc">Keep your pet safe during a seizure &mdash; what to do and what to avoid.</div></a>
      <a href="#" class="guide-card" style="background:#CCFBF1;border-color:#99F6E4"><span class="g-icon">&#x1FAC1;</span><div class="g-title">Breathing Difficulty</div><div class="g-desc">Recognise signs of respiratory distress and provide immediate relief.</div></a>
      <a href="#" class="guide-card" style="background:#FEF3C7;border-color:#FDE68A"><span class="g-icon">&#x1F494;</span><div class="g-title">Cardiac Emergency</div><div class="g-desc">Pet CPR technique and when to start chest compressions.</div></a>
      <a href="#" class="guide-card" style="background:#FEE2E2;border-color:#FECACA"><span class="g-icon">&#x1F9B4;</span><div class="g-title">Broken Bones</div><div class="g-desc">Safe handling and improvised splinting to minimise further injury.</div></a>
      <a href="#" class="guide-card" style="background:#D1FAE5;border-color:#A7F3D0"><span class="g-icon">&#x1F321;&#xFE0F;</span><div class="g-title">Heatstroke</div><div class="g-desc">Rapid cooling methods and signs of dangerous overheating.</div></a>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials">
  <div class="sec-hd reveal">
    <span class="sec-tag">Real Stories</span>
    <h2 class="sec-title">Pet Parents Love PetHelp</h2>
  </div>
  <div class="t-grid">
    <div class="t-card reveal">
      <div class="t-stars">&#x2B50;&#x2B50;&#x2B50;&#x2B50;&#x2B50;</div>
      <p class="t-quote">My dog Bruno swallowed something he shouldn&rsquo;t have. I hit SOS at 2am and Dr. Rahul was at my door in 18 minutes. PetHelp literally saved his life.</p>
      <div class="t-author">
        <div class="t-av" style="background:#FEF3C7">&#x1F469;</div>
        <div><div class="t-name">Aditi Sharma</div><div class="t-pet">Bruno the Golden Retriever &#x1F436;</div></div>
      </div>
    </div>
    <div class="t-card reveal rd1">
      <div class="t-stars">&#x2B50;&#x2B50;&#x2B50;&#x2B50;&#x2B50;</div>
      <p class="t-quote">The emergency guides are genuinely written by vets who care. When my cat had a seizure, I knew exactly what to do because I&rsquo;d read PetHelp&rsquo;s guide beforehand.</p>
      <div class="t-author">
        <div class="t-av" style="background:#CCFBF1">&#x1F468;</div>
        <div><div class="t-name">Rohan Verma</div><div class="t-pet">Mochi the Siamese &#x1F431;</div></div>
      </div>
    </div>
    <div class="t-card reveal rd2">
      <div class="t-stars">&#x2B50;&#x2B50;&#x2B50;&#x2B50;&#x2B50;</div>
      <p class="t-quote">Tracking all three of my pets&rsquo; vaccines and appointments used to be a nightmare. Now it&rsquo;s five minutes a month. The reminders alone are worth it.</p>
      <div class="t-author">
        <div class="t-av" style="background:#EDE9FE">&#x1F469;</div>
        <div><div class="t-name">Meera Nair</div><div class="t-pet">Coco, Toffee &amp; Biscuit &#x1F436;&#x1F431;&#x1F430;</div></div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="cta-in reveal">
    <span class="cta-paws" aria-hidden="true">&#x1F43E; &#x1F43E; &#x1F43E;</span>
    <h2 class="cta-title">Your Pet Deserves<br>the Very Best Care</h2>
    <p class="cta-sub">Join thousands of loving pet parents who have made PetHelp their pet&rsquo;s safety net. It&rsquo;s free to get started.</p>
    <div class="cta-actions">
      @if (Route::has('register'))
        <a href="{{ route('register') }}" class="btn btn-primary btn-xl">&#x1F43E; Create Free Account</a>
      @else
        <a href="#" class="btn btn-primary btn-xl">&#x1F43E; Create Free Account</a>
      @endif
      <a href="#" class="btn btn-outline-orange btn-xl">See All Features</a>
    </div>
    <p class="cta-note">No credit card required &middot; Free forever for pet owners</p>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-in">
    <div class="footer-top">
      <div class="footer-brand">
        <a href="/" class="nav-logo"><span>&#x1F43E;</span> PetHelp</a>
        <p>Connecting pet owners with trusted vets, emergency support, and a community that cares &mdash; because every pet deserves the best life.</p>
      </div>
      <div>
        <div class="fc-title">Platform</div>
        <ul class="f-links">
          <li><a href="#">Emergency SOS</a></li>
          <li><a href="#">Find Vets</a></li>
          <li><a href="#">Pet Profiles</a></li>
          <li><a href="#">Health Records</a></li>
          <li><a href="#">AI Assistant</a></li>
        </ul>
      </div>
      <div>
        <div class="fc-title">Resources</div>
        <ul class="f-links">
          <li><a href="#">Emergency Guides</a></li>
          <li><a href="#">Pet Health Blog</a></li>
          <li><a href="#">Community Forum</a></li>
          <li><a href="#">API Docs</a></li>
        </ul>
      </div>
      <div>
        <div class="fc-title">Company</div>
        <ul class="f-links">
          <li><a href="#">About PetHelp</a></li>
          <li><a href="#">For Vets</a></li>
          <li><a href="#">Contact Us</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Service</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bot">
      <div style="color:rgba(255,251,245,.3)">&copy; {{ date('Y') }} PetHelp. Made with &#x2764;&#xFE0F; for pet families everywhere.</div>
      <div class="footer-bot-links">
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
        <a href="#">Cookies</a>
      </div>
    </div>
  </div>
</footer>

<script>
// Sticky nav
const nav = document.getElementById('nav');
window.addEventListener('scroll', () => { nav.classList.toggle('scrolled', window.scrollY > 20); });
// Mobile menu
function openMob()  { document.getElementById('mMenu').classList.add('open'); document.body.style.overflow='hidden'; }
function closeMob() { document.getElementById('mMenu').classList.remove('open'); document.body.style.overflow=''; }
// Scroll reveal
const obs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
// Counter animation
function animateCount(el) {
  const target = parseInt(el.dataset.target, 10);
  const suffix = el.dataset.suffix || '';
  const steps = 55, dur = 1600;
  let current = 0;
  const inc = target / steps;
  const iv = setInterval(() => {
    current = Math.min(current + inc, target);
    el.textContent = Math.floor(current).toLocaleString() + suffix;
    if (current >= target) { clearInterval(iv); el.textContent = target.toLocaleString() + suffix; }
  }, dur / steps);
}
const cObs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) { animateCount(e.target); cObs.unobserve(e.target); } });
}, { threshold: 0.5 });
document.querySelectorAll('.counter').forEach(el => cObs.observe(el));
// Draggable guides scroll
const gs = document.getElementById('gsScroll');
if (gs) {
  let drag = false, sx, sl;
  gs.addEventListener('mousedown', e => { drag=true; gs.style.cursor='grabbing'; sx=e.pageX-gs.offsetLeft; sl=gs.scrollLeft; });
  gs.addEventListener('mouseleave', () => { drag=false; gs.style.cursor='grab'; });
  gs.addEventListener('mouseup', () => { drag=false; gs.style.cursor='grab'; });
  gs.addEventListener('mousemove', e => { if(!drag) return; e.preventDefault(); gs.scrollLeft = sl - (e.pageX - gs.offsetLeft - sx)*1.6; });
}
</script>
</body>
</html>
