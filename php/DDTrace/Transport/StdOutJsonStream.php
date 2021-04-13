namespace DDTrace\Transport {
    use DDTrace\Transport;
    use DDTrace\Contracts\Tracer;
    use DDTrace\Sampling\PrioritySampling;
    use DDTrace\Log\LoggingTrait;

    final class StdOutJsonStream implements Transport
    {
        use LoggingTrait;

        private const SPAN_CHUNK_SIZE = 3;

        private $headers = [];
        private $stream;

        public function __construct()
        {
            $this->stream = fopen('php://stdout', 'w');
        }

        public function send(Tracer $tracer)
        {
            $traces = $this->normaliseTraces($tracer);
            $isDebug = false !== (bool) \getenv('LOG_BUNDLE_SERVERLESS_DEBUG');

            if ($isDebug) {
                fwrite($this->stream, '[DEBUG] prioritySampling = ' . $tracer->getPrioritySampling() . PHP_EOL);
                fwrite($this->stream, '[DEBUG] ' . json_encode($traces) . PHP_EOL);
            }

            if (\in_array($tracer->getPrioritySampling(), [PrioritySampling::AUTO_REJECT, PrioritySampling::USER_REJECT])) {
                if ($isDebug) {
                    fwrite($this->stream, '[DEBUG] Dropping trace as requested'. PHP_EOL);
                }

                return;
            }

            foreach ($traces as $trace) {
                foreach (array_chunk($trace, self::SPAN_CHUNK_SIZE) as $spans) {
                    fwrite($this->stream, json_encode(['headers' => $this->headers, 'traces' => [$spans]]) . PHP_EOL);
                }
            }
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
                    $span['trace_id'] = (string) $span['trace_id'];
                    $span['span_id'] = (string) $span['span_id'];
                    $span['parent_id'] = (string) $span['parent_id'];

                    if (!isset($span['meta'])) {
                        $span['meta'] = (object) [];
                    }
                }
            }

            return $traces;
        }
    }
}
