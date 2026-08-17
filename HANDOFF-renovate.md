# Handoff: automated dependency updates with Renovate

Status: **plan, not yet implemented.** All file/line references below were verified against
the working tree on 2026-08-16. Nothing has been changed on `main`. The decision for
Renovate over Dependabot is final — do not re-litigate it (rationale in §2 for context).

---

## 1. Why

Version pins are scattered across seven kinds of files (Dockerfile, a shell script, a
composite action, workflow YAML, composer, npm) with no automation, and they have already
drifted: **CI runs PHP 8.5.8** (`.github/actions/setup-dependencies/action.yaml:17`) while
**the production container runs PHP 8.5.9** (`Dockerfile:17`). Nobody noticed because each
pin is bumped by hand, independently.

Outcome wanted: one bot, one config file, PRs for every pin in the repo — including the
non-standard ones (an `ARG` in the Dockerfile, a variable in a bash script, a version
string inside a composite action) — with related pins grouped so PHP/Node can never drift
between CI and the container again.

## 2. Decision: Renovate, not Dependabot

Dependabot covers Docker `FROM` lines, GitHub Actions, composer and npm — but it cannot
touch `ARG MYSQL_CLIENT_VERSION` (`Dockerfile:40`), `NODE_MAJOR` in
`container/install-npm.sh:11`, or `php-version:` inside `action.yaml`. Those are exactly
the pins that keep drifting. Renovate's custom regex managers cover them, and its
grouping rules solve the drift problem. Decision is Renovate; the only open choice is
hosted vs self-hosted (§5).

## 3. Complete pin inventory

Standard managers pick these up with **zero config** (verify they appear in Renovate's
onboarding-PR dependency dashboard; if one is missing, something is wrong):

| File | Pin | Manager |
|---|---|---|
| `Dockerfile:2` | `node:26.7-alpine` | dockerfile |
| `Dockerfile:17` | `php:8.5.9-apache-trixie` | dockerfile |
| `Dockerfile:59` | `COPY --from=mlocati/php-extension-installer:2.11.12` | dockerfile (handles `COPY --from`) |
| `Dockerfile:66` | `composer:latest` | dockerfile — **must be pinned first, see §4 step 1** |
| `.github/workflows/*.yaml` | `actions/checkout@v7`, `docker/*@v4-v7`, `getsentry/action-release@v3`, `shivammathur/setup-php@v2`, `actions/cache@v6`, `actions/setup-node@v7` | github-actions |
| `.github/workflows/tests.yaml:13`, `ci.yaml:38` | service `image: mysql:8` | github-actions (service containers) |
| `composer.json` / `composer.lock` | app packages | composer |
| `package.json` / `package-lock.json` | app packages | npm |

These need **custom regex managers** (§4 step 3):

| File | Pin | Notes |
|---|---|---|
| `Dockerfile:40` | `ARG MYSQL_CLIENT_VERSION=8.4.9` | feeds a hand-built Oracle download URL on line 42 |
| `container/install-npm.sh:11` | `NODE_MAJOR=26` | major-only value |
| `.github/actions/setup-dependencies/action.yaml:17` | `php-version: '8.5.8'` | must be grouped with the Dockerfile PHP image |
| `.github/actions/setup-dependencies/action.yaml:39` | `node-version: '26'` | major-only value |

Explicitly **not** managed: `composer.json`'s `"php": "^8.5.8"` platform constraint (it's
a range, not a pin — leave it; only widen it manually if a grouped PHP PR needs it) and
`ghcr.io/blt950/where2fly:latest` in `docker-compose.yml:4` (that's this app's own image).

## 4. Implementation steps

### Step 1 — pin `composer:latest` (separate commit, before the Renovate config)

`Dockerfile:66` uses `composer:latest`, which silently changes between builds and is
invisible to any update bot. Look up the current stable 2.x tag on Docker Hub
(`docker.io/library/composer`) and pin the exact version, e.g.
`COPY --from=docker.io/library/composer:2.9.4 ...` — check what's actually current, don't
copy that example number. Renovate then manages it like every other image.

### Step 2 — align the service images with prod

`tests.yaml:13` and `ci.yaml:38` use `mysql:8`. Prod is MySQL **8.4 LTS** (see CLAUDE.md
and the 8.4.x client in the Dockerfile). Change both to `mysql:8.4` so CI tests against
the prod line and Renovate proposes in-line updates instead of an unwanted 8→9 major.

### Step 3 — write `renovate.json5` (repo root)

Starting point below. **Do not trust the exact field names from memory** — Renovate
renamed several config fields over the last year (e.g. `fileMatch` →
`managerFilePatterns`). After writing the file, validate it; the validator is
authoritative:

```bash
npx --yes --package renovate -- renovate-config-validator renovate.json5
```

```json5
{
  "$schema": "https://docs.renovatebot.com/renovate-schema.json",
  "extends": ["config:recommended"],
  "schedule": ["before 6am on monday"],
  "labels": ["dependencies"],
  "packageRules": [
    // MySQL: stay on the 8.4 LTS line everywhere (client ARG + CI service images)
    { "matchDepNames": ["mysql"], "allowedVersions": "/^8\\.4\\./" },
    // PHP must move in lockstep: Dockerfile image + CI php-version in one PR.
    // This is the drift this whole effort exists to prevent.
    { "groupName": "php", "matchDepNames": ["php"] },
    // Node likewise: Dockerfile image + install-npm.sh + setup-node
    { "groupName": "node", "matchDepNames": ["node"] },
    // Optional, low-risk automerge — confirm with Daniel before enabling:
    // { "matchManagers": ["github-actions"], "matchUpdateTypes": ["minor", "patch"], "automerge": true }
  ],
  "customManagers": [
    {
      "customType": "regex",
      "managerFilePatterns": ["/^Dockerfile$/"],
      "matchStrings": ["ARG MYSQL_CLIENT_VERSION=(?<currentValue>[\\d.]+)"],
      "depNameTemplate": "mysql",
      "datasourceTemplate": "docker"
    },
    {
      "customType": "regex",
      "managerFilePatterns": ["/^container/install-npm\\.sh$/"],
      "matchStrings": ["NODE_MAJOR=(?<currentValue>\\d+)"],
      "depNameTemplate": "node",
      "datasourceTemplate": "node-version",
      "extractVersionTemplate": "^v?(?<version>\\d+)"
    },
    {
      "customType": "regex",
      "managerFilePatterns": ["/^\\.github/actions/setup-dependencies/action\\.yaml$/"],
      "matchStrings": ["php-version: '(?<currentValue>[\\d.]+)'"],
      "depNameTemplate": "php",
      "datasourceTemplate": "docker"
    },
    {
      "customType": "regex",
      "managerFilePatterns": ["/^\\.github/actions/setup-dependencies/action\\.yaml$/"],
      "matchStrings": ["node-version: '(?<currentValue>\\d+)'"],
      "depNameTemplate": "node",
      "datasourceTemplate": "node-version",
      "extractVersionTemplate": "^v?(?<version>\\d+)"
    }
  ]
}
```

Known rough edges to resolve while validating (these are the parts most likely to need
iteration — check current Renovate docs rather than guessing):

- **Major-only pins** (`NODE_MAJOR=26`, `node-version: '26'`): the `node-version`
  datasource returns full versions (`26.7.0`); `extractVersionTemplate` reducing to the
  major is the intent, but verify Renovate diffs `26` → `28` cleanly. If it misbehaves,
  the fallback is `datasourceTemplate: "docker"`, `depNameTemplate: "node"` with a
  `versioning` that tolerates major-only values.
- **PHP grouping across datasources**: the Dockerfile image dep is named `php` (docker
  datasource) and the regex manager also names it `php` — the `groupName: "php"` rule
  should catch both. Confirm in the dependency dashboard that one PHP PR touches both
  files. Same check for the `node` group (it spans the alpine image tag `26.7-alpine`,
  a bare `26`, and setup-node's `26` — different granularities, grouping still works,
  but eyeball the first grouped PR).
- The Dockerfile PHP tag is `8.5.9-apache-trixie`; the docker manager treats
  `-apache-trixie` as a tag suffix and preserves it. Nothing to configure, just verify
  the first PHP PR proposes e.g. `8.5.10-apache-trixie`, not a bare version.

### Step 4 — enable the bot

Two options; default to the first unless Daniel objects:

1. **Hosted Mend Renovate GitHub App** (recommended): install from the GitHub
   Marketplace on the `where2fly` repo. It opens an onboarding PR containing its view of
   discovered dependencies — use that PR's dependency list as the acceptance check
   against §3 before merging. Free; no CI to maintain; handles `composer.lock` /
   `package-lock.json` regeneration server-side.
2. **Self-hosted** via `renovatebot/github-action` on a weekly cron workflow, if
   granting a third-party app write access is unacceptable. More moving parts: needs a
   PAT/GitHub App credential, and lock-file regeneration runs in your CI minutes.

### Step 5 — acceptance checks

- [ ] `renovate-config-validator` passes.
- [ ] Onboarding PR / dependency dashboard lists **every** pin from §3 — most
      importantly the four custom-manager ones.
- [ ] The MySQL entries (ARG + two service images) show 8.4.x updates only, no 9.x.
- [ ] The first PHP PR touches `Dockerfile` **and** `action.yaml` together and CI is
      green on it (the whole `ci.yaml` matrix, including the docker build job — that
      build is also what catches a MySQL client version whose Oracle tarball URL doesn't
      exist yet, a rare but real failure mode for the hand-built URL on `Dockerfile:42`).
- [ ] `composer:latest` no longer appears anywhere.

## 5. Gotchas / context

- **The Oracle MySQL client URL is hand-built** (`Dockerfile:41-48`) from the ARG. A
  Docker Hub `mysql` tag existing does not guarantee the corresponding
  `dev.mysql.com/get/Downloads/...` tarball exists. Acceptable risk: the docker build in
  `ci.yaml` fails loudly on the Renovate PR, not on main. Don't try to "fix" this with a
  custom datasource unless it actually bites.
- **Don't bundle** the `composer:latest` pin, the `mysql:8` → `mysql:8.4` change, and
  the Renovate config into one commit — three separate concerns, three commits (repo
  convention: small, focused commits).
- Run `./vendor/bin/pint` only if PHP files change (none should in this task).
- **`HANDOFF-docker-build.md` overlaps this work — verified, not hypothetical.** Its plan
  restructures the same Dockerfile sections: it keeps the `ARG MYSQL_CLIENT_VERSION=8.4.9`
  pin but switches the download to the `-minimal` tarball (different URL, same version
  scheme), and narrows the `COPY --from=frontend` line. The custom regex here
  (`ARG MYSQL_CLIENT_VERSION=`) survives that refactor unchanged, but line numbers cited
  in this doc will shift, and the "does the Oracle tarball exist" gotcha then applies to
  the *minimal* tarball URL instead. Preferred order: land the docker-build refactor
  first, then this Renovate setup — the acceptance checks in §5 of this doc re-verify
  everything against whatever the Dockerfile looks like at that point. If Renovate lands
  first, re-run the §5 checks after the docker-build refactor merges.
- `HANDOFF-maplibre.md` is unrelated (frontend map) — ignore it.
