FROM sail-8.5/app

USER root

RUN apt-get update \
    && apt-get upgrade -y \
    && rm -rf /var/lib/apt/lists/*

RUN php -r 'if (PHP_VERSION !== "8.5.12") { fwrite(STDERR, "Version inesperada: ".PHP_VERSION."\n"); exit(1); }'

