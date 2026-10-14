<!-- BEGIN:nextjs-agent-rules -->

## This is NOT the Next.js you know

This version has breaking changes — APIs, conventions, and file structure may all differ from your training data. Read the relevant guide in `node_modules/next/dist/docs/` (resolved from this file's directory; in monorepos the `next` package may not be visible from the repo root) before writing any code. Heed deprecation notices.

This block is written and re-added by `next dev` — verify at `node_modules/next/dist/server/lib/generate-agent-files.js`. Removing it from a diff only re-creates the uncommitted change; committing it with your work keeps the tree clean.

<!-- END:nextjs-agent-rules -->

## SECURITY-FIRST DEVELOPMENT RULE

Before creating files, writing test fixtures, executing commands, running scripts, or testing uploads:

1. Assess whether the operation could execute code, modify the system, expose data, or trigger security software.
2. Prefer harmless mock data and isolated test environments.
3. Never create or execute real malware, backdoors, web shells, reverse shells, or dangerous payloads for routine testing.
4. Never disable or bypass security software to make development easier.
5. Never repeat a security-sensitive operation that could affect my computer without explaining the risk and obtaining my approval when necessary.
6. Investigate antivirus detections instead of automatically dismissing them.
7. Run security and regression checks before declaring a feature complete.
8. Clean up test artifacts safely and never touch unrelated user files.
9. Explain security-sensitive testing decisions clearly.
10. If the safe approach is uncertain, stop the risky operation and ask me before proceeding.

Apply this rule to the entire Secure Asset Portal and future work in this project. Follow it even when a different model, agent, or automated workflow is used.
