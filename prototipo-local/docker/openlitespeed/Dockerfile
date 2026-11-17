FROM litespeedtech/openlitespeed:1.8.4-lsphp84

RUN sed -i '/^listener HTTP {$/a\  map Example *' /usr/local/lsws/conf/httpd_config.conf \
    && sed -i '/^vhTemplate docker {$/,/^}/d' /usr/local/lsws/conf/httpd_config.conf \
    && sed -i 's|vhRoot                   Example/|vhRoot                   /var/www/core/|' /usr/local/lsws/conf/httpd_config.conf

RUN apt-get update \
    && apt-get install -y --no-install-recommends lsphp84-sqlite3 \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/www/tenants/loja1/runtime/cache /var/www/tenants/loja1/runtime/sessions \
    /var/www/tenants/loja1/runtime/views /var/www/tenants/loja1/runtime/logs /var/www/tenants/loja1/runtime/tmp \
    /var/www/tenants/loja1/data \
    /var/www/tenants/loja2/runtime/cache /var/www/tenants/loja2/runtime/sessions \
    /var/www/tenants/loja2/runtime/views /var/www/tenants/loja2/runtime/logs /var/www/tenants/loja2/runtime/tmp \
    /var/www/tenants/loja2/data /var/www/tenant-config/loja1 /var/www/tenant-config/loja2 \
    && chown -R nobody:nogroup /var/www/tenants /var/www/tenant-config
