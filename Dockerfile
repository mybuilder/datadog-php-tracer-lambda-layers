ARG PHP_VERSION

FROM bref/build-php-$PHP_VERSION AS ext

ARG PHP_VERSION_DATE
ARG DD_TRACE_VERSION

COPY php /tmp/php

RUN curl -A "Docker" -o /tmp/ddtrace.tar.gz -D - -L -s "https://github.com/DataDog/dd-trace-php/releases/download/${DD_TRACE_VERSION}/datadog-php-tracer-${DD_TRACE_VERSION}.x86_64.tar.gz" \
 && mkdir -p /tmp/ddtrace /opt/ddtrace \
 && tar zxpf /tmp/ddtrace.tar.gz -C /tmp/ddtrace \
 && mv "/tmp/ddtrace/opt/datadog-php/extensions/ddtrace-${PHP_VERSION_DATE}.so" /tmp/ddtrace/ddtrace.so \
 && mv "/tmp/ddtrace/opt/datadog-php/extensions/ddtrace-${PHP_VERSION_DATE}-zts.so" /tmp/ddtrace/ddtrace-zts.so \
 && cat /tmp/php/DDTrace/Encoders/ServerlessJson.php >> /tmp/ddtrace/opt/datadog-php/dd-trace-sources/bridge/_generated.php \
 && sed -i 's/$tracer = new Tracer();/$tracer = new Tracer(new \\DDTrace\\Transport\\Stream(new \\DDTrace\\Encoders\\ServerlessJson(), fopen("php:\/\/stdout", "w")));/g' /tmp/ddtrace/opt/datadog-php/dd-trace-sources/bridge/_generated.php \
 && cp -R /tmp/ddtrace/ddtrace.so /tmp/ddtrace/ddtrace-zts.so /tmp/ddtrace/opt/datadog-php/dd-trace-sources /opt/ddtrace

FROM lambci/lambda:provided

COPY --from=ext /opt/ddtrace /opt/ddtrace
