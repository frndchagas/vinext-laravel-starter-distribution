FROM caddy:2.11.4-alpine

COPY infra/docker/proxy/Caddyfile /etc/caddy/Caddyfile

RUN addgroup --system --gid 1000 caddy \
    && adduser --system --disabled-password --uid 1000 --ingroup caddy caddy

ENV XDG_CONFIG_HOME=/tmp/config \
    XDG_DATA_HOME=/tmp/data

USER caddy

EXPOSE 8080

HEALTHCHECK --interval=10s --timeout=5s --retries=6 \
    CMD ["wget", "--quiet", "--spider", "http://127.0.0.1:8080/up"]
