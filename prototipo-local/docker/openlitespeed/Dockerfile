FROM litespeedtech/openlitespeed:1.8.4-lsphp84

RUN apt-get update \
    && apt-get install -y --no-install-recommends lsphp84-sqlite3 \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/www/tenants/loja1/runtime/{cache,sessions,views,logs,tmp} /var/www/tenants/loja1/data \
    /var/www/tenants/loja2/runtime/{cache,sessions,views,logs,tmp} /var/www/tenants/loja2/data \
    && chown -R nobody:nogroup /var/www/tenants
