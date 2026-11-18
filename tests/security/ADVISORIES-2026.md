# Rhymix security advisories reviewed for XE Core

Reviewed against the `stable-1.11` working tree on 2026-11-09. Only XE Core is changed; `/home/yjsoft/rhymix-security` is a read-only reference.

| Upstream issue | XE issue | Result |
| --- | --- | --- |
| [48 / RVE-2026-28](https://github.com/rhymix/rhymix-security/issues/48) | [1](https://github.com/YJSoft/xe-core-security/issues/1) | Fixed widget preview authorization against the source document's module and secret-document permissions. XE's `documentItem::isAccessible()` only checks a password session, so the Rhymix check cannot be copied verbatim. |
| [49 / RVE-2026-29](https://github.com/rhymix/rhymix-security/issues/49) | — | Already blocked: `Security::_parseURL()` rejects ASCII control characters and spaces, and accepts only HTTP(S) schemes. Login/signup redirects use `Security::getSafeRedirectURL()`. Added regressions for embedded control characters, percent-encoded schemes, and safe fallbacks. No new issue is needed. |
| [50 / RVE-2026-30](https://github.com/rhymix/rhymix-security/issues/50) | — | The affected `ncenterlite` notification producer and templates are not shipped in this XE Core repository. The reported notification XSS path therefore does not exist in Core. Separately installed notification addons have not been audited. No new issue is needed. |
| [51 / RVE-2026-31](https://github.com/rhymix/rhymix-security/issues/51) | [4](https://github.com/YJSoft/xe-core-security/issues/4) | Reproduced: unknown editor components retained executable frontend directives after purification. Blocked custom hyphenated attributes except `data-*`, and removed components that cannot render. The attack requires a frontend library that interprets those attributes. |
| [52 / RVE-2026-32](https://github.com/rhymix/rhymix-security/issues/52) | [2](https://github.com/YJSoft/xe-core-security/issues/2) | Removed dangerous intermediate extensions, irrespective of case, count, or surrounding whitespace; neutralized dangerous final extensions. Applied to attachments and the skin, layout, RSS, and integration-search paths that retain original filenames. |
| [53 / RVE-2026-33](https://github.com/rhymix/rhymix-security/issues/53) | [3](https://github.com/YJSoft/xe-core-security/issues/3) | Applied routed-module authorization to save, load, and delete; bound author identity; filtered new and legacy content; validated pending attachments and existing upload sessions before restoration. Fixed guest file ownership checks and removed implicit deletion during iframe upload. |

## Compatibility and behavior

- Production changes use PHP 5.3-compatible syntax and APIs. Standalone security regressions run on PHP 5.3.29, 5.4.45, and 8.4.27.
- The request's `module_srl` cannot redirect autosave storage away from the routed `current_module_info`.
- Third-party modules without standard write grants remain supported through their access grant. Page autosave requires manager permission; member-module autosave is disabled.
- Text-only editors may restore text without receiving file-upload privileges. Older editor clients may omit `editor_sequence` when their submitted target already belongs to an issued upload session.
- Guest autosaves require their secret cookie; legacy IP-only recovery is deliberately disabled because multiple users may share an IP address. Pending guest attachments require proof in the current upload session; member attachments require ownership or session proof.
- Existing upload sessions without a module binding must be reissued by reopening the editor.

## Verification

Run `php tests/security/regressions.php`, with `php53` and `php54` as well when installed. The standalone tests cover denied/allowed previews, filename normalization, CSTI filtering, autosave author/module mismatches, shared-IP isolation, legacy-content filtering, published/foreign attachment protection, and file deletion rights.

`codecept run unit` includes the existing compatibility suite and actual XML-to-PHP query compilation checks ensuring that guest `member_srl = 0` remains in ownership conditions.

An isolated temporary PHP HTTP fixture also exercised real multipart upload handling in PHP 5.4 and 8.4: four dangerous filenames through each actual skin/layout upload controller, plus allowed and cross-module-denied requests through the normal/iframe upload controllers (24 checks total). These requests used temporary files and model fixtures; they did not run against an installed production site.

Final local result: security regressions passed 424 checks each on PHP 5.3.29 and 5.4.45, and 409 checks on PHP 8.4.27 (the pre-existing suite has version-dependent cases). The unit suite completed 210 tests / 622 assertions with one skipped test and no failures. All 15 changed PHP files passed syntax checks on these three runtimes; the source and generated editor JavaScript passed Node syntax checks.
