/**
* This file is taken from an older version of dd trace extension as it's needed to call flush on the tracer.
* See here https://github.com/DataDog/dd-trace-php/blob/0.59.0/src/DDTrace/Bootstrap.php
*/
namespace DDTrace {
    final class Bootstrap
    {
        private static bool $bootstrapped = false;

        /*
        * Idempotent method to bootstrap the datadog tracer once.
        */
        public static function tracerOnce(): void
        {
            if (self::$bootstrapped) {
                return;
            }

            self::$bootstrapped = true;

            \DDTrace\hook_method('DDTrace\\Bootstrap', 'flushTracerShutdown', null, function () {
                $tracer = GlobalTracer::get();
                $scopeManager = $tracer->getScopeManager();
                $scopeManager->close();
                if (!\dd_trace_env_config('DD_TRACE_AUTO_FLUSH_ENABLED')) {
                    $tracer->flush();
                }
            });
            register_shutdown_function(function () {
                /*
                * Register the shutdown handler during shutdown so that it is run after all the other shutdown handlers.
                * Doing this ensures:
                * 1) Calls in shutdown hooks will still be instrumented
                * 2) Fatal errors (or any zend_bailout) during flush will happen after the user's shutdown handlers
                * Note: Other code that implements this same technique will be run _after_ the tracer shutdown.
                */
                register_shutdown_function(function () {
                    // We wrap the call in a closure to prevent OPcache from skipping the call.
                    Bootstrap::flushTracerShutdown();
                });
            });
        }

        public static function flushTracerShutdown(): int
        {
            // Flushing happens in the sandboxed tracing closure after the call.
            // Return a value from runtime to prevent OPcache from skipping the call.
            return mt_rand();
        }
    }
}
