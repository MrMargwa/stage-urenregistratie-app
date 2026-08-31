#!/bin/sh
set -e

mkdir -p /var/log/nginx /var/cache/nginx
chmod -R ugo+rw /app/storage

echo "[start] starting php-fpm..."
php-fpm -y /app/php-fpm.conf &
FPM_PID=$!

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

# Inject the correct listen port into the nginx config (nginx reads the file directly,
# so we substitute the __PORT__ placeholder with the port Railway routeert naar).
# Railway's domein 'target port' is 8000. Let een eventueel handmatig gezette PORT-
# variabele NIET de routing-poort overschrijven (die kan afwijken, bv. 8080).
LISTEN_PORT="${RAILWAY_LISTEN_PORT:-8000}"
sed -i "s/__PORT__/${LISTEN_PORT}/g" /app/nginx.conf
echo "[start] nginx will listen on 0.0.0.0:${LISTEN_PORT}"

echo "[start] validating nginx config..."
nginx -t -c /app/nginx.conf 2>&1 || { echo "[start] ERROR: nginx config invalid"; exit 1; }

echo "[start] starting nginx..."
nginx -c /app/nginx.conf &
NGINX_PID=$!

trap "kill $FPM_PID $NGINX_PID 2>/dev/null" INT TERM EXIT

wait
