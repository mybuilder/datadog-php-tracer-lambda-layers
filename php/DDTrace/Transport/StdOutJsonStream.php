namespace DDTrace\Transport {
    use DDTrace\Transport;
    use DDTrace\Contracts\Tracer;
    use DDTrace\Sampling\PrioritySampling;
    use DDTrace\Log\LoggingTrait;

    final class StdOutJsonStream implements Transport
    {
        use LoggingTrait;

        private $headers = [];
        private $stream;

        public function __construct()
        {
            $this->stream = fopen('php://stdout', 'w');
        }

        public function send(Tracer $tracer)
        {
            if (\in_array($tracer->getPrioritySampling(), [PrioritySampling::AUTO_REJECT, PrioritySampling::USER_REJECT])) {
                return;
            }

            fwrite($this->stream, '{"headers": ');
            fwrite($this->stream, json_encode($this->headers));
            fwrite($this->stream, ', "traces": ');
            fwrite($this->stream, $this->encodeTraces($tracer));
            fwrite($this->stream, '}');
            fwrite($this->stream, PHP_EOL);
        }

        public function setHeader($key, $value)
        {
            $this->headers[(string) $key] = (string) $value;
        }

        private function encodeTraces(Tracer $tracer)
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

            $json = json_encode($traces);

            if (false === $json) {
                self::logDebug('Failed to json-encode trace: ' . json_last_error_msg());

                return '[[]]';
            }

            return $json;
        }
    }
}
