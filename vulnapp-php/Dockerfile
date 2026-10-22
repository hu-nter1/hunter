# Lab app, not production practice: this intentionally keeps uploads/ writable+executable
# and lets the demo SQLite DB live inside the container. See VULNERABILITIES.md.
FROM php:8.2-cli

# pdo_sqlite needs SQLite's dev headers (libsqlite3-dev) to build; the base image doesn't
# ship them. --no-install-recommends + apt cache cleanup keeps the image small.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_sqlite

WORKDIR /app
COPY . /app

# uploads/ must be writable (and reachable) for the unrestricted-upload vuln to work.
RUN mkdir -p uploads && chmod 777 uploads

EXPOSE 8080
# Railway supplies $PORT; fall back to 8080 for a plain `docker run`.
# Keep the PHP server as the container's main process while honoring Railway's PORT.
CMD ["sh", "-c", "exec php -d auto_prepend_file=/app/auth_gate.php -S 0.0.0.0:${PORT:-8080} -t /app"]
