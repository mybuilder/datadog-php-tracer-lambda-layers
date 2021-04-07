namespace DDTrace\Encoders {
    use DDTrace\Contracts\Tracer;
    use DDTrace\Encoder;
    use DDTrace\Log\LoggingTrait;

    final class ServerlessJson implements Encoder
    {
        use LoggingTrait;

        public function encodeTraces(Tracer $tracer)
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

        public function getContentType()
        {
            return 'application/json';
        }
    }
}
