# Handoff: Docker image & CI build optimization

Reviewed plan (Fable, 2026-08-16) based on the Opus proposal from earlier in this session.
Two findings from the original plan were corrected during review — read **Corrections** before
implementing, since they change what to build. Everything else was verified and stands.

## Corrections to the original plan

### C1. The "wrong SHA" checkout bug (#9) is not a bug — downgrade to cleanup

The claim was that `repository: blt950/where2fly` without `ref:` in the build-container
checkout resolves to the default branch. Verified against `actions/checkout`'s
`src/input-helper.ts`: it compares the `repository` input case-insensitively against
`github.repository`, and when they match (`isWorkflowRepository`), it defaults to the
**triggering** ref/SHA. This workflow lives in `blt950/where2fly` (confirmed via
`git remote -v`), so branch pushes have always built the correct commit.

**Action:** still delete the `repository:` line — it's redundant and misleading — but as a
cosmetic cleanup, not a correctness fix. Nothing needs re-tagging or auditing.

### C2. Composer BuildKit cache mount is a no-op in this CI — do the full split instead

`RUN --mount=type=cache` contents live in the BuildKit builder's local state and are **not
exported** by `cache-to: type=gha` (long-standing BuildKit limitation; the workaround,
`buildkit-cache-dance`, isn't worth the complexity here). GHA runners are ephemeral, so the
composer cache mount would start empty on every single CI build and save nothing.

**Action:** do the lockfile-first composer split (task 3b below) instead, paired with the CI
smoke test (task 11). Risk note softening the original warning: if
`bootstrap/cache/packages.php` is missing at boot, Laravel's `PackageManifest` rebuilds it
lazily from `vendor/composer/installed.json` (bootstrap/cache is www-data-writable), so a
misordered discovery step degrades to a first-request rebuild, not a silent provider outage.
The genuinely fatal misorder is a missing/incomplete **autoloader** — which fails instantly
on any request and is exactly what the smoke test catches. Consult the `laravel-13` skill if
any doubt about Laravel 13 bootstrap/discovery behavior.

## Hard constraints (from Daniel)

- **Keep `vim` and `nano`** in the image — used interactively for in-container edits.
- **Keep the Oracle MySQL client** (not MariaDB/`default-mysql-client`) — README.md line 23
  documents why: protocol/feature parity with the MySQL 8.4 server. The trim below keeps the
  exact same Oracle binaries and the `MYSQL_CLIENT_VERSION` pin; only the packaging changes.
- Keep **both** `mysql` and `mysqldump` (interactive debugging + scheduled `backup:run`).
- Workflow job ordering stays sequential: lint → tests → build-container. Do not parallelize.

## Ordering constraint

Do task 2 (MySQL trim) **before or together with** task 1 (GHA cache). With the current
1.46 GB extracted-MySQL layer, `mode=max` caching would churn the 10 GB per-repo GHA cache
budget and evict itself. After the trim the full layer set is well under 1 GB and caches
comfortably.

## Tasks

### 1. GHA layer cache — `.github/workflows/ci.yaml`, build & push step

```yaml
      - name: build & push container image
        uses: docker/build-push-action@v7
        with:
          context: "."
          push: true
          cache-from: type=gha
          cache-to: type=gha,mode=max
          provenance: false          # task 8 folded in
          ...
```

### 2. MySQL client: Oracle's native Debian 13 packages — `Dockerfile`

**Context (why this client exists at all):** commit `7fe0c24` (Nov 2025) replaced Debian's
`default-mysql-client` (MariaDB) with the Oracle tarball because MariaDB's dump tool errors
when dumping MySQL 8 (stored-function metadata moved from `mysql.proc` to the data
dictionary in MySQL 8; MariaDB's mysqldump still queries `mysql.proc`). That incompatibility
is architectural and has NOT been fixed — do not revert to `default-mysql-client`.

**What has changed since Nov 2025:** Oracle now publishes native `1debian13` (trixie) .deb
packages, which didn't exist when the tarball approach was chosen. Verified this session:
all three debs exist on cdn.mysql.com for 8.4.9 (~3 MB total download vs 831 MB today),
`mysql-community-client-core` contains exactly `/usr/bin/mysql` + `/usr/bin/mysqldump`, and
a `dpkg -i` of common+plugins+core on this trixie dev container succeeded with both binaries
running (`Ver 8.4.9 for Linux on x86_64`).

```dockerfile
ARG MYSQL_CLIENT_VERSION=8.4.9
RUN set -eux; \
    cd /tmp; \
    for p in mysql-common mysql-community-client-plugins mysql-community-client-core; do \
        curl -fsSL "https://cdn.mysql.com/Downloads/MySQL-8.4/${p}_${MYSQL_CLIENT_VERSION}-1debian13_amd64.deb" -O; \
    done; \
    apt-get update; \
    apt-get install -y --no-install-recommends /tmp/mysql-*.deb; \
    apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/mysql-*.deb; \
    mysql --version; mysqldump --version
```

Notes:
- Use `apt-get install ./debs` (not bare `dpkg -i`) so any missing shared-lib deps resolve
  from the archive automatically.
- Drop the old `/usr/local/mysql` tree + symlinks AND the separate runtime-libs apt layer
  (`libncurses6 libtinfo6 libzstd1 zlib1g libssl3`) — the debs declare their own deps and
  apt enforces them. Nothing in `app/`, `config/`, or `container/` references the old path —
  `spatie/db-dumper` finds `mysqldump` on `PATH` (grep verified); debs install to `/usr/bin`.
- `mysql-community-client-plugins` is a hard versioned dep of client-core (~14 MB installed,
  LDAP/kerberos/webauthn client auth plugins — unused but obligatory). Total ≈30 MB
  installed vs 1.46 GB today.
- The `-1debian13` suffix is tied to the **base image's** Debian release — when the php base
  moves to Debian 14, bump the suffix (build fails loudly on mismatch URL, so it can't rot
  silently). Consider a second ARG for it.
- Bonus: dpkg registration means security scanners see the package, and the deb's
  `Conflicts: mariadb-*` guards against a MariaDB client ever sneaking back in.
- Fallback if the deb route hits a snag: the `-minimal` tarball variant (80 MB download)
  contains working binaries too — extract only `bin/mysql`/`bin/mysqldump` (~16 MB), both
  also execution-verified on trixie this session.

Residual risk: `--version` proves linkage, not a full dump — see verification plan step 4.

Also update **README.md line 23**: the Oracle-vs-MariaDB rationale sentence stays, but
"installed from the official generic Linux tarball" becomes "installed from Oracle's
official Debian packages". The `MYSQL_CLIENT_VERSION` build-arg sentence stays true as-is.

### 3a. Frontend stage: lockfile-first npm — `Dockerfile`

`npm ci` needs no secret and no app source; only the vite build does. `node_modules` is
dockerignored so the later full COPY can't clobber it.

```dockerfile
FROM docker.io/library/node:26.7-alpine AS frontend
LABEL stage=build
WORKDIR /app
COPY package.json package-lock.json /app/
RUN npm ci --omit dev
COPY ./ /app/
RUN --mount=type=secret,id=sentry_auth_token \
    export SENTRY_AUTH_TOKEN="$(cat /run/secrets/sentry_auth_token 2>/dev/null || true)"; \
    export SENTRY_RELEASE="$(sed -n "s/.*'version' *=> *'\([^']*\)'.*/\1/p" config/app.php)"; \
    npx vite build
```

(The `SENTRY_RELEASE` sed reads `config/app.php`, which lands with the full COPY — order
matters, keep the sed in the build RUN, after the full COPY.)

Do **not** add a `type=cache` mount for `/root/.npm` — same GHA no-op as C2.

### 3b. Main stage: lockfile-first composer — `Dockerfile`

Replace the current `COPY ./` → `COPY --from=frontend` → `composer install` block with:

```dockerfile
WORKDIR /app
# Deps layer: no scripts (artisan isn't copied yet), no autoloader (dumped after app COPY)
COPY composer.json composer.lock /app/
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY --chown=www-data:www-data ./ /app/
COPY --from=frontend --chown=www-data:www-data /app/public/build/ /app/public/build/

# Fires post-autoload-dump → package:discover (writes bootstrap/cache/packages.php as root)
RUN composer dump-autoload --optimize --no-dev
```

Critical details:
- `vendor/` is dockerignored, so the full `COPY ./` cannot overwrite the installed deps.
- `composer dump-autoload` fires the `post-autoload-dump` script by default — that is what
  runs `artisan package:discover`. Do not add `--no-scripts` to this second command.
- The existing `mkdir`/`chown -R www-data` block must stay **after** `dump-autoload`, so the
  root-written `bootstrap/cache/packages.php` gets chowned. (It already sits last; keep it.)
- The `COPY --from=frontend` narrowing to `public/build/` is task 4, folded in here. Safe
  because `laravel-vite-plugin` writes only `public/build/` (manifest included), `public/hot`
  is dev-only, and `.dockerignore` excludes local `public/build` from context so the main
  COPY brings `public/img` etc. but never a stale local build.

### 5. Collapse to a single apt layer — `Dockerfile`

With task 2's deb route, the hand-picked runtime-libs layer disappears entirely; one
`apt-get update` + install remains: `curl xz-utils git unzip vim nano ca-certificates`.
Keep vim/nano/git (vim/nano: Daniel's requirement; git: composer fallback). `xz-utils` is
only needed if the tarball fallback is used — droppable on the deb route.

### 6. `.dockerignore`: add `fonts-repo`

CI checks the private fonts repo out into `fonts-repo/` and copies it to `resources/fonts/`
before the build; without this line the raw checkout also ships in the image at
`/app/fonts-repo`.

### 7. Concurrency — `.github/workflows/ci.yaml` top level

```yaml
concurrency:
  group: ci-${{ github.ref }}
  cancel-in-progress: ${{ github.ref != 'refs/heads/main' }}
```

Main is exempt from cancellation so every main SHA gets its image (the `type=sha` and
`latest` tags depend on those builds completing).

### 8. `provenance: false` — folded into task 1's snippet.

### 9. Delete the `repository: blt950/where2fly` line from the build-container checkout
(cosmetic, per C1). Keep the fonts-repo checkout's `repository:` — that one is a genuinely
different repo and needs it.

### 10. Delete `.github/workflows/linting.yaml` and `.github/workflows/tests.yaml`
(both `on: {}`, superseded by ci.yaml).

### 11. CI smoke test — new step at the end of build-container

Guards the composer split (C2) and future Dockerfile changes. The job is already logged in
to ghcr; pull the just-pushed image and boot the framework (registers all discovered
providers; `about` needs no DB and the entrypoint self-generates an APP_KEY):

```yaml
      - name: Smoke test image
        run: |
          IMAGE_TAG="$(echo "${{ steps.meta.outputs.tags }}" | head -n1)"
          docker run --rm "$IMAGE_TAG" php artisan about
```

If `about` proves flaky in practice, `php artisan --version` is the weaker fallback — but
try `about` first; it's the one that actually exercises provider registration.

## Verification plan (no Docker daemon in the dev container)

1. `docker build .` cannot be run locally here — the CI smoke test (task 11) is the gate.
   Push to a branch first; branch pushes run the full lint → tests → build → smoke chain.
2. Confirm in the build logs that a second push to the same branch shows `CACHED` on the
   apt, mysql, npm-ci and composer-install layers.
3. Check the pushed image size in ghcr (expect roughly 0.8–0.9 GB on disk, ~0.37 GB
   compressed, down from ~2.3 GB / ~1.25 GB).
4. After the first production deploy, verify the next scheduled `backup:run` (01:30, watch
   the Sentry monitor) produced a valid gzipped dump — this is the real test of the trimmed
   `mysqldump`, beyond `--version`.

## Out of scope (decided, don't revisit)

- Parallelizing lint/tests/build — explicitly rejected by Daniel.
- Removing vim/nano/git — rejected (interactive use).
- Switching base image to Ubuntu 26.04 for its native MySQL 8.4 client — separate decision,
  not part of this work; the trim above deletes cleanly if that ever happens.
- `php artisan test --parallel`, stub Vite manifest for tests, reusing test-job assets in
  the image build — all rejected in the original analysis, reasons stand.
- Pinning `composer:latest` → `composer:2` — optional nice-to-have, fine to include if
  touching that line anyway, not required.

Delete this file once the work is merged.
