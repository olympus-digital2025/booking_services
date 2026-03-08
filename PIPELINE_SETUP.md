# GitHub Pipeline Setup Guide

## Overview

This pipeline implements the Code Development Standardization Plan for the
`MBNDEV/custom-plugin` repository. It covers:

- Husky pre-commit hooks (lint before push)
- AI PR review via GitHub Copilot Business (@copilot, @claude, @codex)
- GitHub Actions: lint → unit tests → SonarQube → deploy staging → deploy production
- Hotfix fast lane
- PR template with OWASP checklist

---

## Files Included

```
.github/
  workflows/
    ci.yml                        — Main pipeline (lint, test, sonarqube, deploy)
    ai-review.yml                 — AI PR review trigger + secret scan
    hotfix.yml                    — Hotfix fast-lane pipeline
  PULL_REQUEST_TEMPLATE/
    pull_request_template.md      — PR template with OWASP checklist
.husky/
  pre-commit                      — Blocks commits that fail PHPCS
composer.json                     — Updated with phpunit + husky prepare script
sonar-project.properties          — SonarQube project config matched to phpcs.xml
```

---

## Step 1 — GitHub Repository Secrets

Go to: **GitHub repo → Settings → Secrets and variables → Actions → New repository secret**

Add every secret below. Without these, the pipeline will fail.

### SonarQube Secrets

| Secret Name       | Value                                      | Where to get it                                  |
|-------------------|--------------------------------------------|--------------------------------------------------|
| `SONAR_TOKEN`     | SonarQube user token                       | SonarQube → My Account → Security → Generate     |
| `SONAR_HOST_URL`  | Your SonarQube server URL                  | e.g. `https://sonarqube.mybizniche.com` or SonarCloud URL |

### Staging Secrets

| Secret Name          | Value                             |
|----------------------|-----------------------------------|
| `STAGING_SSH_HOST`   | IP or hostname of staging server  |
| `STAGING_SSH_USER`   | SSH username (e.g. `deployer`)    |
| `STAGING_SSH_KEY`    | Private SSH key (entire key file) |
| `STAGING_PATH`       | Absolute path e.g. `/home/user/public_html/staging/wp-content/plugins/custom-plugin` |
| `STAGING_URL`        | Full staging URL e.g. `https://staging.mybizniche.com` |

### Production Secrets

| Secret Name       | Value                              |
|-------------------|------------------------------------|
| `PROD_SSH_HOST`   | IP or hostname of production server (SiteGround or GoDaddy) |
| `PROD_SSH_USER`   | SSH username                       |
| `PROD_SSH_KEY`    | Private SSH key                    |
| `PROD_PATH`       | Absolute path to plugin directory  |

---

## Step 2 — SonarQube Setup

### Option A: SonarCloud (easiest, free for public repos)

1. Go to https://sonarcloud.io and sign in with GitHub
2. Click **Analyze new project** → select `MBNDEV/custom-plugin`
3. Choose **GitHub Actions** as your CI
4. Copy the `SONAR_TOKEN` shown and add it to GitHub secrets
5. Set `SONAR_HOST_URL` to `https://sonarcloud.io`
6. Update `sonar-project.properties`:
   ```
   sonar.organization=mbndev
   sonar.projectKey=mbndev_custom-plugin
   ```

### Option B: Self-hosted SonarQube

1. Deploy SonarQube (Docker recommended):
   ```bash
   docker run -d --name sonarqube \
     -p 9000:9000 \
     sonarqube:community
   ```
2. Open `http://your-server:9000`
3. Default login: `admin` / `admin` — change immediately
4. Create project → set key to `mbndev_custom-plugin`
5. Generate token → add as `SONAR_TOKEN` in GitHub secrets
6. Set `SONAR_HOST_URL` to `http://your-server:9000`

### Quality Gate Configuration (SonarQube Server)

In SonarQube, go to **Quality Gates → Create** and set these conditions
to match the standardization plan:

| Metric                    | Condition | Value |
|---------------------------|-----------|-------|
| Bugs (new code)           | is worse than | 0 |
| Vulnerabilities (new code)| is worse than | 0 |
| Security Hotspots Reviewed| is less than  | 100% |
| Coverage (new code)       | is less than  | 80%  |
| Cyclomatic Complexity     | is worse than | 10   |
| Duplicated Lines (new)    | is worse than | 3%   |

Set this Quality Gate as **default** and assign it to your project.

---

## Step 3 — Husky Setup (Local)

Each developer runs this once after cloning:

```bash
composer install
npx husky install
chmod +x .husky/pre-commit
```

This installs the pre-commit hook. Any `git commit` that fails
`composer run lint:run` will be blocked before it reaches GitHub.

---

## Step 4 — Enable AI Agents in Copilot Business

1. Go to: **GitHub repo → Settings → Copilot → Coding agent**
2. Under **Partner Agents**, enable:
   - Claude by Anthropic
   - OpenAI Codex
3. Enterprise admins: enable at **Enterprise AI Controls → Agents** first

Once enabled, every PR will receive inline review comments from
`@copilot`, `@claude`, and `@codex` automatically via `ai-review.yml`.

You can also manually trigger a review by commenting on any PR:
```
@copilot please review this PR for security issues
@claude check the ACF field mapping in this PR
@codex scan for SQL injection vulnerabilities
```

---

## Step 5 — Branch Protection Rules

Go to: **GitHub repo → Settings → Branches → Add rule**

### For `main` branch:
- [x] Require a pull request before merging
- [x] Require approvals: **2** (peer + lead)
- [x] Dismiss stale pull request approvals when new commits are pushed
- [x] Require status checks to pass before merging
  - Required checks: `lint`, `test`, `sonarqube`
- [x] Require branches to be up to date before merging
- [x] Do not allow bypassing the above settings
- [x] Restrict who can push to matching branches → leads only

### For `develop` branch:
- [x] Require a pull request before merging
- [x] Require approvals: **1** (peer review)
- [x] Require status checks to pass: `lint`, `test`, `sonarqube`
- [x] Require branches to be up to date

---

## Step 6 — SSH Key Setup for Deploy

Generate a deploy key pair (do this once per server):

```bash
ssh-keygen -t ed25519 -C "github-deploy@mybizniche.com" -f ~/.ssh/deploy_key
```

1. Add the **public key** (`deploy_key.pub`) to the server's `~/.ssh/authorized_keys`
2. Add the **private key** contents as `STAGING_SSH_KEY` or `PROD_SSH_KEY` in GitHub secrets
3. Test the connection:
   ```bash
   ssh -i ~/.ssh/deploy_key deployer@your-server "echo connected"
   ```

---

## Step 7 — PHPUnit Bootstrap (if not already set up)

Create `phpunit.xml` in the plugin root:

```xml
<?xml version="1.0"?>
<phpunit
  bootstrap="tests/bootstrap.php"
  colors="true"
  convertErrorsToExceptions="true"
  convertNoticesToExceptions="true"
  convertWarningsToExceptions="true"
>
  <testsuites>
    <testsuite name="Custom Plugin Tests">
      <directory>tests/</directory>
    </testsuite>
  </testsuites>
  <coverage>
    <include>
      <directory suffix=".php">.</directory>
    </include>
    <exclude>
      <directory>vendor</directory>
      <directory>tests</directory>
      <directory>build</directory>
      <directory>scripts</directory>
    </exclude>
  </coverage>
</phpunit>
```

---

## Pipeline Flow Summary

```
Dev local → Husky pre-commit (PHPCS) → git push → PR opened
  → AI review auto-triggered (@copilot @claude @codex)
  → Secret scan (Gitleaks)
  → Peer review + OWASP checklist
  → Lead final approval
  → GitHub Actions: lint → PHPUnit → SonarQube gate → Liquibase
  → develop: deploy to staging → Playwright E2E
  → main: deploy to production → git tag → GitHub release
```

---

## Troubleshooting

**SonarQube gate times out**
Increase `timeout-minutes` in `ci.yml` sonarqube job.
Check SonarQube server is reachable from GitHub Actions runner.

**Coverage below 80%**
Write more PHPUnit tests. Run `composer run test` locally to check coverage.
Coverage report path in `sonar-project.properties` must match `phpunit.xml` output.

**rsync deploy fails**
Verify SSH key is added to server authorized_keys.
Check `STAGING_PATH` / `PROD_PATH` secrets are absolute paths.
Ensure the deploy user has write access to the target directory.

**Husky not running**
Run `npx husky install` then `chmod +x .husky/pre-commit`.
Confirm `"prepare": "husky install"` is in `composer.json` scripts.
