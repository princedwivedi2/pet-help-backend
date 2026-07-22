# CI/CD setup — Deploy backend to EC2

The workflow `.github/workflows/deploy.yml` deploys automatically when `main` is updated (you work on `prince_dev`, so merging `prince_dev` → `main` triggers a deploy). You can also run it manually from **Actions → Deploy backend to EC2 → Run workflow**.

## 1. Add GitHub secrets

Repo → **Settings → Secrets and variables → Actions → New repository secret**:

| Secret     | Value                                                      |
|------------|------------------------------------------------------------|
| `SSH_HOST` | Server IP, e.g. `23.20.64.92`                              |
| `SSH_USER` | SSH user, e.g. `ubuntu`                                    |
| `SSH_KEY`  | Full contents of the **private** deploy key (BEGIN/END lines included) |
| `SSH_PORT` | Usually `22`                                               |
| `APP_PATH` | App path on the server, e.g. `/var/www/laravel-app`        |
| `APP_URL`  | Public URL for the health check, e.g. `https://yourdomain.com` |

## 2. One-time server prep (already-hosted server)

```bash
# Generate a dedicated deploy key on your machine (NOT on the server):
ssh-keygen -t ed25519 -f deploy_key -C "github-actions-deploy" -N ""

# Put the PUBLIC key on the server:
ssh ubuntu@SERVER "echo '<contents of deploy_key.pub>' >> ~/.ssh/authorized_keys"

# Paste the PRIVATE key (deploy_key) into the SSH_KEY secret, then delete both local files.
```

Make sure on the server:

```bash
cd /var/www/laravel-app          # your APP_PATH
git remote -v                     # must point to github.com/princedwivedi2/pet-help-backend
git status                        # must be clean and on main (no local edits)
```

Allow passwordless sudo for the two commands the deploy uses (run `sudo visudo`):

```
ubuntu ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm, /bin/chown, /bin/chmod
```

## 3. What each deploy does

1. `php artisan down` — maintenance mode (visitors see a retry page, no broken states)
2. `git pull --ff-only origin main` — fetch latest code; fails loudly if the server has stray local commits
3. `composer install --no-dev --optimize-autoloader`
4. `php artisan migrate --force`
5. Rebuild config/route caches, clear compiled views
6. `php artisan queue:restart` — workers pick up the new code
7. Fix `storage/` and `bootstrap/cache` permissions
8. Reload PHP-FPM, `php artisan up`
9. Health check — curls `APP_URL` up to 5 times; the run fails (and emails you) if the site doesn't respond

## 4. Deploy flow day-to-day

```bash
git checkout main
git merge prince_dev
git push origin main      # <- this triggers the deploy
```

## Troubleshooting

- **"ff-only" pull fails** → someone edited files directly on the server. SSH in, `git stash` or `git reset --hard origin/main`, redeploy.
- **Permission errors** → check the sudoers entry above and that the SSH user can write to `APP_PATH`.
- **Health check fails but site looks fine** → confirm `APP_URL` returns HTTP 2xx/3xx (not 401/500) from outside the server.
