namespace DDTrace\Transport {
    use DDTrace\Transport;
    use DDTrace\Contracts\Tracer;
    use DDTrace\Sampling\PrioritySampling;
    use DDTrace\Log\LoggingTrait;

    final class StdOutJsonStream implements Transport
    {
        use LoggingTrait;

        private const MAX_OUTPUT_LENGTH = 50_000;

        private array $headers = [];

        public function send(Tracer $tracer)
        {
            $traces = $this->normaliseTraces($tracer);
            $isDebug = false !== (bool) \getenv('LOG_BUNDLE_SERVERLESS_DEBUG');

            if (\function_exists('dd_trace_stdout_json_stream_send')) {
                dd_trace_stdout_json_stream_send($traces, $isDebug);

                return;
            }

            $stream = \fopen('php://stderr', 'w');

            if ($isDebug) {
                \fwrite($stream, '[DEBUG] prioritySampling = ' . $tracer->getPrioritySampling() . \PHP_EOL);
            }

            if (\in_array($tracer->getPrioritySampling(), [PrioritySampling::AUTO_REJECT, PrioritySampling::USER_REJECT])) {
                if ($isDebug) {
                    \fwrite($stream, '[DEBUG] Dropping trace as requested' . \PHP_EOL);
                }

                return;
            }

            $outputLength = 0;

            foreach ($traces as $trace) {
                foreach ($trace as $span) {
                    $encodedSpan = \json_encode(['traces' => [[$span]]]) . \PHP_EOL;
                    $outputLength += \strlen($encodedSpan);

                    if ($outputLength > self::getMaxOutputLength()) {
                        if ($isDebug) {
                            \fwrite($stream, \sprintf('[DEBUG] Reached max output length of %d', self::getMaxOutputLength()) . \PHP_EOL);
                        }

                        break 2;
                    }

                    \fwrite($stream, $encodedSpan);
                }
            }

            \fclose($stream);
        }

        private static function getMaxOutputLength(): int
        {
            return (int) ($_ENV['LOG_BUNDLE_SERVERLESS_MAX_OUTPUT_LENGTH'] ?? self::MAX_OUTPUT_LENGTH);
        }

        public function setHeader($key, $value)
        {
            $this->headers[(string) $key] = (string) $value;
        }

        private function normaliseTraces(Tracer $tracer)
        {
            $traces = $tracer->getTracesAsArray();

            foreach ($traces as &$trace) {
                foreach ($trace as &$span) {
                    $span['trace_id'] = dechex($span['trace_id']);
                    $span['span_id'] = dechex($span['span_id']);
                    $span['parent_id'] = dechex($span['parent_id']);

                    if (!isset($span['meta'])) {
                        $span['meta'] = (object) [];
                    }
                }
            }

            return $traces;
        }
    }
}
