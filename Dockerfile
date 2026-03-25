FROM alpine:3.19

LABEL maintainer="game-docker"

ARG WITH_GAME=false

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories \
    && apk add --no-cache \
    nginx \
    php82 \
    php82-fpm \
    openrc \
    supervisor \
    unzip

RUN mkdir -p /run/nginx \
    /run/php \
    /var/www/html \
    && chown -R nginx:nginx /var/www/html \
    && chown nginx:nginx /run/php

COPY nginx.conf /etc/nginx/http.d/default.conf
COPY supervisord.conf /etc/supervisord.conf

RUN chmod 644 /etc/nginx/http.d/*.conf

COPY game /var/www/html/game
RUN chown -R nginx:nginx /var/www/html \
    && sed -i 's/^user = .*/user = nginx/' /etc/php82/php-fpm.d/www.conf \
    && sed -i 's/^group = .*/group = nginx/' /etc/php82/php-fpm.d/www.conf \
    && sed -i 's/^listen.owner = .*/listen.owner = nginx/' /etc/php82/php-fpm.d/www.conf \
    && sed -i 's/^listen.group = .*/listen.group = nginx/' /etc/php82/php-fpm.d/www.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
