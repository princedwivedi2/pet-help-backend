<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Respaw — Pet care, finally in one place</title>
    <meta name="description" content="Respaw is an upcoming pet-care platform for finding veterinary care, booking clinic or online consultations, and keeping pet health information organized. Request early access.">
    <meta name="theme-color" content="#35144F">
    <meta name="color-scheme" content="light">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Respaw — Pet care, finally in one place">
    <meta property="og:description" content="Find care, book consultations, and keep your pet's health story close. Respaw is launching soon.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
    @vite(['resources/css/landing.css', 'resources/js/landing.js'])
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <svg class="svg-sprite" aria-hidden="true">
        <symbol id="icon-arrow-right" viewBox="0 0 24 24"><path d="M5 12h14m-5-5 5 5-5 5"/></symbol>
        <symbol id="icon-arrow-up" viewBox="0 0 24 24"><path d="m6 14 6-6 6 6"/></symbol>
        <symbol id="icon-audio" viewBox="0 0 24 24"><path d="M4 10v4m4-7v10m4-14v18m4-14v10m4-7v4"/></symbol>
        <symbol id="icon-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></symbol>
        <symbol id="icon-calendar" viewBox="0 0 24 24"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/><path d="M8 13h3m2 0h3m-8 4h3"/></symbol>
        <symbol id="icon-chat" viewBox="0 0 24 24"><path d="M21 14a4 4 0 0 1-4 4H9l-5 3v-5a7 7 0 1 1 17-2Z"/><path d="M9 11h.01M13 11h.01M17 11h.01"/></symbol>
        <symbol id="icon-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
        <symbol id="icon-chevron" viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></symbol>
        <symbol id="icon-clinic" viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h6m-3-3v6M8 21v-5h8v5"/></symbol>
        <symbol id="icon-close" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></symbol>
        <symbol id="icon-document" viewBox="0 0 24 24"><path d="M6 2h9l3 3v17H6Z"/><path d="M14 2v5h4M9 12h6m-6 4h6"/></symbol>
        <symbol id="icon-heartbeat" viewBox="0 0 24 24"><path d="M3 12h4l2-5 4 10 2-5h6"/></symbol>
        <symbol id="icon-location" viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></symbol>
        <symbol id="icon-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></symbol>
        <symbol id="icon-paw" viewBox="0 0 24 24"><ellipse cx="7" cy="8" rx="2" ry="2.5"/><ellipse cx="17" cy="8" rx="2" ry="2.5"/><ellipse cx="4.5" cy="13" rx="2" ry="2.5"/><ellipse cx="19.5" cy="13" rx="2" ry="2.5"/><path d="M8 18c0-2.3 1.8-4 4-4s4 1.7 4 4c0 2-1.8 3-4 3s-4-1-4-3Z"/></symbol>
        <symbol id="icon-pill" viewBox="0 0 24 24"><path d="m8.5 19.5-4-4a5 5 0 0 1 7-7l4 4a5 5 0 0 1-7 7Z"/><path d="m8 12 4 4"/></symbol>
        <symbol id="icon-profile" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 22a8 8 0 0 1 16 0"/></symbol>
        <symbol id="icon-shield" viewBox="0 0 24 24"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="M9 12h6m-3-3v6"/></symbol>
        <symbol id="icon-video" viewBox="0 0 24 24"><rect x="3" y="5" width="13" height="14" rx="2"/><path d="m16 10 5-3v10l-5-3"/></symbol>
        <symbol id="icon-warning" viewBox="0 0 24 24"><path d="M10.3 3.7 2.4 18a2 2 0 0 0 1.8 3h15.6a2 2 0 0 0 1.8-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4m0 4h.01"/></symbol>
    </svg>

    <header class="site-header" data-header>
        <div class="shell header-inner">
            <a class="brand" href="#top" aria-label="Respaw home">
                <img src="{{ asset('images/respaw-logo-wordmark.png') }}" alt="Respaw">
            </a>
            <nav class="desktop-nav" aria-label="Primary navigation">
                <a href="#why">Why Respaw</a>
                <a href="#how">How it works</a>
                <a href="#care">Care in one place</a>
                <a href="#faq">FAQ</a>
            </nav>
            <a class="button button-small header-cta" href="#early-access">Request early access</a>
            <button class="menu-button" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="mobile-menu" data-menu-button>
                <svg><use href="#icon-menu"/></svg>
            </button>
        </div>
        <nav class="mobile-menu" id="mobile-menu" aria-label="Mobile navigation" data-mobile-menu>
            <a href="#why">Why Respaw</a>
            <a href="#how">How it works</a>
            <a href="#care">Care in one place</a>
            <a href="#faq">FAQ</a>
            <a class="button" href="#early-access">Request early access</a>
        </nav>
    </header>

    <main id="main-content">
        <section class="hero" id="top">
            <div class="hero-halo" aria-hidden="true"></div>
            <div class="shell hero-grid">
                <div class="hero-copy">
                    <h1>Pet care, finally in one place.</h1>
                    <p class="hero-lede">Find veterinary care, book clinic or online consultations, keep every record close, and know what to do when something feels urgent.</p>
                    <div class="hero-actions">
                        <a class="button button-arrow" href="#early-access">
                            <span>Request early access</span>
                            <svg><use href="#icon-arrow-right"/></svg>
                        </a>
                        <a class="text-link" href="#how">
                            See how Respaw works
                            <svg><use href="#icon-arrow-right"/></svg>
                        </a>
                    </div>
                    <p class="launch-note">
                        <span class="launch-mark" aria-hidden="true"><svg><use href="#icon-paw"/></svg></span>
                        <span><strong>Launching soon.</strong> Thoughtful pet care is taking shape.</span>
                    </p>
                </div>

                <div class="hero-product" aria-label="Preview of the upcoming Respaw app">
                    <figure class="hero-photo">
                        <img src="{{ asset('images/respaw-hero-pet.png') }}" alt="Golden retriever resting in warm light" fetchpriority="high">
                        <figcaption><strong>Care, made clearer.</strong><span>Launching soon</span></figcaption>
                    </figure>
                    <div class="orbit orbit-one" aria-hidden="true"></div>
                    <div class="orbit orbit-two" aria-hidden="true"></div>
                    <article class="phone phone-main float-slow">
                        <div class="phone-top"><span>9:41</span><span class="phone-status">● ●</span></div>
                        <div class="app-header">
                            <div>
                                <p class="app-kicker">Pet profile</p>
                                <h2>Milo</h2>
                            </div>
                            <span class="avatar-paw"><svg><use href="#icon-paw"/></svg></span>
                        </div>
                        <div class="pet-meta"><span>3 years</span><span>Dog</span><span>12.4 kg</span></div>
                        <div class="app-list">
                            <div class="app-row">
                                <span class="app-icon lavender"><svg><use href="#icon-document"/></svg></span>
                                <span><strong>Health records</strong><small>Vaccines, notes, history</small></span>
                                <svg class="row-arrow"><use href="#icon-chevron"/></svg>
                            </div>
                            <div class="app-row">
                                <span class="app-icon mint"><svg><use href="#icon-calendar"/></svg></span>
                                <span><strong>Appointments</strong><small>Upcoming and past</small></span>
                                <svg class="row-arrow"><use href="#icon-chevron"/></svg>
                            </div>
                            <div class="app-row">
                                <span class="app-icon peach"><svg><use href="#icon-pill"/></svg></span>
                                <span><strong>Medications</strong><small>Schedules and reminders</small></span>
                                <svg class="row-arrow"><use href="#icon-chevron"/></svg>
                            </div>
                        </div>
                        <div class="app-dock" aria-hidden="true">
                            <svg><use href="#icon-paw"/></svg>
                            <svg><use href="#icon-heartbeat"/></svg>
                            <span><svg><use href="#icon-clinic"/></svg></span>
                            <svg><use href="#icon-profile"/></svg>
                        </div>
                    </article>

                    <article class="mini-screen mini-nearby float-medium">
                        <div class="mini-head"><span>Nearby care</span><svg><use href="#icon-location"/></svg></div>
                        <div class="mini-map" aria-hidden="true">
                            <i class="map-pin pin-one"></i><i class="map-pin pin-two"></i><i class="map-pin pin-three"></i>
                        </div>
                        <p><strong>Veterinary care nearby</strong><small>Compare location and visit options</small></p>
                    </article>

                    <article class="mini-screen mini-appointment float-fast">
                        <span class="app-icon lavender"><svg><use href="#icon-calendar"/></svg></span>
                        <p><small>Upcoming appointment</small><strong>Clinic visit</strong><em>Details stay close</em></p>
                    </article>

                    <article class="mini-screen mini-reminder float-slow">
                        <span class="app-icon yellow"><svg><use href="#icon-bell"/></svg></span>
                        <p><small>Reminder</small><strong>Medication due</strong><em>Today at 9:00 AM</em></p>
                    </article>
                </div>
            </div>
            <div class="shell hero-tail" aria-hidden="true">
                <span>One calm place for the moments that matter.</span>
                <div></div>
            </div>
        </section>

        <section class="scattered section" id="why">
            <div class="shell scattered-grid">
                <div class="section-copy reveal">
                    <p class="eyebrow">THE SPACE BETWEEN VISITS</p>
                    <h2>Care gets messy.<br><em>Clarity shouldn’t.</em></h2>
                    <p>The clinic address in one chat. A prescription in another. A reminder you meant to set. Respaw is being built to bring the whole care journey together—before, during, and after the appointment.</p>
                </div>

                <div class="care-thread reveal" aria-label="A visual showing scattered pet-care details becoming one calm care thread">
                    <div class="thread-orb thread-orb-one" aria-hidden="true"></div>
                    <div class="thread-orb thread-orb-two" aria-hidden="true"></div>
                    <div class="thread-head">
                        <div class="thread-pet-mark"><svg><use href="#icon-paw"/></svg></div>
                        <div><small>ONE CARE THREAD</small><strong>Milo’s story, in context</strong></div>
                        <span>Launching soon</span>
                    </div>
                    <div class="thread-flow">
                        <div class="thread-fragments">
                            <span class="thread-side-label">Before</span>
                            <span class="fragment fragment-chat"><svg><use href="#icon-chat"/></svg><b>Clinic address</b><em>in a chat</em></span>
                            <span class="fragment fragment-photo"><svg><use href="#icon-document"/></svg><b>Prescription</b><em>in your camera roll</em></span>
                            <span class="fragment fragment-memory"><svg><use href="#icon-bell"/></svg><b>Next dose</b><em>in your head</em></span>
                        </div>
                        <div class="thread-bridge" aria-hidden="true">
                            <svg viewBox="0 0 120 80" preserveAspectRatio="none"><path d="M4 42C34 5 72 72 116 20"/></svg>
                            <span><svg><use href="#icon-arrow-right"/></svg></span>
                        </div>
                        <div class="thread-record">
                            <span class="thread-side-label">With Respaw</span>
                            <div class="record-surface">
                                <div class="record-surface-head"><span><svg><use href="#icon-paw"/></svg></span><div><small>Pet profile</small><strong>Milo</strong></div><i>3 yrs · Dog</i></div>
                                <div class="record-surface-line"><span class="surface-dot mint"></span><div><small>UPCOMING</small><b>Clinic appointment</b></div><em>Details stay close</em></div>
                                <div class="record-surface-line"><span class="surface-dot coral"></span><div><small>REMINDER</small><b>Medication due</b></div><em>Today · 9:00 AM</em></div>
                                <div class="record-surface-line"><span class="surface-dot lavender"></span><div><small>RECORD</small><b>Vaccination history</b></div><em>Saved with Milo</em></div>
                            </div>
                        </div>
                    </div>
                    <div class="thread-foot"><span><svg><use href="#icon-check"/></svg> Less hunting. More knowing.</span><span>One calm place</span></div>
                </div>

                <div class="paper-trail reveal" aria-label="Pet-care details gathered into one organized journey">
                    <article class="care-note note-one">
                        <span class="note-icon coral"><svg><use href="#icon-calendar"/></svg></span>
                        <small>Appointment note</small>
                        <strong>Future clinic visit</strong>
                    </article>
                    <article class="care-note note-two">
                        <span class="note-icon mint"><svg><use href="#icon-document"/></svg></span>
                        <small>Health record</small>
                        <strong>Vaccination history</strong>
                    </article>
                    <article class="care-note note-three">
                        <span class="note-icon lavender"><svg><use href="#icon-pill"/></svg></span>
                        <small>Medication</small>
                        <strong>Next dose reminder</strong>
                    </article>
                    <article class="care-note note-four">
                        <span class="note-icon yellow"><svg><use href="#icon-clinic"/></svg></span>
                        <small>Saved care</small>
                        <strong>Clinic details</strong>
                    </article>
                    <svg class="trail-line" viewBox="0 0 900 220" preserveAspectRatio="none" aria-hidden="true">
                        <path d="M5 95C150 205 255 5 420 105s285 100 475-35"/>
                    </svg>
                    <span class="trail-paw" aria-hidden="true"><svg><use href="#icon-paw"/></svg></span>
                </div>

                <div class="journey-phone reveal">
                    <div class="journey-phone-head">
                        <div><small>Care journey</small><strong>Everything in context</strong></div>
                        <span>Milo</span>
                    </div>
                    <ol class="timeline">
                        <li class="purple">
                            <span><svg><use href="#icon-calendar"/></svg></span>
                            <div><small>Upcoming</small><strong>Clinic appointment</strong><p>Visit details and preparation</p></div>
                        </li>
                        <li class="coral">
                            <span><svg><use href="#icon-bell"/></svg></span>
                            <div><small>Reminder</small><strong>Medication due</strong><p>Schedule kept with the record</p></div>
                        </li>
                        <li class="mint">
                            <span><svg><use href="#icon-document"/></svg></span>
                            <div><small>Record</small><strong>Vaccination history</strong><p>Documents and notes together</p></div>
                        </li>
                        <li class="yellow">
                            <span><svg><use href="#icon-pill"/></svg></span>
                            <div><small>Prescription</small><strong>Care instructions</strong><p>Easy to return to later</p></div>
                        </li>
                    </ol>
                </div>
            </div>
        </section>

        <section class="how section" id="how">
            <div class="shell">
                <div class="how-heading reveal">
                    <h2>From “what now?” to a clearer next step.</h2>
                    <p>Respaw will make the path through everyday pet care easier to see.</p>
                </div>
                <ol class="steps">
                    <li class="reveal">
                        <span class="step-number">1</span>
                        <div>
                            <h3>Find the right care</h3>
                            <p>Discover nearby veterinary care and view the details that matter.</p>
                        </div>
                    </li>
                    <li class="reveal">
                        <span class="step-number">2</span>
                        <div>
                            <h3>Choose how to consult</h3>
                            <p>Book a clinic visit, or choose video, audio, or chat when appropriate.</p>
                        </div>
                    </li>
                    <li class="reveal">
                        <span class="step-number">3</span>
                        <div>
                            <h3>Keep the next step close</h3>
                            <p>Return to profiles, records, prescriptions, reminders, and appointment history.</p>
                        </div>
                    </li>
                </ol>
            </div>
        </section>

        <section class="care section" id="care">
            <div class="shell care-heading reveal">
                <div>
                    <h2>Care that meets the moment.</h2>
                    <p>Start with nearby veterinary care, then choose the kind of appointment that fits the situation.</p>
                </div>
                <p class="sample-note">Product preview · final features may evolve before launch</p>
            </div>

            <div class="consultation-stage">
                <div class="shell consultation-grid">
                    <div class="modality-wrap reveal">
                        <div class="modality-rail" role="tablist" aria-label="Consultation options">
                            <button type="button" role="tab" aria-selected="true" aria-controls="consultation-preview" data-mode="clinic">
                                <svg><use href="#icon-clinic"/></svg><span>Clinic</span>
                            </button>
                            <button type="button" role="tab" aria-selected="false" aria-controls="consultation-preview" data-mode="video" tabindex="-1">
                                <svg><use href="#icon-video"/></svg><span>Video</span>
                            </button>
                            <button type="button" role="tab" aria-selected="false" aria-controls="consultation-preview" data-mode="audio" tabindex="-1">
                                <svg><use href="#icon-audio"/></svg><span>Audio</span>
                            </button>
                            <button type="button" role="tab" aria-selected="false" aria-controls="consultation-preview" data-mode="chat" tabindex="-1">
                                <svg><use href="#icon-chat"/></svg><span>Chat</span>
                            </button>
                        </div>
                        <div class="mode-copy" aria-live="polite">
                            <span class="mode-icon"><svg><use href="#icon-clinic"/></svg></span>
                            <div>
                                <small data-mode-label>Clinic consultation</small>
                                <strong data-mode-title>Plan an in-person visit</strong>
                                <p data-mode-copy>Find nearby veterinary care and keep the appointment details close.</p>
                            </div>
                        </div>
                    </div>

                    <article class="consult-phone reveal" id="consultation-preview">
                        <div class="phone-top"><span>9:41</span><span class="phone-status">● ●</span></div>
                        <div class="consult-preview-head">
                            <span class="back-dot"><svg><use href="#icon-chevron"/></svg></span>
                            <div><small>Choose how to connect</small><strong data-preview-title>Clinic consultation</strong></div>
                        </div>
                        <div class="location-preview" data-preview-scene="clinic">
                            <div class="search-line"><svg><use href="#icon-location"/></svg><span>Search by location</span></div>
                            <div class="map-preview" aria-hidden="true">
                                <span class="map-radius"></span>
                                <i class="map-pin pin-one"></i><i class="map-pin pin-two"></i><i class="map-pin pin-three"></i>
                            </div>
                            <div class="availability-line"><span>Care near you</span><strong>View options</strong></div>
                        </div>
                        <div class="remote-preview" data-preview-scene="remote" hidden>
                            <span class="remote-orbit orbit-a"></span><span class="remote-orbit orbit-b"></span>
                            <span class="remote-symbol"><svg><use href="#icon-video"/></svg></span>
                            <strong data-remote-title>Meet online</strong>
                            <p data-remote-copy>Connect from a quiet place when a remote consultation is appropriate.</p>
                        </div>
                        <button class="preview-button" type="button" tabindex="-1">Continue</button>
                    </article>
                </div>
            </div>

            <div class="records-band">
                <div class="shell records-grid">
                    <div class="records-copy reveal">
                        <h2>A clearer picture of their health.</h2>
                        <p>Profiles, records, prescriptions, reminders, and appointment history stay connected to the pet they belong to.</p>
                        <ul class="record-index">
                            <li><span>01</span> Pet profiles</li>
                            <li><span>02</span> Health records</li>
                            <li><span>03</span> Prescriptions</li>
                            <li><span>04</span> Reminders</li>
                            <li><span>05</span> Appointment history</li>
                        </ul>
                    </div>
                    <div class="record-previews reveal">
                        <article class="record-phone phone-profile">
                            <div class="phone-top"><span>9:41</span><span class="phone-status">● ●</span></div>
                            <div class="profile-hero">
                                <span class="avatar-paw"><svg><use href="#icon-paw"/></svg></span>
                                <div><small>Pet profile</small><strong>Milo</strong><p>Care details, all together</p></div>
                            </div>
                            <div class="profile-menu">
                                <span><svg><use href="#icon-document"/></svg> Health records</span>
                                <span><svg><use href="#icon-pill"/></svg> Prescriptions</span>
                                <span><svg><use href="#icon-bell"/></svg> Reminders</span>
                                <span><svg><use href="#icon-calendar"/></svg> Appointment history</span>
                            </div>
                        </article>
                        <article class="record-phone phone-history">
                            <div class="phone-top"><span>9:41</span><span class="phone-status">● ●</span></div>
                            <h3>Health records</h3>
                            <div class="history-tabs"><span>Timeline</span><span>Documents</span></div>
                            <ol class="mini-timeline">
                                <li><small>Today</small><strong>New note</strong><p>General observation</p></li>
                                <li><small>Earlier</small><strong>Clinic visit</strong><p>Visit summary</p></li>
                                <li><small>Earlier</small><strong>Prescription</strong><p>Care instructions</p></li>
                                <li><small>Earlier</small><strong>Vaccination</strong><p>Record saved</p></li>
                            </ol>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="urgent section" id="urgent">
            <div class="urgent-lines" aria-hidden="true"></div>
            <div class="shell urgent-grid">
                <div class="urgent-copy reveal">
                    <span class="urgent-symbol" aria-hidden="true"><svg><use href="#icon-heartbeat"/></svg></span>
                    <h2>When it feels urgent, clarity comes first.</h2>
                    <p>Respaw will offer calm, practical guidance and help you find appropriate veterinary care. It will not replace a veterinarian or emergency veterinary service.</p>
                    <a class="button button-coral button-arrow" href="#emergency-disclaimer">
                        <span>Understand urgent care</span>
                        <svg><use href="#icon-arrow-right"/></svg>
                    </a>
                </div>
                <div class="urgent-product reveal">
                    <article class="urgent-phone">
                        <div class="phone-top"><span>9:41</span><span class="phone-status">● ●</span></div>
                        <div class="urgent-phone-head">
                            <span><svg><use href="#icon-shield"/></svg></span>
                            <div><small>Urgent guidance</small><strong>What is happening?</strong></div>
                        </div>
                        <div class="urgent-option danger">
                            <svg><use href="#icon-warning"/></svg>
                            <div><strong>Severe or sudden symptoms</strong><p>Contact urgent veterinary care now</p></div>
                            <svg class="row-arrow"><use href="#icon-chevron"/></svg>
                        </div>
                        <div class="urgent-option unsure">
                            <svg><use href="#icon-chat"/></svg>
                            <div><strong>Not sure if it is urgent?</strong><p>Use guidance to choose a next step</p></div>
                            <svg class="row-arrow"><use href="#icon-chevron"/></svg>
                        </div>
                        <div class="urgent-option general">
                            <svg><use href="#icon-document"/></svg>
                            <div><strong>General guidance</strong><p>For less urgent concerns</p></div>
                            <svg class="row-arrow"><use href="#icon-chevron"/></svg>
                        </div>
                    </article>
                    <ul class="urgent-benefits">
                        <li><span class="coral"><svg><use href="#icon-warning"/></svg></span><div><strong>Immediate guidance</strong><p>Clear steps for urgent situations.</p></div></li>
                        <li><span class="mint"><svg><use href="#icon-location"/></svg></span><div><strong>Find appropriate care</strong><p>Options for nearby veterinary care.</p></div></li>
                        <li><span class="lavender"><svg><use href="#icon-document"/></svg></span><div><strong>Prepare and act</strong><p>Know what to do and what to share.</p></div></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="waitlist section" id="early-access">
            <div class="shell waitlist-grid">
                <div class="waitlist-copy reveal">
                    <h2>Be there from the first paw forward.</h2>
                    <p>Join the early-access list for launch news and a first look at Respaw. No pet records are collected here—just the details we need to keep you posted.</p>
                    <div class="waitlist-art" aria-hidden="true">
                        <span class="sun"></span>
                        <span class="hill hill-one"></span>
                        <span class="hill hill-two"></span>
                        <span class="waiting-pet"><svg><use href="#icon-paw"/></svg></span>
                        <i></i><i></i><i></i><i></i><i></i>
                    </div>
                </div>

                <div class="waitlist-panel reveal">
                    <div class="form-progress" aria-hidden="true">
                        <span class="active"><b>Sign up</b></span>
                        <span><b>Sending</b></span>
                        <span><b>Confirmation</b></span>
                    </div>
                    <form id="waitlist-form" novalidate>
                        <div class="form-fields">
                            <div class="field">
                                <label for="name">Your name</label>
                                <input id="name" name="name" type="text" autocomplete="name" required minlength="2" maxlength="80" placeholder="Your name" aria-describedby="name-error">
                                <span class="field-error" id="name-error"></span>
                            </div>
                            <div class="field">
                                <label for="email">Email address</label>
                                <input id="email" name="email" type="email" inputmode="email" autocomplete="email" required maxlength="120" placeholder="you@example.com" aria-describedby="email-error">
                                <span class="field-error" id="email-error"></span>
                            </div>
                        </div>

                        <fieldset class="interest-field">
                            <legend>I’m interested as</legend>
                            <div class="interest-options">
                                <label>
                                    <input type="radio" name="interest" value="pet-parent" checked>
                                    <span class="radio-ui"><svg><use href="#icon-profile"/></svg>A pet parent</span>
                                </label>
                                <label>
                                    <input type="radio" name="interest" value="veterinary-professional">
                                    <span class="radio-ui"><svg><use href="#icon-heartbeat"/></svg>A veterinary professional</span>
                                </label>
                            </div>
                        </fieldset>

                        <label class="consent">
                            <input id="consent" name="consent" type="checkbox" required aria-describedby="consent-error">
                            <span>I agree to receive Respaw launch updates. I can unsubscribe at any time.</span>
                        </label>
                        <span class="field-error" id="consent-error"></span>

                        <p class="form-error" id="form-error" role="alert" hidden></p>
                        <button class="button submit-button" type="submit">
                            <span class="submit-idle">Request early access</span>
                            <span class="submit-loading"><i aria-hidden="true"></i>Sending request…</span>
                        </button>
                        <p class="form-privacy">Your details are used only for Respaw launch communication. Read our <button type="button" data-dialog-open="privacy-dialog">privacy note</button>.</p>
                    </form>

                    <div class="success-state" id="waitlist-success" role="status" tabindex="-1" hidden>
                        <span class="success-icon"><svg><use href="#icon-check"/></svg></span>
                        <div>
                            <h3>You’re on the early-access list.</h3>
                            <p>We’ll keep it useful and only write when there’s something worth sharing.</p>
                        </div>
                        <button class="text-link" type="button" data-reset-form>Submit another request</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="faq section" id="faq">
            <div class="shell faq-grid">
                <div class="faq-heading reveal">
                    <h2>A few things worth knowing.</h2>
                    <p>Respaw is still being shaped for launch. Here is what we can say clearly today.</p>
                </div>
                <div class="faq-list reveal">
                    <details>
                        <summary>When will Respaw launch?<span><svg><use href="#icon-chevron"/></svg></span></summary>
                        <p>Respaw is in pre-launch development. Join the early-access list and we’ll share meaningful launch updates as the platform gets closer to release.</p>
                    </details>
                    <details>
                        <summary>What will I be able to do in Respaw?<span><svg><use href="#icon-chevron"/></svg></span></summary>
                        <p>Respaw is being built to help pet parents discover veterinary care, book clinic or online consultations, manage pet profiles and health records, access prescriptions, receive reminders, and revisit appointment history.</p>
                    </details>
                    <details>
                        <summary>Can Respaw help in an emergency?<span><svg><use href="#icon-chevron"/></svg></span></summary>
                        <p>Respaw will provide practical guidance and help people find appropriate care. It is not an emergency service. If a pet may be in immediate danger, contact a local veterinarian or emergency veterinary service now.</p>
                    </details>
                    <details>
                        <summary>Will Respaw replace my veterinarian?<span><svg><use href="#icon-chevron"/></svg></span></summary>
                        <p>No. Respaw is a technology platform intended to make access and organization easier. Veterinary professionals remain responsible for clinical assessment, advice, diagnosis, and treatment.</p>
                    </details>
                    <details>
                        <summary>How will my information be used?<span><svg><use href="#icon-chevron"/></svg></span></summary>
                        <p>The early-access form asks only for contact details and your general interest. Those details are intended for Respaw launch communication, not for pet records or clinical information.</p>
                    </details>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="shell footer-main">
            <div class="footer-brand">
                <img src="{{ asset('images/respaw-logo-wordmark.png') }}" alt="Respaw">
                <p>Respaw is an upcoming technology platform for pet-care access and organization.</p>
            </div>
            <nav class="footer-nav" aria-label="Legal and contact">
                <button type="button" data-dialog-open="privacy-dialog">Privacy</button>
                <button type="button" data-dialog-open="terms-dialog">Terms</button>
                <a href="mailto:hello@respaw.in">Contact</a>
            </nav>
            <a class="footer-email" href="mailto:hello@respaw.in">hello@respaw.in</a>
        </div>
        <div class="shell emergency-disclaimer" id="emergency-disclaimer">
            <span><svg><use href="#icon-warning"/></svg></span>
            <p><strong>If your pet may be in immediate danger, contact a local veterinarian or emergency veterinary service now.</strong> Respaw is not an emergency service.</p>
        </div>
        <div class="shell footer-bottom">
            <p>© <span data-year></span> Respaw. All rights reserved.</p>
            <p>Launching soon.</p>
        </div>
    </footer>

    <button class="back-to-top" type="button" aria-label="Back to top" data-back-to-top>
        <svg><use href="#icon-arrow-up"/></svg>
    </button>

    <dialog class="legal-dialog" id="privacy-dialog" aria-labelledby="privacy-title">
        <div class="dialog-head">
            <h2 id="privacy-title">Pre-launch privacy note</h2>
            <button type="button" aria-label="Close privacy note" data-dialog-close><svg><use href="#icon-close"/></svg></button>
        </div>
        <div class="dialog-body">
            <p>The early-access form asks for your name, email address, general interest, and consent to receive launch communication. It does not ask for pet-health or clinical information.</p>
            <p>These details are intended only for Respaw launch updates. You may ask to update or remove your early-access details by contacting <a href="mailto:hello@respaw.in">hello@respaw.in</a>.</p>
            <p>This short note is for the pre-launch website and will be replaced by Respaw’s full privacy policy before broader service availability.</p>
        </div>
    </dialog>

    <dialog class="legal-dialog" id="terms-dialog" aria-labelledby="terms-title">
        <div class="dialog-head">
            <h2 id="terms-title">Pre-launch website terms</h2>
            <button type="button" aria-label="Close terms" data-dialog-close><svg><use href="#icon-close"/></svg></button>
        </div>
        <div class="dialog-body">
            <p>This website describes an upcoming product. Features, availability, and launch timing may change before release.</p>
            <p>Respaw is a technology platform and does not replace a veterinarian, diagnosis, treatment, or emergency veterinary service. Do not use this website to submit clinical or emergency information.</p>
            <p>For questions about this pre-launch website, contact <a href="mailto:hello@respaw.in">hello@respaw.in</a>.</p>
        </div>
    </dialog>
</body>
</html>
