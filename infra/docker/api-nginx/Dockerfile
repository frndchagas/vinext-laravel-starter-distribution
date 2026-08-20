FROM nginx:1.28.3-alpine3.23

COPY infra/docker/api-nginx/nginx.conf /etc/nginx/nginx.conf
COPY --chown=nginx:nginx public /var/www/html/public

USER nginx

EXPOSE 8080

HEALTHCHECK --interval=10s --timeout=5s --retries=6 \
    CMD ["wget", "--quiet", "--spider", "http://127.0.0.1:8080/up"]

ENTRYPOINT []
CMD ["nginx", "-g", "daemon off;"]
