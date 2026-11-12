# .agents Library (Offline / On-Demand Repository)

## Purpose
This directory houses components from the universal Agent bundle (Everything Claude Code / ECC) that are not part of the active stack for **PT Daijo Industrial MES** (Laravel 10/12, PHP 8.1/8.4, Blade, Tailwind CSS, Alpine.js, Livewire 3, MySQL, Redis).

By moving off-stack components into this `library` directory:
1. **Context Window Savings**: Up to 60% of token consumption in prompt overhead is eliminated on every turn.
2. **Unblocks Core Capabilities**: Built-in critical subagents (such as `browser` and `research`) and core skills that were previously excluded due to context budget limits can now run freely.
3. **Zero Data Loss**: No tools or instructions are deleted. Everything remains reachable on demand and can be promoted back in seconds.

---

## How to Promote a Component to DAILY

If your workflow requires any component from the library:

```bash
# To promote an agent:
mv .agents/library/agents/<agent-name>.md .agents/agents/

# To promote a skill:
mv .agents/library/skills/<skill-name> .agents/skills/

# To promote a rule:
mv .agents/library/rules/<rule-name>.md .agents/rules/

# To promote a workflow:
mv .agents/library/workflows/<workflow-name>.md .agents/workflows/
```

---

## Library Inventory

### 1. Agents (`.agents/library/agents/` — 45 agents)
- **C++**: `cpp-build-resolver.md`, `cpp-reviewer.md`
- **C# / .NET**: `csharp-reviewer.md`
- **Dart / Flutter**: `dart-build-resolver.md`, `flutter-reviewer.md`
- **Python / Django / FastAPI / ML**: `django-build-resolver.md`, `django-reviewer.md`, `fastapi-reviewer.md`, `python-reviewer.md`, `pytorch-build-resolver.md`, `mle-reviewer.md`, `rag-pipeline-reviewer.md`
- **F#**: `fsharp-reviewer.md`
- **Go**: `go-build-resolver.md`, `go-reviewer.md`
- **Java / JVM**: `java-build-resolver.md`, `java-reviewer.md`
- **Kotlin / Android**: `kotlin-build-resolver.md`, `kotlin-reviewer.md`
- **Rust**: `rust-build-resolver.md`, `rust-reviewer.md`
- **Swift / iOS**: `swift-build-resolver.md`, `swift-reviewer.md`
- **React / TS / Vue**: `react-build-resolver.md`, `react-reviewer.md`, `typescript-reviewer.md`, `vue-reviewer.md`
- **HarmonyOS**: `harmonyos-app-resolver.md`
- **Specialized / Non-MES**: `chief-of-staff.md`, `gan-evaluator.md`, `gan-generator.md`, `gan-planner.md`, `harness-optimizer.md`, `healthcare-reviewer.md`, `homelab-architect.md`, `loop-operator.md`, `marketing-agent.md`, `network-architect.md`, `network-config-reviewer.md`, `network-troubleshooter.md`, `opensource-forker.md`, `opensource-packager.md`, `opensource-sanitizer.md`, `seo-specialist.md`, `spec-miner.md`

### 2. Skills (`.agents/library/skills/` — 12 skills)
- `bun-runtime` (Bun package manager/runtime)
- `react-patterns`, `react-performance`, `react-testing` (React ecosystem)
- `vue-patterns`, `ui-to-vue` (Vue ecosystem)
- `motion-foundations`, `motion-patterns`, `motion-advanced` (Framer motion for React)
- `frontend-slides` (HTML slides generator)
- `hexagonal-architecture` (Ports & Adapters for TS/Java/Go)
- `continuous-learning` (Deprecated v1)

### 3. Rules (`.agents/library/rules/` — 15 rules)
- **React**: `react-coding-style.md`, `react-hooks.md`, `react-patterns.md`, `react-security.md`, `react-testing.md`
- **Vue**: `vue-coding-style.md`, `vue-hooks.md`, `vue-patterns.md`, `vue-security.md`, `vue-testing.md`
- **TypeScript**: `typescript-coding-style.md`, `typescript-hooks.md`, `typescript-patterns.md`, `typescript-security.md`, `typescript-testing.md`

### 4. Workflows (`.agents/library/workflows/` — 25 workflows)
- `cpp-build.md`, `cpp-review.md`, `cpp-test.md`
- `fastapi-review.md`
- `flutter-build.md`, `flutter-review.md`, `flutter-test.md`
- `gan-build.md`, `gan-design.md`
- `go-build.md`, `go-review.md`, `go-test.md`
- `gradle-build.md`
- `kotlin-build.md`, `kotlin-review.md`, `kotlin-test.md`
- `marketing-campaign.md`
- `python-review.md`
- `react-build.md`, `react-review.md`, `react-test.md`
- `rust-build.md`, `rust-review.md`, `rust-test.md`
- `vue-review.md`

