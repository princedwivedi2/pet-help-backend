# Pet-Help (RESPAW) — Emergency Feature Specification

**Status:** Proposed (new scope beyond the original 10-day plan)
**Author:** Product + Engineering
**Last updated:** 2026-06-29
**Replaces:** The cut "SOS / vet-comes-home" home-visit idea.

---

## 1. Overview

The Emergency feature gives pet owners a fast, telehealth-appropriate path when something is wrong *right now* — without promising a physical "vet comes to your home" service the platform can't reliably staff. It is built as **three layers** of decreasing availability and increasing capability, so a user always gets *something* useful even at 3 a.m. with no vet online.

**Design principles**
- Always return value: every layer degrades gracefully into the one below it.
- Reuse the existing telehealth stack (Agora, FCM, WebSocket, wallet, Razorpay, prescriptions) — minimize net-new infrastructure.
- The vet is the clinical decision-maker; the app never gives an autonomous diagnosis.
- Clear liability posture: first-aid content is informational, not a substitute for emergency veterinary care.

**The three layers at a glance**
1. **Emergency First-Aid Tips** — vet-approved static guidance, always available, works offline. No live vet needed.
2. **Instant "Talk to a vet now"** — on-demand live emergency video/audio consult with the first available vet.
3. **Nearby 24/7 Clinics** — directory/map for cases that need hands-on physical care.

---

## 2. The Three Layers

### Layer 1 — Emergency First-Aid Tips (always available, offline)
Curated, vet-approved guidance for common pet emergencies. **Lowest effort, highest baseline value, no live vet required.**

- **Covered emergencies (initial set):** poisoning, choking, seizure, heatstroke, bleeding, bloat / breathing trouble, hit-by-car, and similar high-frequency events.
- **Each entry is structured as:**
  - **Do this now** — immediate safe actions.
  - **Don't do this** — common harmful mistakes to avoid.
  - **Get to a vet immediately if…** — red-flag escalation triggers.
- **Content management:**
  - Stored as editable, structured content in the backend (not hard-coded).
  - Must be **vet-reviewed / approved** before publishing (review state on each entry).
  - Cached on-device so it works **offline**.
- **Disclaimer:** every entry carries a clear, persistent *"This is not a substitute for emergency veterinary care"* notice.

### Layer 2 — Instant "Talk to a vet now" (on-demand live consult)
The core on-demand connection: user taps one button and is connected to the first available vet by live video/audio.

- **Vet presence:** vets toggle an **on-call / available** status, reusing `availability_status` / `working_hours`.
- **Request → connect** (see §3 for the full sequence): user request → push + real-time banner to eligible online vets → first vet to accept locks the request → both join an Agora channel.
- **Concurrency safety:** the accept action is **atomic** (DB transaction / row lock) so two vets can never accept the same request; losing vets' banners clear immediately.
- **Timeout & fallback:** if no vet accepts within ~30–60s, **widen the pool / escalate**; if still none, fall back to **Layer 1 (first-aid tips) + Layer 3 (nearby clinics)** and auto-refund.

### Layer 3 — Nearby 24/7 Clinics (physical care)
For cases that need hands-on treatment the app cannot provide remotely.

- A **directory / map** of 24/7 emergency clinics.
- **Start hand-curated by city** (a vetted dataset), with an optional maps-API integration later for distance/directions.
- Surfaced both as a standalone entry and as a **disposition outcome** when a vet refers the user in person (see §5).

---

## 3. Connection Flow (Layer 2, numbered sequence)

1. **User triggers** — taps "Talk to a vet now" from an emergency entry point.
2. **Backend creates** a **pending emergency consultation** record.
3. **Payment hold** — user pays the emergency fee upfront via Razorpay; funds are **held** (reuse existing payment + hold flow).
4. **Notify pool** — backend notifies all **eligible online vets** simultaneously via **FCM push** + **WebSocket real-time banner**: *"Incoming emergency — Accept / Decline"* with a visible **countdown**.
5. **First accept wins** — the first vet to tap **Accept** atomically **locks** the request (DB transaction / lock). All other vets' banners **clear**.
6. **Join call** — both parties join an **Agora channel** using the existing **RTC token endpoint** → live video / audio consult.
7. **Timeout handling** — if no vet accepts within ~30–60s:
   - **Escalate / widen** the eligible pool (e.g., relax filters, broaden radius/specialty), then re-notify, **or**
   - **Fall back** → *"No vet available right now"* → show Layer 1 first-aid tips + Layer 3 nearby clinics, and **auto-refund** the held payment (reuse existing auto-refund).
8. **Disposition** — at call end the vet records an outcome (see §5); billing settles (see §4).

---

## 4. Pricing, Payments & Revenue Split

**Emergency fee (premium, fixed)**
- Emergency consults cost a **premium over a normal consult**. *Example:* normal ₹300 → emergency **₹600–800**.
- The fee is **platform-set and FIXED** — no per-vet haggling.

**Revenue split (favors the vet for emergencies)**
- Emergencies pay the vet a **higher share** than normal. *Example:* normal **80/20** (vet/platform) → emergency **90/10**.

**Money flow (all reuse existing rails)**
1. User pays the emergency fee upfront via **Razorpay**; funds **held**.
2. On call completion, the **vet's share** is credited to their **in-app wallet**; the platform keeps its **commission**.
3. Vet's wallet balance is **paid out to their bank on the normal payout cycle**.
4. If **no vet connects**, the held payment is **auto-refunded** (reuse existing auto-refund).

**Supply-side incentive levers (optional, phased)**
- **On-call / standby pay** — e.g. ~₹100/hr to stay available (overnight coverage).
- **Surge pricing** — raise the emergency fee when few vets are online, to pull supply on.

---

## 5. Vet Disposition (call outcome — the vet decides)

The vet is the clinical decision-maker and records one outcome at the end of the call. **Even a 2-minute resolution still charges the full emergency fee — the answer is the value.**

- **Manageable — home care:** vet gives advice + a **prescription** (reuse prescriptions / records); logged to the **pet's history**.
- **Refer to clinic — go in person now:** app surfaces **nearby 24/7 clinics** (Layer 3), with an optional vet note.
- **Needs follow-up:** drop the user into the **normal appointment booking** flow.

---

## 6. Reuse vs. New Build

**Reuse (already exists in the platform)**
- Agora calls + **RTC token endpoint**.
- **FCM push** notifications.
- **WebSocket** real-time service.
- **Wallet** (vet earnings + payout cycle).
- **Razorpay** payments + hold.
- **Prescriptions / medical records**.
- **Auto-refund** on failed connection.

**New build (net-new work)**
- **Live presence / availability tracking** for on-call vets.
- **Request → notify → claim → timeout** orchestration (with atomic claim / lock).
- **Emergency fee configuration** + **split logic** (premium fee, 90/10 split).
- **First-aid content store** + **admin** review/publish workflow.
- **Clinic dataset** (hand-curated 24/7 directory) + surfacing.
- **Disposition step UI** in the **vet app**.
- **Emergency entry points + "Talk to a vet now" UI** in the **user app**.

---

## 7. Liability & Safety Notes

- **First-aid content is informational only** — every entry shows *"not a substitute for emergency veterinary care."*
- Content must be **vet-reviewed and approved** before it can publish; track reviewer + review date per entry.
- The app **never auto-diagnoses**; clinical judgment always comes from a licensed vet (Layer 2) or directs the user to in-person care (Layer 3).
- Make the **fallback path explicit** when no vet is available, so a user in a real emergency is always pointed to physical care, not left waiting.
- Keep an **audit trail** of emergency requests, accepts, dispositions, and refunds for accountability and dispute handling.
- Surface the **"get to a vet immediately if…"** escalation triggers prominently, including inside the live consult.

---

## 8. Open Questions

- **Eligibility filters** for the vet pool: all online vets, or by species/specialty/locale? How wide does escalation go before fallback?
- **Timeout tuning:** exact accept window (30s? 60s?) and how many escalation rounds before fallback.
- **Standby pay funding:** who funds on-call standby pay, and does surge pricing fully cover it?
- **Signaling infra:** confirm the WebSocket/real-time layer can fan out emergency banners reliably (vs. polling) at the needed latency.
- **Clinic data sourcing & accuracy:** how is the 24/7 clinic dataset kept current; liability if a listed clinic is closed/wrong?
- **Concurrency at scale:** lock strategy under many simultaneous emergencies and a small vet pool.
- **Refund edge cases:** vet accepts then the call drops before any advice — refund, partial, or full charge?
- **Regulatory / insurance:** telehealth emergency-advice liability coverage and any regional constraints.
- **Abuse / misuse:** preventing non-emergencies from clogging the on-call pool (and the premium fee's role as a natural filter).
