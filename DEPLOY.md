# Deploying StockWatch on an Oracle Cloud Always Free VM

Single-user deployment, free forever, reachable 24/7 over HTTPS.
Everything below is done once; afterwards updating is `git pull` + one `docker compose` command.

---

## 1. Create the Oracle Cloud account

1. Sign up at <https://signup.cloud.oracle.com>.
2. A credit/debit card is required **for identity verification only** — Always Free
   resources are never charged. Pick your home region carefully: **it cannot be changed
   later**, so choose one close to you (e.g. Singapore, Tokyo).
3. After signup, make sure the account shows "Always Free eligible" resources.

## 2. Create the VM

Compute → Instances → **Create instance**

| Setting | Value |
|---|---|
| Image | Canonical **Ubuntu 24.04** |
| Shape | **VM.Standard.A1.Flex** (Ampere ARM) — Always Free covers 4 OCPU / 24 GB total |
| OCPU / Memory | 1 OCPU / 6 GB is plenty for this app |
| SSH keys | Generate a key pair and **download the private key** |

> If the ARM shape reports "out of capacity", either retry later (it frees up regularly)
> or use **VM.Standard.E2.1.Micro** (AMD), which is also Always Free but smaller.

## 3. Open ports 80 and 443

Two separate firewalls have to allow traffic — missing the second one is the usual reason
a fresh Oracle VM appears unreachable.

**a) Oracle's virtual firewall**
Networking → Virtual Cloud Networks → your VCN → Security Lists → default →
**Add Ingress Rules**:

| Source CIDR | Protocol | Destination port |
|---|---|---|
| 0.0.0.0/0 | TCP | 80 |
| 0.0.0.0/0 | TCP | 443 |

**b) The firewall inside Ubuntu** (run after connecting in step 4):

```bash
sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 80 -j ACCEPT
sudo iptables -I INPUT 6 -m state --state NEW -p tcp --dport 443 -j ACCEPT
sudo netfilter-persistent save
```

## 4. Connect and install Docker

```bash
ssh -i /path/to/private-key ubuntu@<VM-PUBLIC-IP>

sudo apt update && sudo apt upgrade -y
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
exit          # log out and back in so the group membership applies
```

## 5. Get a free domain name

HTTPS certificates are issued for domain names, not bare IP addresses.
Register a free subdomain at <https://www.duckdns.org> (sign in with Google/GitHub),
create e.g. `stockwatch.duckdns.org`, and point it at the VM's public IP.

Check that it resolves before continuing:

```bash
ping -c1 stockwatch.duckdns.org
```

## 6. Upload the project

```bash
git clone <your-repo-url> ~/stockwatch     # or: scp -r the folder from your PC
cd ~/stockwatch
```

## 7. Configure secrets

```bash
cp .env.example .env
openssl rand -base64 24    # run twice, use the output for the two DB passwords
nano .env
```

Fill in `APP_DOMAIN` (the DuckDNS name), `ACME_EMAIL`, and both passwords.
`.env` is gitignored — it must never be committed. Put the real values in `.env`, not in
`.env.example`, which is tracked by git.

Two settings that will silently break the stack:

- **`DB_USER` must not be `root`.** MySQL's entrypoint refuses it (`MYSQL_USER and
  MYSQL_PASSWORD are for configuring a regular user and cannot be used for the root user`),
  the container then crash-loops, and the app shows
  `getaddrinfo for db failed: Temporary failure in name resolution` — because the `db`
  container isn't running to be resolved, which reads like a network problem but isn't.
- **Give `DB_PASS` and `DB_ROOT_PASSWORD` different values.** The app user only needs its
  own database; root can drop everything.

Also `chmod 600 .env` so it isn't world-readable on the server.

## 8. Start it

```bash
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml ps      # all services should be "running"
docker compose -f docker-compose.prod.yml logs caddy   # watch the certificate get issued
```

Open `https://<your-domain>` — the first visit lands on `setup.php` to create the admin
account, because the database starts empty.

**Use a long, unique password here.** The app is now reachable by anyone on the internet,
and its only protection is this password plus the login rate limiter (8 failed attempts
locks that IP out for 15 minutes).

---

## Day-to-day

**Deploy a change**

```bash
cd ~/stockwatch && git pull
docker compose -f docker-compose.prod.yml up -d --build
```

**Back up the database** (do this before any schema change)

```bash
docker exec stockwatch_db mysqldump -uroot -p"$DB_ROOT_PASSWORD" stock_tracker > backup-$(date +%F).sql
```

**Restore**

```bash
docker exec -i stockwatch_db mysql -uroot -p"$DB_ROOT_PASSWORD" stock_tracker < backup-2026-09-01.sql
```

**Reach phpMyAdmin** — it is deliberately bound to loopback, so it is not on the internet.
Tunnel to it over SSH, then open <http://localhost:8081>:

```bash
ssh -i /path/to/private-key -L 8081:localhost:8081 ubuntu@<VM-PUBLIC-IP>
```

**Apply a migration** (files in `db/`; `init.sql` only runs on a brand-new volume)

```bash
docker exec -i stockwatch_db mysql -uroot -p"$DB_ROOT_PASSWORD" stock_tracker < db/migration_00X_name.sql
```

---

## What differs from local development

| | local (`docker-compose.yml`) | server (`docker-compose.prod.yml`) |
|---|---|---|
| Credentials | written in the compose file | from `.env`, never committed |
| MySQL port | published on 3307 | not published at all |
| App port | 8080, plain HTTP | via Caddy on 443, HTTPS only |
| phpMyAdmin | open on 8081 | loopback only, needs an SSH tunnel |
| Certificate | none | Let's Encrypt, auto-renewed by Caddy |

## Security notes

- Session cookies are `HttpOnly` + `SameSite=Lax`, and become `Secure` automatically once
  the request arrives over HTTPS (`src/session.php`).
- The session id is regenerated on login to prevent session fixation.
- Failed logins are recorded per IP in `login_attempts`; 8 failures locks that IP for 15
  minutes, correct password included (`public/login.php`).
- The real client IP comes from Caddy's `X-Real-IP` header. This is only trustworthy
  because the PHP container publishes no ports — nothing but Caddy can reach it.
- `setup.php` only works while the `users` table is empty, so it cannot be used to add a
  second admin later.
- There is no automated off-server backup. The `mysqldump` above is manual — copy the dump
  somewhere else if the data starts to matter.

---

## Variant: shared VPS without its own domain (the shicenter.net server)

Since 11 Nov 2026 StockWatch also runs on the VPS that hosts shicenter.net
(`root@162.141.142.135`, key `~/.ssh/shicenter_deploy`, aaPanel + Docker). That machine's
own nginx already owns ports 80/443, so the Caddy stack above is not used there. Instead
`docker-compose.server.yml` runs just MySQL + PHP + phpMyAdmin with its own port block,
kept apart from the other projects on the box (shicenter uses 9810-9813, novicecraft 8091+).

| What | Where |
|---|---|
| Code | `/opt/stockwatch` (plain copy of the working tree, no git checkout) |
| Compose project | `stockwatch` (`docker compose -f docker-compose.server.yml ...`) |
| App | <https://stockkate.duckdns.org> (DuckDNS name → aaPanel nginx → `127.0.0.1:9820`; HTTP redirects to HTTPS) |
| nginx vhost | `/www/server/panel/vhost/nginx/stockkate.duckdns.org.conf` (copy kept in `deploy/nginx-stockkate.duckdns.org.conf`); reload with `/www/server/nginx/sbin/nginx -t && /www/server/nginx/sbin/nginx -s reload` |
| Certificate | certbot webroot (`/var/www/stockkate.duckdns.org`), auto-renews, issued 11 Nov 2026 |
| phpMyAdmin | `127.0.0.1:9821` on the server — `ssh -L 9821:127.0.0.1:9821 root@162.141.142.135` then `http://localhost:9821` |
| Secrets | `/opt/stockwatch/.env` (`APP_PORT`, `PMA_PORT`, `DB_*`), chmod 600, not in git |

Update the code (from this folder on your PC):

```bash
tar --exclude=./.git --exclude=./backup --exclude=./.env -cf - . \
  | ssh -i ~/.ssh/shicenter_deploy root@162.141.142.135 "tar -x -C /opt/stockwatch"
# only if php/Dockerfile changed:
ssh -i ~/.ssh/shicenter_deploy root@162.141.142.135 \
  "cd /opt/stockwatch && docker compose -f docker-compose.server.yml up -d --build"
```

The web container is bound to `127.0.0.1:9820`, so the app is only reachable through nginx
(Docker-published ports bypass ufw on this box, so the binding, not a firewall rule, is what
closes it). The vhost sends `X-Forwarded-Proto` and `X-Real-IP`, which the Secure cookie flag
and the login rate limiter rely on. If the DuckDNS name ever changes: update the IP at
<https://www.duckdns.org/domains>, run
`certbot certonly --webroot -w /var/www/<name> -d <name>`, and rename the vhost accordingly.

Do not touch `/opt/shicenter.net`, `/opt/novicecraft` or the other `/opt/*` folders — they
belong to other projects sharing the machine.
