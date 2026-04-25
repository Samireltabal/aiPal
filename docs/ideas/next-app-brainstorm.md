# Next App Brainstorm — Built on aiPal

**Date:** 2026-04-23
**Status:** Brainstorm complete, decision pending — to be picked up in a separate project.

## Context

aiPal v1.2.0 just shipped. Looking for the next product to build on top of the aiPal foundation. Two tracks, two separate apps (not one combined product):

- **Track A:** Paying customers / business
- **Track B:** Portfolio / hireability

aiPal infrastructure available to reuse: provider-agnostic LLM layer, WhatsApp + Telegram bots, workflow engine (scheduled / webhook / message-prefix triggered), semantic memory (pgvector), RAG, voice in/out, tasks/reminders/notes, Google Calendar + Gmail, PWA + Web Push, fully self-hosted, AGPL-3.0 (copyright owned by Samir → dual-licensing open).

---

## Initial 8 ideas (filtered)

Killed by advisor as too crowded / well-funded competition:
- ❌ PR review SaaS (CodeRabbit, Greptile, Qodo, Graphite)
- ❌ Zapier-for-AI (n8n, Make pivoting here)
- ❌ Personal CRM (Monica, Dex, Clay — graveyard, users won't pay)
- ❌ AI unified inbox (Superhuman, Shortwave own mindshare)

Survived:
- ✅ WhatsApp-native SMB assistant for MENA
- ✅ Vertical SaaS on the workflow engine
- ✅ aiPal Cloud (hosted multi-tenant)

---

## Track A — Revenue play

**Shape:** WhatsApp-first vertical SaaS for MENA SMBs. Multi-tenant hosted version of aiPal's workflow + bot + memory stack, wrapped in a vertical-specific UX.

**Reuses ~70% of aiPal:** provider-agnostic LLM layer, WhatsApp bot, workflow engine, semantic memory, reminders.

**Net-new:** tenant isolation, billing, vertical-specific onboarding flow, minimal web dashboard for SMB owner, WhatsApp Business API / BSP integration.

**Success metric:** paying design partners → MRR. Not GitHub stars.

### Vertical shortlist

| Vertical | WhatsApp-native? | ACV | Sales cycle | Demo-ability | Regulatory drag |
|---|---|---|---|---|---|
| **Real estate agents** | Yes, already live there | High | Long | Medium | Low |
| **Tutoring centers / private tutors** | Yes | Low–Med | Short | High | Low |
| Small clinics / dentists | Partial | Med | Medium | Medium | **High (patient data)** |
| Salons / barbers | Yes | Low | Very short | High | Low |

**Top 2:** real estate (bigger checks, longer close) or tutoring (faster cycles, no regulation). Skip clinics (compliance), skip salons (ACV too low for self-hosted).

**Product shape (same for both):** *"your staff WhatsApp number becomes an AI that handles routine inquiries, books appointments, follows up on leads, and surfaces what needs a human."*

**Open question:** which vertical do you have warmer intros into? Distribution beats product quality at this stage — pick the one where you can land 5 paying design partners in 60 days. Network geography (Egypt / Gulf / elsewhere) also drives Arabic-first decisions and pricing.

---

## Track B — Portfolio play

**Hireability signal that matters in 2026:** evidence of production judgment, not GitHub stars. What hiring managers at AI-forward companies look for:
1. Shipped product with real users (aiPal already counts)
2. Eval harness / systematic prompt engineering
3. Cost + latency awareness (model routing, caching, batching)
4. Observability — can explain why an agent failed last Tuesday

### 4 candidates

| Option | What it is | Demo wow | Hireability signal | Risk |
|---|---|---|---|---|
| **B1: Memory-as-a-service** | Extract aiPal's pgvector memory into a standalone API. Decay, summarization, multi-agent isolation, eval harness. (mem0 competitor) | Medium | High — mem0 = $50M+ valuation, market validated | mem0 / Zep funded and ahead |
| **B2: Chat-agent eval harness** | Open-source framework for evaluating multi-turn chat agents. Scripted convos, assertions, cost/latency tracking, regression detection. | Medium | Medium | Eval tools rarely monetize → portfolio-only |
| **B3: Pi-based offline voice assistant** | Ollama + Whisper + Piper + aiPal memory + workflows, fully local, wake-word, plug-and-play box. | **Very high** | High — "ships real systems, not wrappers" | Hardware demos hard to scale |
| **B4: Codebase onboarding agent** | Point at any repo → living architecture doc, Q&A, drift detection between code and docs. | Medium | Medium | Crowded (DeepWiki, Sourcegraph) |

**Advisor's ranking:** B3 > B1 > B2 > B4.

**The fork:**
- **Demo video + write-up gets you noticed** → **B3 (Pi voice assistant)**
- **Portfolio piece should also have users / could become a product** → **B1 (memory service)**

---

## Cross-cutting decisions to make before building

1. **License posture.** aiPal is AGPL-3.0; you own the copyright. For Track A SaaS, either (a) dual-license aiPal commercially, or (b) architect the SaaS as a separate app talking to aiPal over an API. Decide BEFORE writing code.
2. **Track A vertical.** Tutoring vs real estate — driven by warmer-intro network, not TAM slide.
3. **Track B intent.** Demo/write-up reach (B3) or product traction (B1).
4. **Geographic focus for Track A.** Egypt / Gulf / elsewhere → drives language priorities (Arabic-first?), pricing, BSP partner choice.

---

## Compounding angle (advisor's note)

Even running both tracks, **Track B should ideally be a byproduct of Track A**, not a parallel first-class build — solo builders don't have the hours for two parallel products. The lowest-risk version is:
- Build Track A
- Spin out one technical write-up per month from real Track A decisions (provider-agnostic layer, workflow matcher, local-vs-cloud cost tradeoffs)
- That write-up cadence + Track A traction = stronger portfolio than two half-finished GitHub repos

If you do want a second artifact, **B2 (eval harness)** compounds with Track A because you'll need it anyway to make the WhatsApp bot reliable.

---

## Next session — pick up here

Re-read this file, then answer:
1. Track A: tutoring or real estate? (network-driven, not TAM-driven)
2. Track A: geography → Arabic-first, BSP choice, pricing tier
3. Track B: B3 (Pi voice assistant) or B1 (memory service)? — driven by whether you want demo reach or product traction
4. License posture: dual-license aiPal or build SaaS as separate API consumer?

Then spec the MVP for whichever app you choose to start.
