FROM alpine:3.19

LABEL maintainer="game-docker"

ARG WITH_GAME=false

RUN apk add --no-cache \
    nginx \
    php82 \
    php82-fpm \
    openrc

RUN mkdir -p /run/nginx \
    /run/php \
    /var/www/html \
    && chown -R nginx:nginx /var/www/html

COPY nginx.conf /etc/nginx/http.d/default.conf
COPY supervisord.conf /etc/supervisord.conf

RUN chmod 644 /etc/nginx/http.d/*.conf

COPY --chown=nginx:nginx game /var/www/html

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
