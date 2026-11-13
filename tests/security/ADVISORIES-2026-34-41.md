# Applicability of RVE-2026-34 through RVE-2026-41 to XE Core

Reviewed on 2026-11-13 against `stable-1.11`, starting at `dbcd8aeeb`. The Rhymix sources and issues are references only. `master` remains at `d60f6a4b4812bf0fc05725a70b75f1e976234b05`; no fixes were applied there.

Sources: [Rhymix issue 54](https://github.com/rhymix/rhymix-security/issues/54), [RVE-2026-34 branch](https://github.com/rhymix/rhymix-security/compare/master...security/rve-2026-34), [Rhymix issue 55](https://github.com/rhymix/rhymix-security/issues/55), [RVE-2026-41 branch](https://github.com/rhymix/rhymix-security/compare/security/rve-2026-31...security/rve-2026-41). Retrieved with authenticated `gh` CLI.

| RVE | XE applicability | XE issue and fix |
| --- | --- | --- |
| 2026-34 | Reproduced: comment and reply forms expose another author's consultation document or comment. | [#5](https://github.com/YJSoft/xe-core-security/issues/5): check actual module membership and strict document/comment access before supplying parent objects to a skin. |
| 2026-35 | Reproduced: module managers can classify administrators as spammers; the content removal phase acts across modules. | [#6](https://github.com/YJSoft/xe-core-security/issues/6): require a site-wide administrator for the action, view, and popup entries, and reject administrator/self/invalid targets. |
| 2026-36 | Reproduced: page update accepts another module's ID and overwrites its module/page type; a missing ID becomes an insert. | [#7](https://github.com/YJSoft/xe-core-security/issues/7): separate insert/update, verify target page and manager permission, bind routed page identity, preserve immutable fields. Creation remains available to administrators and virtual-site administrators in their current site. |
| 2026-37 | Not applicable: XE has no `procMemberRegisterDevice` or `procMemberLoginWithDevice` action/implementation. Its existing `memberController::doLogin()` administrator-IP check has no `procMemberLogin`-only action condition. This conclusion concerns the reported device-API bypass, not an audit of every possible authentication extension. | No XE issue or patch. |
| 2026-38 | Reproduced in XE's `document.getDocumentVotedMemberList` API: it checks public-voter settings but ignores actual source-document visibility. The reported Rhymix board history/log views are absent in XE. | [#8](https://github.com/YJSoft/xe-core-security/issues/8): load the full document and check its actual module, consultation, secret, and unpublished-document restrictions before querying voters. |
| 2026-39 | Reproduced: submitted counters reach document/comment INSERT arguments, and comment IP is user-controlled. | [#9](https://github.com/YJSoft/xe-core-security/issues/9): initialize counters and IP on the server; derive attachment counts from files. Preserve explicitly trusted manual-import/restoration data. |
| 2026-40 | Reproduced on PHP 5.3: board settings initialization replaces its module information using the requested ID without rechecking target permissions. | [#10](https://github.com/YJSoft/xe-core-security/issues/10): validate target board, target manager/access grants, and original module identity before exposing settings. Invoke `syncModuleToSite()` through its model instance for PHP 8 compatibility. |
| 2026-41 | Not applicable to the reported mechanism: XE does not encode/sign/restore `rx_encoded_properties` or `rx_encoded_datas`. Its component customization adds attributes only to `img` and `div`; no signed attribute is decoded back onto an `iframe`. | No XE issue or production patch. Added malicious-payload regressions and verified the filtered output in Chromium. |

## Compatibility and scope

Production code uses PHP 5.3-compatible syntax and APIs. The original no-argument behavior of `documentItem::isAccessible()` and `commentItem::isAccessible()` is retained; callers that need complete visibility checks opt into `isAccessible(true)`. Comment/reply forms and voter lookup use this strict check.

Spammer management has site-wide effects, so module administrators and virtual-site administrators do not receive that feature merely because they manage one module. Ordinary page creation remains available to the appropriate virtual-site administrator, with the site ID taken from the current site rather than arbitrary request data.

The unrelated bug/potential-issue list under Rhymix issue 54 is outside this RVE-specific patch set. No new issues were created for features absent in XE, and no changes were made to the Rhymix checkout.

## Verification

- Before each fix, an executable regression using the actual affected XE method failed on the reported authorization/data-integrity condition. Query I/O and unrelated UI dependencies use local fixtures; no live database records or accounts were modified.
- `php53 tests/security/regressions.php`: 499 checks passed on PHP 5.3.29.
- `php54 tests/security/regressions.php`: 499 checks passed on PHP 5.4.45.
- `php tests/security/regressions.php`: 484 checks passed on PHP 8.4.27. The existing suite contains version-dependent checks.
- `codecept run unit`: 210 tests, 622 assertions, one existing skipped test, no failures.
- All 13 changed PHP files passed `php -l` on PHP 5.3, 5.4, and 8.4. `git diff --check` passed.
- Three RVE-2026-41 payloads, including the published nested-component example and iframe restoration attempts, were passed through XE's actual HTMLPurifier implementation and rendered in Chromium. No executable iframe attributes or script execution were observed. Safe iframe sources remain intact.

The local RVE-2026-41 browser fixture is in `/tmp/xe-rve-41-review/`; the unit-test output is in `/tmp/xe-rve-34-41-unit.log`.
