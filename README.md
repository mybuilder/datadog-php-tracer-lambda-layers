# Datadog php tracer lambda layers

This lambda layer adds the [DD Trace PHP extensions](https://github.com/DataDog/dd-trace-php) so we can add traces in to datadog from our application.

## Usage

This will publish a lambda layer that adds the Datadog PHP tracer extension as a PHP extension.

This allows us to use `DDTrace` functions from inside our application so that we can send traces to datadog.

The way the trace extension wants you to integrate this is by using their datadog agent but this has quite a large
runtime memory footprint so instead we've chosen to just add the PHP extension and manually call `flush` from within our [log bundle](https://github.com/mybuilder/log-bundle/blob/main/src/EventSubscriber/DatadogTraceSubscriber.php#L89)
as well as manipulating the generated code as follows:

In the `Dockerfile` we make a couple of tweaks:

1. Run a cat command to add the contents of `StdOutJsonStream.php` to the generated_tracer_api: `cat /tmp/php/DDTrace/Transport/StdOutJsonStream.php >> /tmp/ddtrace/opt/datadog-php/dd-trace-sources/bridge/_generated_tracer_api.php`
2. Run a sed command to amend the contents of the generated_tracer_api to add in our `StdOutJsonStream` transport: `sed -i 's/self::$instance = new Tracer();/self::$instance = new Tracer(new \\DDTrace\\Transport\\StdOutJsonStream());/g' /tmp/ddtrace/opt/datadog-php/dd-trace-sources/bridge/_generated_tracer_api.php`

This means that the trace will write to `stderr` and can be picked up by datadog.

## Deployment

### Prerequisites

- Download and install [AWS CLI v2](https://docs.aws.amazon.com/cli/latest/userguide/install-cliv2.html).
- Verify that the AWS CLI has been installed by executing `aws --version`, you should see a response similar to `aws-cli/2.0.5 Python/3.7.4 Darwin/18.7.0 botocore/2.0.0dev9`.
- Next we need to locally configure your AWS CLI setup, based on the following:

```bash
$ aws configure sso
SSO start URL: https://console.aws.amazon.com/console/home
SSO Region: eu-west-1
Choose the relevant AWS account/role.
CLI default client Region: eu-west-1
CLI default output format [None]: <leave blank>
```

- With this configured we can verify that it has worked by executing the following:

```bash
$ aws s3 ls
...
2022-09-21 15:12:07 admin
2022-09-20 15:12:07 api
...
```

We are using marketplace prod here, so you will need access to that account in AWS.
The reason for using marketplace is because this layer is used by multiple accounts/apps and marketplace seems like
the most generic account we have at the minute.

### Deploy to prod

The `./build.sh` will create a new zipped version of the layer and then upload it to AWS for us.

```bash
$ ./build.sh
### Building datadog-php-tracer 0.79.0 Lambda layer for PHP 81

[+] Building 16.2s (11/11) FINISHED
 => [internal] load build definition from Dockerfile                                                                 0.0s
 => => transferring dockerfile: 37B                                                                                  0.0s
 => [internal] load .dockerignore                                                                                    0.0s
 => => transferring context: 2B                                                                                      0.0s
 => [internal] load metadata for docker.io/lambci/lambda:provided                                                    1.4s
 => [internal] load metadata for docker.io/bref/build-php-81:latest                                                  1.5s
 => [internal] load build context                                                                                    0.0s
 => => transferring context: 3.06kB                                                                                  0.0s
 => CACHED [stage-1 1/2] FROM docker.io/lambci/lambda:provided@sha256:cb4cf37c22d7ae7017193db7fed18dcb9418ddff3af14  0.0s
 => CACHED [ext 1/3] FROM docker.io/bref/build-php-81@sha256:e17deba2e9c6317b6c2d0557a71dcff459f75d383fb77ba9d6ff2e  0.0s
 => [ext 2/3] COPY php /tmp/php                                                                                      0.0s
 => [ext 3/3] RUN curl -A "Docker" -o /tmp/ddtrace.tar.gz -D - -L -s "https://github.com/DataDog/dd-trace-php/rele  14.3s
 => [stage-1 2/2] COPY --from=ext /opt/ddtrace /opt/ddtrace                                                          0.0s 
 => exporting to image                                                                                               0.0s 
 => => exporting layers                                                                                              0.0s 
 => => writing image sha256:2643ab82c0cbe226ab814ce191deb9202ab2868502fd18ad003456e0c850d97d                         0.0s 
 => => naming to docker.io/library/datadog-php-tracer-0.79.0-php-81                                                  0.0s 

Use 'docker scan' to run Snyk tests against images to find vulnerabilities and learn how to fix them
updating: ddtrace/ (stored 0%)
updating: ddtrace/dd-trace-sources/ (stored 0%)
updating: ddtrace/dd-trace-sources/bridge/ (stored 0%)
updating: ddtrace/dd-trace-sources/bridge/_generated_api.php (deflated 71%)
updating: ddtrace/dd-trace-sources/bridge/autoload.php (deflated 66%)
updating: ddtrace/dd-trace-sources/bridge/_generated_tracer.php (deflated 80%)
updating: ddtrace/dd-trace-sources/bridge/_files_tracer_api.php (deflated 77%)
updating: ddtrace/dd-trace-sources/bridge/dd_register_optional_deps_autoloader.php (deflated 71%)
updating: ddtrace/dd-trace-sources/bridge/dd_init.php (deflated 41%)
updating: ddtrace/dd-trace-sources/bridge/configuration.php (deflated 76%)
updating: ddtrace/dd-trace-sources/bridge/_files_api.php (deflated 72%)
updating: ddtrace/dd-trace-sources/bridge/_files_integrations.php (deflated 85%)
updating: ddtrace/dd-trace-sources/bridge/.gitignore (deflated 51%)
updating: ddtrace/dd-trace-sources/bridge/_generated_integrations.PHP5.php (deflated 85%)
updating: ddtrace/dd-trace-sources/bridge/_generated_integrations.php (deflated 85%)
updating: ddtrace/dd-trace-sources/bridge/_files_integrations.PHP5.php (deflated 84%)
updating: ddtrace/dd-trace-sources/bridge/dd_wrap_autoloader.php (deflated 12%)
updating: ddtrace/dd-trace-sources/bridge/_generated_tracer_api.php (deflated 80%)
updating: ddtrace/dd-trace-sources/bridge/_files_tracer.php (deflated 75%)
updating: ddtrace/dd-trace-sources/src/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/CMakeLists.txt (deflated 57%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/test/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/test/main.cc (deflated 4%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/test/CMakeLists.txt (deflated 43%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/test/client.cc (deflated 77%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/.editorconfig (deflated 58%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/dogstatsd_client/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/dogstatsd_client/client.h (deflated 69%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/README.md (deflated 54%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/client.c (deflated 70%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/.gitignore (stored 0%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/dogstatsd_client.pc.in (deflated 30%)
updating: ddtrace/dd-trace-sources/src/dogstatsd/.clang-format (deflated 9%)
updating: ddtrace/dd-trace-sources/src/Integrations/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Util/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Util/Runtime.php (deflated 64%)
updating: ddtrace/dd-trace-sources/src/Integrations/Util/Versions.php (deflated 65%)
updating: ddtrace/dd-trace-sources/src/Integrations/Util/ObjectKVStore.php (deflated 74%)
updating: ddtrace/dd-trace-sources/src/Integrations/Util/ArrayKVStore.php (deflated 67%)
updating: ddtrace/dd-trace-sources/src/Integrations/Util/Normalizer.php (deflated 69%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/DefaultIntegrationConfiguration.php (deflated 56%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/CodeIgniter/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/CodeIgniter/V2/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/CodeIgniter/V2/CodeIgniterIntegration.php (deflated 77%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/CodeIgniter/V2/CodeIgniterIntegration.PHP5.php (deflated 77%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/WordPress/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/WordPress/WordPressIntegration.php (deflated 60%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/WordPress/V4/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/WordPress/V4/WordPressIntegrationLoader.php (deflated 86%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/ZendFramework/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/ZendFramework/V1/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/ZendFramework/V1/TraceRequest.php (deflated 68%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/ZendFramework/V1/Ddtrace.php (deflated 48%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/ZendFramework/ZendFrameworkIntegration.php (deflated 70%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Web/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Web/WebIntegration.php (deflated 57%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/AbstractIntegrationConfiguration.php (deflated 67%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/CakePHP/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/CakePHP/CakePHPIntegration.PHP5.php (deflated 71%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/CakePHP/CakePHPIntegration.php (deflated 69%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/PHPRedis/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/PHPRedis/PHPRedisIntegration.php (deflated 81%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Yii/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Yii/YiiIntegration.PHP5.php (deflated 72%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Yii/YiiIntegration.php (deflated 73%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Predis/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Predis/PredisIntegration.php (deflated 76%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/IntegrationsLoader.php (deflated 78%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/PDO/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/PDO/PDOIntegration.php (deflated 74%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Nette/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Nette/NetteIntegration.php (deflated 78%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/MongoDB/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/MongoDB/MongoDBIntegration.php (deflated 79%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Eloquent/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Eloquent/EloquentIntegration.php (deflated 78%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Memcached/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Memcached/MemcachedIntegration.php (deflated 79%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Curl/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Curl/CurlIntegration.php (deflated 67%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Lumen/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Lumen/LumenIntegration.php (deflated 72%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Mysqli/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Mysqli/MysqliCommon.php (deflated 67%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Mysqli/MysqliIntegration.php (deflated 81%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Integration.php (deflated 67%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Mongo/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Mongo/MongoIntegration.php (deflated 85%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Symfony/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Symfony/SymfonyIntegration.PHP5.php (deflated 80%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Symfony/SymfonyIntegration.php (deflated 79%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Slim/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Slim/SlimIntegration.php (deflated 73%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Pcntl/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Pcntl/PcntlIntegration.php (deflated 61%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/ElasticSearch/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/ElasticSearch/V1/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/ElasticSearch/V1/ElasticSearchIntegration.php (deflated 78%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/ElasticSearch/V1/ElasticSearchCommon.php (deflated 54%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Laravel/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Laravel/LaravelIntegration.php (deflated 76%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Guzzle/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/Integrations/Integrations/Guzzle/GuzzleIntegration.php (deflated 79%)
updating: ddtrace/dd-trace-sources/src/Integrations/Obfuscation.php (deflated 60%)
updating: ddtrace/dd-trace-sources/src/DDTrace/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/DDTrace/autoload.php (deflated 48%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Tracer.php (deflated 74%)
updating: ddtrace/dd-trace-sources/src/DDTrace/StartSpanOptionsFactory.php (deflated 52%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Time.php (deflated 57%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Transport/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Transport/Http.php (deflated 69%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Transport/Internal.php (deflated 42%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Transport/Noop.php (deflated 40%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Span.php (deflated 77%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Encoder.php (deflated 47%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Processing/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Processing/TraceAnalyticsProcessor.php (deflated 49%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Scope.php (deflated 67%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Sampling/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Sampling/PrioritySampling.php (deflated 57%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Propagator.php (deflated 60%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Propagators/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Propagators/TextMap.php (deflated 73%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Propagators/Noop.php (deflated 52%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Http/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Http/Request.php (deflated 48%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Encoders/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Encoders/SpanEncoder.php (deflated 66%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Encoders/MessagePack.php (deflated 51%)
updating: ddtrace/dd-trace-sources/src/DDTrace/Encoders/Noop.php (deflated 47%)
updating: ddtrace/dd-trace-sources/src/DDTrace/ScopeManager.php (deflated 73%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer1/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer1/Tracer.php (deflated 76%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer1/Span.php (deflated 74%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer1/Scope.php (deflated 65%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer1/ScopeManager.php (deflated 68%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer1/SpanContext.php (deflated 68%)
updating: ddtrace/dd-trace-sources/src/DDTrace/try_catch_finally.php (deflated 44%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer/Tracer.php (deflated 75%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer/Span.php (deflated 75%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer/Scope.php (deflated 66%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer/ScopeManager.php (deflated 68%)
updating: ddtrace/dd-trace-sources/src/DDTrace/OpenTracer/SpanContext.php (deflated 68%)
updating: ddtrace/dd-trace-sources/src/DDTrace/SpanContext.php (deflated 79%)
updating: ddtrace/dd-trace-sources/src/api/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/api/NoopTracer.php (deflated 77%)
updating: ddtrace/dd-trace-sources/src/api/NoopScopeManager.php (deflated 57%)
updating: ddtrace/dd-trace-sources/src/api/Configuration/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/api/Configuration/AbstractConfiguration.php (deflated 74%)
updating: ddtrace/dd-trace-sources/src/api/Configuration/Registry.php (deflated 70%)
updating: ddtrace/dd-trace-sources/src/api/Configuration/EnvVariableRegistry.php (deflated 76%)
updating: ddtrace/dd-trace-sources/src/api/Type.php (deflated 46%)
updating: ddtrace/dd-trace-sources/src/api/NoopSpan.php (deflated 81%)
updating: ddtrace/dd-trace-sources/src/api/Contracts/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/api/Contracts/Tracer.php (deflated 69%)
updating: ddtrace/dd-trace-sources/src/api/Contracts/Span.php (deflated 72%)
updating: ddtrace/dd-trace-sources/src/api/Contracts/Scope.php (deflated 50%)
updating: ddtrace/dd-trace-sources/src/api/Contracts/ScopeManager.php (deflated 55%)
updating: ddtrace/dd-trace-sources/src/api/Contracts/SpanContext.php (deflated 64%)
updating: ddtrace/dd-trace-sources/src/api/Tag.php (deflated 61%)
updating: ddtrace/dd-trace-sources/src/api/Reference.php (deflated 67%)
updating: ddtrace/dd-trace-sources/src/api/Configuration.php (deflated 72%)
updating: ddtrace/dd-trace-sources/src/api/Exceptions/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/api/Exceptions/InvalidReferenceArgument.php (deflated 57%)
updating: ddtrace/dd-trace-sources/src/api/Exceptions/UnsupportedFormat.php (deflated 46%)
updating: ddtrace/dd-trace-sources/src/api/Exceptions/InvalidSpanArgument.php (deflated 51%)
updating: ddtrace/dd-trace-sources/src/api/Exceptions/InvalidReferencesSet.php (deflated 54%)
updating: ddtrace/dd-trace-sources/src/api/Exceptions/InvalidSpanOption.php (deflated 78%)
updating: ddtrace/dd-trace-sources/src/api/NoopScope.php (deflated 52%)
updating: ddtrace/dd-trace-sources/src/api/bootstrap.composer.php (deflated 24%)
updating: ddtrace/dd-trace-sources/src/api/GlobalTracer.php (deflated 58%)
updating: ddtrace/dd-trace-sources/src/api/Transport.php (deflated 51%)
updating: ddtrace/dd-trace-sources/src/api/Http/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/api/Http/Urls.php (deflated 65%)
updating: ddtrace/dd-trace-sources/src/api/Log/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/api/Log/Logger.php (deflated 61%)
updating: ddtrace/dd-trace-sources/src/api/Log/LogLevel.php (deflated 55%)
updating: ddtrace/dd-trace-sources/src/api/Log/ErrorLogLogger.php (deflated 71%)
updating: ddtrace/dd-trace-sources/src/api/Log/InterpolateTrait.php (deflated 51%)
updating: ddtrace/dd-trace-sources/src/api/Log/NullLogger.php (deflated 68%)
updating: ddtrace/dd-trace-sources/src/api/Log/LoggerInterface.php (deflated 70%)
updating: ddtrace/dd-trace-sources/src/api/Log/PsrLogger.php (deflated 69%)
updating: ddtrace/dd-trace-sources/src/api/Log/LoggingTrait.php (deflated 72%)
updating: ddtrace/dd-trace-sources/src/api/Log/AbstractLogger.php (deflated 54%)
updating: ddtrace/dd-trace-sources/src/api/Obfuscation/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/api/Obfuscation/WildcardToRegex.php (deflated 59%)
updating: ddtrace/dd-trace-sources/src/api/NoopSpanContext.php (deflated 73%)
updating: ddtrace/dd-trace-sources/src/api/Data/ (stored 0%)
updating: ddtrace/dd-trace-sources/src/api/Data/Span.php (deflated 71%)
updating: ddtrace/dd-trace-sources/src/api/Data/SpanContext.php (deflated 66%)
updating: ddtrace/dd-trace-sources/src/api/Format.php (deflated 59%)
updating: ddtrace/dd-trace-sources/src/api/StartSpanOptions.php (deflated 78%)
updating: ddtrace/ddtrace.so (deflated 59%)

### Publishing datadog-php-tracer 0.79.0 Lambda layer for PHP 81

{
    "Statement": "{\"Sid\":\"public\",\"Effect\":\"Allow\",\"Principal\":\"*\",\"Action\":\"lambda:GetLayerVersion\",\"Resource\":\"arn:aws:lambda:eu-west-1:123:layer:datadog-php-tracer-81:2\"}",
    "RevisionId": "25e8e930-e1f7-4400-a24d-28ab205b788c"
}
```
