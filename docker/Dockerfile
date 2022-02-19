FROM caddy:2-alpine

ARG WWWGROUP

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
 && apk add --no-cache bash su-exec nss-tools \
 && rm -rf /var/cache/apk/* \
 && addgroup -S $WWWGROUP \
 && adduser -G $WWWGROUP -u 1337 -S sail

COPY start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

ENTRYPOINT ["start-container"]
