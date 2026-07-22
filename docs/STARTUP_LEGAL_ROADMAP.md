# RESPAW / Pet-Help — Startup Setup + Legal & Compliance Roadmap

**For:** Founder, RESPAW / Pet-Help (India — pet telehealth platform; ₹, Razorpay, IST, Indian vets + pet owners)
**Prepared:** July 2026
**Domain owned:** respaw.in

> ⚠️ **IMPORTANT — This is informational, not legal or financial advice.**
> I am **not a lawyer, Chartered Accountant (CA), or Company Secretary (CS)**. This document summarises what is *typically* required to set up and run a fundable tech startup in India as of mid-2026, based on public sources. Rules, thresholds, fees, and government portals change frequently. **Before doing anything binding — incorporation, tax registration, contracts, telemedicine positioning — get sign-off from a qualified CA/CS and a lawyer.** Items I am confident are factual are marked **[Fact]**; items you must have a professional confirm for your specific case are marked **[Confirm with professional]**.

---

## (a) Executive Summary — Immediate Next Moves

RESPAW is a multi-sided marketplace: it collects money from pet owners, splits it with vets, handles personal + pet-health data, and facilitates **veterinary teleconsultation** — an area that in India is **regulated for the human side and only lightly/advisory-covered for the animal side.** That combination means you have real compliance surface area, but nearly all of it is routine and inexpensive to set up early. The one genuine grey zone is teleconsultation legality (see section g), which needs a lawyer's opinion before public launch.

**Do these first (this month), roughly in order:**

1. **Decide entity → register a Private Limited Company** via SPICe+ on MCA V3. This is the fundable structure and unlocks DPIIT, 80-IAC, and clean cap-table for investors. (Section b)
2. **Get DSC + DIN for directors**, reserve the name "RESPAW" (Part A), then file incorporation (Part B). Timeline ~7–10 working days. (Section b)
3. **Open a current account** and get **PAN/TAN** (auto-issued with SPICe+). (Sections b, c)
4. **Register on Udyam (MSME)** — free, instant, unlocks the ₹4,500 trademark fee and other benefits. (Section c)
5. **Apply for GST registration** — you are almost certainly a **mandatory** registrant as an "e-commerce operator" collecting payments on behalf of vets, *regardless of turnover*. (Section c) **[Confirm with CA]**
6. **Apply for DPIIT "Startup India" recognition** on the NSWS portal — free, ~7–14 days, and it makes the trademark rebate, 80-IAC tax holiday, and self-certification available. (Section d)
7. **File the trademark for "RESPAW"** (word mark + logo) in the right classes while you still have the DPIIT/MSME rebate. (Section i)
8. **Lock down the urgent legal documents** already scoped in your app — Terms of Service, Privacy Policy, Refund/Cancellation policy, Vet Partner Agreement, and the emergency medical disclaimer — and have a lawyer review them before launch. (Section e)
9. **Get a lawyer's written opinion on veterinary teleconsultation positioning** (RVP-only, disclaimers, prescription limits) — the single most important risk item. (Section g)

**Who you'll need to hire:** a **CA** (incorporation, GST, TDS, bookkeeping, 80-IAC) — most bundle incorporation for ₹8k–₹25k; a **lawyer** (contracts, telemedicine opinion, IP, DPDP) — ₹15k–₹75k depending on scope; optionally a **CS** later (mandatory only past certain thresholds, but useful for fundraising/ROC filings).

---

## (b) Company Registration — Recommended Entity + Step-by-Step

### Options compared

| Structure | Owners/Directors | Fundraising fit | Compliance load | Cost (all-in) | Best for |
|---|---|---|---|---|---|
| **Private Limited (Pvt Ltd)** | ≥2 directors, ≥2 shareholders | **Excellent** — VCs/angels invest via equity, ESOPs, priced rounds | Higher (annual ROC, audit, board) | ~₹7,500–₹25,000 | **Recommended** — a fundable tech startup |
| **LLP** | ≥2 partners | Poor — investors rarely fund LLPs | Lower | ~₹10,000+ | Services firms not raising equity |
| **OPC (One Person Co.)** | 1 director/shareholder | Weak — must convert before raising | Medium | ~₹15,000–₹35,000 | Solo founder, no near-term raise |
| **Sole Proprietorship** | 1 | None — not a separate legal entity | Lowest | Minimal | Hobby/tiny local operation only |

**[Fact]** For a startup that intends to raise from angels/VCs, issue ESOPs to early hires, and present a clean cap table, the **Private Limited Company is the standard and strongly recommended choice.** It is also the entity that best unlocks DPIIT + 80-IAC benefits. LLP is cheaper but investors generally won't fund it. OPC only makes sense if you are a **solo** founder with no co-founder and no near-term raise — and even then you'd convert to Pvt Ltd before a round. **[Confirm with CA]** whether OPC-now-then-convert vs. Pvt-Ltd-now is better for your specific co-founder situation.

### SPICe+ registration — step by step

**[Fact]** All new companies incorporate through the **SPICe+ web form on the MCA V3 portal**. It's a single integrated application that also delivers PAN, TAN, GSTIN (optional), EPFO, ESIC, professional tax (in some states), and bank-account opening.

1. **Get Digital Signature Certificates (DSC)** for all proposed directors (Class 3, ~₹1,000–₹2,000 each).
2. **SPICe+ Part A** — reserve the company name (e.g., "Respaw Technologies Private Limited"). Name-approval govt fee ~₹1,000. Have 1–2 backups; ensure it doesn't clash with existing companies/trademarks.
3. **SPICe+ Part B** — file incorporation: director details (DIN is auto-allotted here if directors don't have one), registered office address, authorised & paid-up capital, MOA/AOA, and the linked forms **AGILE-PRO-S** (GST/EPFO/ESIC/bank) and **INC-9** (declarations).
4. **Pay stamp duty** — varies by state where the registered office sits (from ~₹100 to ₹10,000+). **[Confirm with CA]** the figure for your state.
5. **Receive the Certificate of Incorporation (COI)** with CIN, plus **PAN and TAN**. **[Fact]** Typical timeline is **~7–10 working days** from DSC to COI.

**[Fact]** For authorised capital up to ₹15 lakh, the **SPICe+ government incorporation fee is ₹0** — most of your cost is DSC, stamp duty, and professional fees. A standard 2-director Pvt Ltd with ₹1 lakh authorised capital typically lands at **₹7,500–₹25,000 all-in** in 2026.

**Practical notes:** keep authorised capital modest (e.g., ₹1–10 lakh) to keep stamp duty low; you can increase it later before a raise. Use a proper registered-office address (a virtual office is acceptable in many states **[Confirm with CA]**). Draft the MOA object clause to clearly cover "technology platform for pet care, veterinary teleconsultation facilitation, and allied services."

---

## (c) Tax & Financial Registrations (GST, TDS, Udyam, Bank Account, PT)

### GST registration

**[Fact]** Ordinary threshold: **₹20 lakh** aggregate turnover for service providers (₹10 lakh in Manipur, Mizoram, Nagaland, Tripura). **BUT** — **[Fact]** a business that qualifies as an **"e-commerce operator"** (a platform that facilitates supply of goods/services by others and collects the consideration) must register for **GST regardless of turnover**, from day one.

**[Confirm with CA — this is important for RESPAW]** Because RESPAW collects the consultation fee from pet owners and then settles the vet's share, you are very likely treated as an **e-commerce operator** under GST, which triggers:
- **Mandatory GST registration** irrespective of turnover.
- **GST TCS obligation** — e-commerce operators collect **1% (0.5% CGST + 0.5% SGST)** on the net value of taxable supplies made by sellers (vets) through the platform, deposit it, and file **GSTR-8**.
- GST on **your own commission/platform fee** (the 10–20% you keep).

The exact treatment (are you an "operator" collecting on behalf of vets, or providing the service yourself?) materially changes your GST filings and must be confirmed by a CA who reviews your contracts and money flow. This is the single most consequential tax question for your model.

### TDS on vendor (vet) payouts

**[Fact]** **Section 194-O** requires an e-commerce operator to deduct **TDS at 0.1%** (reduced from 1% w.e.f. 1 Oct 2024) of the gross amount credited to an e-commerce participant (the vet). **[Fact]** No TDS if payments to a resident individual/HUF vet don't exceed **₹5 lakh** in a financial year (and they've furnished PAN/Aadhaar). You'll also handle standard TDS on salaries/professional fees/rent as you scale. **[Confirm with CA]** which sections apply to which payout types.

### Udyam / MSME registration

**[Fact]** Free, paperless, instant, permanent (no renewal) on the official **udyamregistration.gov.in** portal. Needs the authorised signatory's Aadhaar, the enterprise PAN, and GSTIN. **Benefits:** the **50% trademark fee rebate** (₹9,000 → ₹4,500 per class), protection against delayed payments, collateral-free loan schemes (CGTMSE), and various subsidies. Do this early — it's zero-cost upside. ⚠️ Use only the official gov.in portal; many paid look-alike sites charge for a free service.

### Professional Tax (PT)

**[Fact]** A **state-level** tax on employers and salaried staff, applicable in states like Maharashtra, Karnataka, West Bengal, Telangana, Tamil Nadu, etc. — **not levied in some states** (e.g., Delhi, UP, Haryana have no PT). You register for PT (employer + employee) in states where it applies once you have employees. **[Confirm with CA]** based on your registered-office state.

### Bank account

**[Fact]** Open a **current account** in the company's name using the COI, PAN, MOA/AOA, and board resolution. SPICe+/AGILE-PRO-S can initiate account opening with a partner bank; you can also open separately. You'll need this account linked to Razorpay for settlements.

---

## (d) Startup India / DPIIT Recognition (+ 80-IAC Tax Holiday)

### DPIIT recognition

**[Fact]** DPIIT "Startup India" recognition is **free** (zero government fee), applied online via the **NSWS portal**, approved in **~7–14 days**.

**Eligibility [Fact]:**
- Incorporated as a **Pvt Ltd, LLP, registered partnership, or cooperative** (another reason to pick Pvt Ltd/LLP over proprietorship).
- **< 10 years** since incorporation.
- **Turnover < ₹200 crore** in any year since incorporation.
- Not formed by splitting/reconstructing an existing business.
- Working on **innovation/improvement** of a product/process/service, or a **scalable model** with employment/wealth potential. (RESPAW's telehealth platform fits this well.)

**Benefits [Fact]:** Section **80-IAC** tax holiday (3 years), **50% trademark fee rebate** and **80% patent rebate** plus government-borne facilitator fees, **self-certification** under 6 labour + 3 environmental laws, easier access to **public procurement/tenders**, and eligibility for the **Startup India Seed Fund**. **[Fact]** Angel tax was **abolished effective 1 April 2025** (no DPIIT recognition needed for that benefit anymore).

### 80-IAC tax holiday (separate application)

**[Fact]** The 3-year income-tax holiday under **Section 80-IAC** is a **separate application to the Inter-Ministerial Board (IMB)**, restricted to **Pvt Ltd and LLP** only. You choose any **3 consecutive years out of your first 10**. **[Fact]** The incorporation-eligibility window was **extended to 1 April 2030** (startups incorporated between 1 Apr 2016 and 1 Apr 2030 qualify), turnover must be **< ₹100 crore** in the relevant year, and the IMB reviews within ~120 days. Apply once you have some financials to show. **[Confirm with CA]** on timing — claiming the holiday in your first profitable years is usually optimal.

---

## (e) Legal Documents the App Must Have (Urgent vs. Later)

Your `docs/` already scope most of these features (payments, refunds, KYC, emergency disclaimers, consultations). The documents below turn those features into enforceable, compliant policies. **Have a lawyer review the urgent set before public launch.**

| Document | Purpose | Priority |
|---|---|---|
| **Terms of Service / User Agreement** | Governs pet-owner use; limits liability; disclaims that RESPAW is a facilitator, not the treating vet | 🔴 **Urgent (pre-launch)** |
| **Privacy Policy** | DPDP-compliant notice: what data (owner + pet health), why, rights, grievance officer, retention. Must be a live URL for app stores | 🔴 **Urgent (pre-launch)** |
| **Refund & Cancellation Policy** | Covers auto-refund on vet no-show, cancellation windows, emergency-fee rules — you already have auto-refund logic | 🔴 **Urgent (pre-launch)** |
| **Emergency / Medical Disclaimer** | "Not a substitute for emergency veterinary care"; app never auto-diagnoses; RVP is the decision-maker (matches your EMERGENCY_FEATURE_SPEC §7) | 🔴 **Urgent (pre-launch)** |
| **Consent flows** (in-app) | Explicit, granular DPDP consent for data collection, health records, location (SOS), notifications | 🔴 **Urgent (pre-launch)** |
| **Vet Onboarding / Partner Agreement** | RVP registration warranty, KYC, commission/split, payout terms, code of conduct, indemnity, teleconsult standards, termination | 🔴 **Urgent (before onboarding real vets)** |
| **Payment/settlement terms** | Split logic, TCS/TDS handling, payout cycle, wallet terms | 🟠 **Before real money moves** |
| **Community/Content Guidelines & UGC policy** | Moderation of community + blog; IP ownership of content | 🟡 **Before community goes live** |
| **Grievance Redressal / Contact policy** | Named grievance officer (DPDP + IT Rules expectation) | 🟠 **Pre-launch (light version)** |
| **Cookie/Tracking policy (web)** | For respaw.in landing/web app | 🟡 **Before web launch** |

**Note:** Templates get you 70% there but the vet agreement and telemedicine disclaimers are RESPAW-specific and liability-sensitive — those two especially should be lawyer-drafted or lawyer-reviewed, not copy-pasted.

---

## (f) Data Protection & DPDP Compliance

**[Fact]** India's **Digital Personal Data Protection (DPDP) Act, 2023** is the governing privacy law; the **DPDP Rules, 2025** were notified on **13 Nov 2025** and phase in — **consent-manager framework by ~13 Nov 2026**, and **full substantive obligations by 13 May 2027.** This gives you runway, but you should build compliant from the start because retrofitting consent is painful.

RESPAW is a **Data Fiduciary** (you determine purpose/means of processing personal data). Key obligations **[Fact]**:

- **Privacy notice** in clear, plain language at the point of collection — what data, why, how to exercise rights, how to complain. The Rules contemplate availability across India's 22 scheduled languages for consent notices; at minimum plan English + Hindi.
- **Consent** must be **free, specific, informed, unconditional, unambiguous**, and separately obtained (not bundled into T&C). Users can **withdraw** consent as easily as they gave it.
- **Data-principal rights**: access, correction, erasure, grievance redressal, and nominate. Build UI/endpoints for these.
- **Children's data**: **verifiable parental consent** required for under-18s. Likely low-risk for you (pet owners are adults), but if minors can register, you must gate it. **[Confirm with lawyer]**
- **Breach notification**: notify the **Data Protection Board** and affected users, with the Rules pointing to prompt/72-hour-type timelines. Have an incident process (you already keep audit trails — extend them to a breach playbook).
- **Processor contracts**: your processors — Firebase (Google), Razorpay, hosting, FCM, any Agora/WebRTC vendor — must be bound by DPDP-adequate data-processing terms. Review these.
- **Security safeguards**: encryption, access control, retention limits. (Your private-disk KYC/pet-doc storage with signed URLs is a good start.)

**[Fact]** Penalties are severe — up to **₹250 crore** for failing reasonable security safeguards. **[Confirm with lawyer]** on **data localization / cross-border transfer**: DPDP allows transfers except to government-restricted countries, but because you use Firebase/Google infrastructure that may store data outside India, get an opinion on where personal + pet-health data physically resides and whether that's acceptable. Pet health records are not formally "sensitive personal data" the way human health data is, but treat them cautiously.

---

## (g) Veterinary Teleconsultation Legality + Medical Disclaimers

**This is your biggest regulatory grey area — treat it as the top legal priority and get a written lawyer opinion before public launch.**

**What is clear [Fact]:**
- Veterinary practice in India is governed by the **Indian Veterinary Council Act, 1984**. Only a **Registered Veterinary Practitioner (RVP)** — registered with the Veterinary Council of India (VCI) or a State Veterinary Council — may practise veterinary medicine. **Practising without registration is illegal and punishable.**
- **NITI Aayog issued advisory "Telemedicine for Livestock Health" guidelines (2023)** and there's a NITIVeT portal — but these are **advisory and focused on livestock**, not a binding statute for companion-animal (pet) teleconsultation.
- **There is currently no comprehensive, binding regulatory framework specifically for companion-animal / pet veterinary teleconsultation in India.** (Contrast with the human side, which has the 2020 Telemedicine Practice Guidelines.)
- Certain acts **cannot** be done via telemedicine even under the livestock guidance — e.g., fitness/health/trauma certificates, quarantine clearance, birth/death certificates, euthanasia — because they need physical examination.

**What this means for RESPAW [Confirm with lawyer — do not launch public teleconsult without this]:**
1. **Enforce RVP-only.** Every consulting vet must be a verified RVP (registration number captured and checked at KYC). Your Vet Partner Agreement should warrant current registration and require notice on lapse. Non-RVPs must not give clinical advice on the platform.
2. **Position RESPAW as a facilitator/technology platform**, not a provider of veterinary services — the vet is the clinical decision-maker (this matches your EMERGENCY_FEATURE_SPEC design principles, which is good).
3. **Adopt the human Telemedicine Practice Guidelines as a voluntary best-practice baseline** for the vet side (consent, record-keeping, identification, prescription discipline) since there's no pet-specific statute — a defensible posture. **[Confirm with lawyer]**
4. **Prescriptions online:** only RVPs may prescribe; be conservative about scheduled/prescription-only drugs via teleconsult and follow drug-scheduling law. Log every prescription to the pet record (you already do). **[Confirm with lawyer]** on what a vet may prescribe on a first remote consult without physical exam.
5. **Disclaimers (already well-scoped in your spec — keep them):** persistent "not a substitute for emergency veterinary care" on first-aid content; app never auto-diagnoses; explicit fallback to in-person/24-7 clinics; "get to a vet immediately if…" red-flag triggers surfaced prominently, including inside the live consult.
6. **Emergency-advice liability & insurance:** get **professional indemnity / platform liability insurance** and confirm vets carry their own cover. (Your spec's Open Question #8 flags exactly this — resolve it before launch.)
7. **Audit trail** of requests, accepts, dispositions, refunds (you already keep this) — essential for dispute defence.

**Bottom line:** the legality hinges on *who* delivers the advice (must be an RVP) and *how you position liability* (facilitator + disclaimers). Because the pet-teleconsult framework is unsettled, a lawyer's written opinion is not optional here.

---

## (h) Payments & Payout Compliance

**[Fact]** **Razorpay is an RBI-authorised Payment Aggregator (PA)**, PCI DSS Level 1. Using Razorpay means you don't need your own PA licence — you operate as a **merchant/marketplace on top of a licensed aggregator**, which is the right approach for a startup.

**For collecting from owners and paying out to vets [Fact]:**
- **Razorpay Route** is the marketplace product: it splits a single payment across **Linked Accounts** (your commission vs. the vet's share), handles held funds, settlements, refunds, and reconciliation. This maps directly onto your "payment held → captured on completion → vet share to wallet → payout on cycle → auto-refund on no-show" flow.
- **Sub-merchant (vet) KYC:** as the marketplace operator you must onboard vets with **KYC** (PAN, bank, GST if applicable) through Razorpay's verification before routing funds to them. Your existing vet-KYC step should feed this.
- **RBI PA guidelines (updated Sept 2025) + PCI DSS v4.0.1**: keep website/app policies (T&C, privacy, refund, contact) live and complete — aggregators check these before activation. Marketplace operators must monitor for suspicious transactions and align settlement controls with RBI's AML framework. **[Confirm with CA/lawyer]**.

**Tax at the payment layer (ties to section c):**
- **GST TCS 1%** on vet supplies through your platform (if you're an e-commerce operator) → **GSTR-8**.
- **Income-tax TDS 0.1% (Sec 194-O)** on vet payouts above ₹5 lakh/year for individuals.
- **GST on your commission.**
Get your CA to design the settlement + tax-deduction logic **before** real money flows, so Razorpay Route splits and your accounting agree. **[Confirm with CA]**

**Do not:** hold customer funds in your own account for long periods or act like an unlicensed aggregator — route through Razorpay's held/settlement mechanism. **[Confirm with lawyer]**

---

## (i) IP / Trademark

**[Fact]** File a trademark for **"RESPAW"** as a **word mark** and, separately, the **logo/device mark** (a combined mark protects both together but a word mark gives the broadest name protection — many startups file both).

**Classes to consider [Confirm with a trademark attorney on exact specification]:**
- **Class 9** — downloadable software / mobile app.
- **Class 42** — SaaS, software development, cloud platform services (the core "tech platform" class).
- **Class 44** — **medical/veterinary services** (covers the veterinary-care angle; **not** Class 42).
- (Optionally **Class 35** if you run marketplace/advertising/business services.)

**[Fact]** Fees: **₹4,500 per class** for individuals/**DPIIT startups**/**Udyam MSMEs**, vs ₹9,000 for others — so register **Udyam and/or DPIIT first** to halve the cost. Under Startup India, the government also bears facilitator fees (you pay only statutory fees). **[Fact]** Timeline: examination in ~1–3 months, a 4-month opposition window, total **~8–12 months** for an uncontested mark, but you get "™" use and priority from the filing date.

**Also:** you already own **respaw.in** — good. Consider defensively registering **.com** and social handles. Do a **trademark + company-name search** before filing to avoid conflicts (a clash can also block your SPICe+ name approval).

---

## (j) App-Store Legal Prerequisites

**Google Play [Fact]:**
- **Developer account** — register as an **Organization account** (not personal). **Health apps must migrate to a verified Organization account** (a compliance push with deadlines around early 2026), so start as an org.
- **Data safety form** — declare exactly what data you collect/share and why; keep it updated and consistent with your Privacy Policy.
- **Privacy Policy URL** in Play Console **and** an in-app link.
- If you touch health-data permissions, be ready to justify necessity; and if you present health/first-aid content without regulatory clearance, include the standard-style disclaimer that the app is **not a medical device and does not diagnose/treat** (aligns with your emergency disclaimers).
- **Content rating** questionnaire.

**Apple App Store [Fact]:**
- **Apple Developer Program** membership ($99/yr).
- **Privacy Policy** URL (rejections commonly cite a missing/weak one).
- **App Privacy "nutrition labels"** + a **privacy manifest (`PrivacyInfo.xcprivacy`)** declaring data use and any "required-reason" APIs — a 2026 submission requirement.
- Health/medical claims are scrutinised — keep copy to "connect with a vet" / "information only," avoid diagnostic claims.

**Both:** consistent Privacy Policy + data declarations across app store, app, and website; a working support/contact channel; and no claims your teleconsult "replaces" emergency care.

---

## (k) Phased Checklist — Now / 30 Days / Before Launch / Before Fundraising

Rough costs are indicative 2026 ranges; professional fees vary by city and firm. **[Confirm all figures with the professional you hire.]**

### 🟢 NOW (Week 1–2) — foundation
| Task | Who | Rough cost |
|---|---|---|
| Decide entity (recommend **Pvt Ltd**) + reserve name | You + CA | — |
| DSC for directors | CA/vendor | ₹1k–2k/director |
| Incorporate via SPICe+ (COI, PAN, TAN) | CA | ₹8k–25k all-in |
| **Udyam (MSME)** registration | You/CA | ₹0 (official portal) |
| Open current account | You + bank | ₹0 |
| Trademark + name search for "RESPAW" | TM attorney | ₹1k–3k (search) |

### 🟡 NEXT 30 DAYS — registrations & IP
| Task | Who | Rough cost |
|---|---|---|
| **GST registration** (likely mandatory as e-commerce operator) | CA | ₹1.5k–5k prof. fee |
| **DPIIT Startup India** recognition (NSWS) | You/CA | ₹0 |
| **Professional Tax** registration (if applicable in your state) | CA | ₹1k–3k |
| **Trademark filing** — "RESPAW" word + logo, Classes 9/42/44 | TM attorney | ₹4.5k/class (DPIIT/MSME) + prof. fee |
| Razorpay account + **Route** setup, business KYC | You | ₹0 (per-txn fees apply) |
| Engage a **lawyer** for the document + telemedicine work | You | ₹15k–75k scope-dependent |

### 🟠 BEFORE PUBLIC LAUNCH — compliance & legal docs
| Task | Who | Rough cost |
|---|---|---|
| **Lawyer opinion on vet teleconsultation** (RVP-only, disclaimers, prescriptions) | Lawyer | included in scope |
| Lawyer-reviewed **ToS, Privacy Policy, Refund, Vet Partner Agreement, medical disclaimer** | Lawyer | included in scope |
| **DPDP consent flows** + grievance officer + breach playbook | You + lawyer | — |
| **Vet KYC → Razorpay sub-merchant KYC** wired up; TCS/TDS logic in settlement | CA + you | — |
| **Professional indemnity / platform liability insurance** | Broker | quote-based |
| **Google Play (Org account) + Apple** developer accounts, data-safety/privacy manifests, content rating | You | $25 (Play, one-time) + $99/yr (Apple) |
| Verify all vets are **registered RVPs**; capture registration numbers | You | — |

### 🔵 BEFORE FUNDRAISING — investor-readiness
| Task | Who | Rough cost |
|---|---|---|
| **80-IAC** tax-holiday application to IMB (once financials exist) | CA | prof. fee |
| Clean **cap table**, share certificates, statutory registers, board minutes | CS | ₹5k–15k/yr retainer |
| **Founders' Agreement + ESOP pool** setup | Lawyer | ₹20k–60k |
| Increase authorised capital if needed for the round | CA/CS | filing fees |
| Data room: incorporation docs, IP, contracts, compliance certificates, DPDP posture | You + advisors | — |
| Appoint auditor; ensure ROC annual filings (AOC-4, MGT-7) current | CA/CS | audit + filing fees |

---

## Who to Hire — quick guide

- **Chartered Accountant (CA):** incorporation, GST/TCS/TDS setup and returns, bookkeeping, 80-IAC, audit, payout tax logic. **Your day-one hire.**
- **Lawyer (startup/tech + IP):** ToS/Privacy/Refund/Vet agreements, **veterinary teleconsultation opinion**, DPDP, trademark strategy, founders'/ESOP docs. **Your second essential hire, before launch.**
- **Company Secretary (CS):** ROC compliance, registers, board resolutions, cap-table hygiene — **mandatory only above certain thresholds** but valuable from the fundraising stage. **[Confirm with CA]** when you legally need one.

---

## Sources

- [Startup India — DPIIT Recognition](https://www.startupindia.gov.in/content/sih/en/startupgov/startup_recognition_page.html) · [80-IAC](https://www.startupindia.gov.in/content/sih/en/form80iac.html) · [Startup India Scheme](https://www.startupindia.gov.in/content/sih/en/startup-scheme.html)
- [SPICe+ / Pvt Ltd registration cost & process (RegisterKaro)](https://www.registerkaro.in/post/cost-of-company-registration-in-india-a-complete-breakdown) · [LegalSuvidha SPICe+ on MCA V3](https://legalsuvidha.com/blog/spicespice-plus-mca-form-for-company-registration) · [TaxRupees Pvt Ltd guide](https://www.taxrupees.com/article/private-limited-company-registration-india/)
- [DPIIT Recognition 2026 (Patron Accounting)](https://www.patronaccounting.com/blog/dpiit-startup-recognition-2026-benefits-eligibility-application-tax) · [Section 80-IAC (ClearTax)](https://cleartax.in/s/section-80iac-of-income-tax-act)
- [GST registration thresholds (ClearTax)](https://cleartax.in/s/gst-registration-limits-increased) · [Section 194-O TDS (ClearTax)](https://cleartax.in/s/section-194o) · [TDS/GST on e-commerce (Parpella)](https://www.parpella.com/income-tax-and-gst-applicability-on-ecommerce/)
- [DPDP Act 2023 & DPDP Rules 2025 (EY India)](https://www.ey.com/en_in/insights/cybersecurity/decoding-the-digital-personal-data-protection-act-2023) · [DPDP Rules 2025 notified (PIB PDF)](https://static.pib.gov.in/WriteReadData/specificdocs/documents/2025/nov/doc20251117695301.pdf) · [Seclore DPDP Rules 2025 guide](https://www.seclore.com/fundamentals/dpdp-rules-2025-compliance-guide/)
- [NITI Aayog Telemedicine for Livestock Health (PDF)](https://www.niti.gov.in/sites/default/files/2023-07/Telemedicine-for-Livestock-Health_Inside%20Report_18072023.pdf) · [Veterinary telemedicine in India (Pashudhan Praharee)](https://www.pashudhanpraharee.com/veterinary-telemedicine-in-india-a-new-horizon-for-transforming-animal-healthcare/) · [Digital Health & Telemedicine legal guide India (CMS)](https://cms.law/en/int/expert-guides/cms-expert-guide-to-digital-health-apps-and-telemedicine/india)
- [Razorpay Route](https://razorpay.com/route/) · [Payment Gateway Compliance 2026 (Razorpay)](https://razorpay.com/blog/payment-gateway-compliance/) · [Payment Gateway KYC Onboarding 2026 (Razorpay)](https://razorpay.com/blog/payment-gateway-kyc-onboarding-india/)
- [Trademark for software Class 9 & 42 (Patron Accounting)](https://www.patronaccounting.com/blog/trademark-for-software-and-apps-class-9-and-42) · [Trademark fees India 2026 (Intepat)](https://www.intepat.com/blog/trademark-registration-fees-india) · [Trademark classes for startups (Naraway)](https://naraway.com/Blogs/trademark-classes-india-complete-guide-startups-2026.html)
- [Google Play Data Safety section](https://support.google.com/googleplay/android-developer/answer/10787469?hl=en) · [Google Play health apps 2026 requirements](https://myappmonitor.com/blog/google-play-health-apps-update-2026-requirements) · [App Store & Play submission guide 2026 (Primocys)](https://primocys.com/blog/submit-app-to-app-store-google-play/)
- [Udyam / MSME registration (official portal)](https://udyamregistration.gov.in/) · [Udyam benefits 2026 (Kotak)](https://www.kotak.bank.in/en/stories-in-focus/loans/business-loan/udyam-registration-certificate-benefits-msme.html)

---

*Prepared as informational research to help you plan next steps. Not legal, tax, or financial advice. Confirm everything marked **[Confirm with professional]** — and anything binding — with a qualified CA, CS, and lawyer before acting.*
