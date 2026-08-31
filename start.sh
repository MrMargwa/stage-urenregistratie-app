#!/bin/sh
set -e

mkdir -p /var/log/nginx /var/cache/nginx
chmod -R ugo+rw /app/storage

echo "[start] generating nginx config..."
node /assets/scripts/prestart.mjs /assets/nginx.template.conf /nginx.conf

echo "[start] starting php-fpm..."
php-fpm -y /app/php-fpm.conf &

# Wait until FPM listens on 127.0.0.1:9000 (max 15s)
echo "[start] waiting for php-fpm to come up..."
n=0
until php -r '$s=@fsockopen("127.0.0.1",9000,$e,$m,0.5); if($s){fclose($s); exit(0);} exit(1);' 2>/dev/null; do
  n=$((n+1))
  if [ "$n" -ge 15 ]; then
    echo "[start] ERROR: php-fpm did not listen on 127.0.0.1:9000 within 15s."
    echo "[start] ---- diagnostic (ps) ----"
    ps aux 2>/dev/null | grep -i php || true
    echo "[start] ---- end diagnostic ----"
    exit 1
  fi
  sleep 1
done
echo "[start] php-fpm is up on 127.0.0.1:9000"

echo "[start] starting nginx..."
nginx -c /nginx.conf &
NGINX_PID=$!

wait
