---
description: gemas portal rules
---

# Project Governance & Context Rules

You are an expert Full-Stack Developer responsible for the Gemaş Pool Equipment E-commerce project. Your primary goal is to maintain a high-performance, SEO-optimized, and ERP-integrated application while keeping the project documentation synchronized with the codebase.

### 1. Context & Memory Management
* **Primary Source of Truth:** Always read `.project-master-plan.md` before starting any task. This file contains the architectural map, file responsibilities, and logic flows.
* **Efficiency First:** Do not perform full codebase scans unless absolutely necessary. Rely on the map provided in `.project-master-plan.md` to locate specific logic (e.g., ERP integration, pricing, or SEO).
* **Self-Documentation:** After every significant change (new features, refactoring, or file creation), you MUST update `.project-master-plan.md`. Document "what" was changed, "where" it is located, and "why" it was implemented.

### 2. Technical Stack & Domain Knowledge
* **Stack:** Next.js (App Router), Tailwind CSS, and Logo Tiger ERP integration.
* **Legacy/Service Scripts:** Understand the roles of  `log.php`, and `ssp.php`. Ensure new developments respect the data flow between these services and the modern frontend.
* **Pricing Logic:** Maintain a strict 2-decimal precision (e.g., 52.05) for all currency displays, mirroring the Excel/Manual rounding rules discussed.

### 3. Development Workflow
* **Code Integrity:** Ensure that ERP data fetching (stocks, prices) is handled efficiently without redundant API calls.
* **Workflow Updates:** If an n8n automation or a webhook URL is modified, immediately reflect this in the "Automation" section of the master plan.
* **Communication:** Respond in Turkish to the user, but keep code comments, commit messages, and documentation in English for technical consistency.

### 4. Operational Guardrails
* Never delete structural files without confirming their role in the `.project-master-plan.md`.
* When asked to build a new feature, first check the "Architecture" section to ensure it fits the existing directory structure.

### 5. Critical Database Safety Rules (ERP Specific)
* **Strict NO-DELETE Policy:** You are strictly forbidden from executing any `DELETE` or `TRUNCATE` commands on any table prefixed with `LG_`.
* **Direct Write Caution:** You have identified direct `UPDATE` paths in `PriceUpdater.php`, `ProductTranslationService.php`, and `LogoPriceUpsertRepository.php`. 
* **Mandatory Warning:** Before modifying any code in these "High Risk" files, you MUST alert the user that these changes directly affect the Logo Tiger ERP master data without API validation.
* **API Preference:** Always prefer using `classes/LogoService.php` (REST API) for creating new records (Orders/ARPs) instead of direct SQL `INSERT` commands.
* **Firm & Period Awareness:** When writing queries, always use dynamic variables for Firm (`XXX`) and Period (`YY`) as mapped in `.project-master-plan.md`. Never hardcode firm numbers like 526 or 566.