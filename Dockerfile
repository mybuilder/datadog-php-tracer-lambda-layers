ARG PHP_VERSION

FROM bref/build-php-$PHP_VERSION AS ext

ARG PHP_VERSION_DATE
ARG DD_TRACE_VERSION

COPY php /tmp/php

RUN curl -A "Docker" -o /tmp/ddtrace.tar.gz -D - -L -s "https://github.com/DataDog/dd-trace-php/releases/download/${DD_TRACE_VERSION}/datadog-php-tracer-${DD_TRACE_VERSION}.x86_64.tar.gz" \
 && mkdir -p /tmp/ddtrace /opt/ddtrace \
 && tar zxpf /tmp/ddtrace.tar.gz -C /tmp/ddtrace \
 && cat /tmp/php/DDTrace/Transport/StdOutJsonStream.php >> /tmp/ddtrace/opt/datadog-php/dd-trace-sources/bridge/_generated.php \
 && sed -i 's/$tracer = new Tracer();/$tracer = new Tracer(new \\DDTrace\\Transport\\StdOutJsonStream());/g' /tmp/ddtrace/opt/datadog-php/dd-trace-sources/bridge/_generated.php \
 && cp "/tmp/ddtrace/opt/datadog-php/extensions/ddtrace-${PHP_VERSION_DATE}.so" /opt/ddtrace/ddtrace.so \
 && cp -R /tmp/ddtrace/opt/datadog-php/dd-trace-sources /opt/ddtrace

FROM lambci/lambda:provided

COPY --from=ext /opt/ddtrace /opt/ddtrace
