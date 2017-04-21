<?php

namespace Chadicus\Psr\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SobanVuex\NewRelic\Agent;

/**
 * PSR-3 Implementation using NewRelic.
 */
final class NewRelicLogger extends AbstractLogger implements LoggerInterface
{
    /**
     * NewRelic Agent implementation.
     *
     * @var Agent
     */
    private $newRelicAgent;

    /**
     * Array of log levels which should be reported to new relic.
     *
     * @var array
     */
    private $observedLevels = [];

    /**
     * Default log levels to report.
     *
     * @var array
     */
    const DEFAULT_OBSERVED_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
    ];

    /**
     * Construct a new instance of the logger.
     *
     * @param Agent $newRelicAgent  NewRelic Agent implementation.
     * @param array $observedLevels Array of log levels which should be reported to new relic.
     */
    public function __construct(Agent $newRelicAgent, array $observedLevels = self::DEFAULT_OBSERVED_LEVELS)
    {
        $this->newRelicAgent = $newRelicAgent;
        $this->observedLevels = $observedLevels;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level   A valid RFC 5424 log level.
     * @param string $message The base log message.
     * @param array  $context Any extraneous information that does not fit well in a string.
     *
     * @return void
     */
    public function log($level, $message, array $context = [])//@codingStandardsIgnoreLine Interface does not define type-hints or return
    {
        if (!in_array($level, $this->observedLevels)) {
            return;
        }

        $this->newRelicAgent->addCustomParameter('level', $level);

        $exception = null;
        if (array_key_exists('exception', $context)) {
            $exception = $context['exception'];
            unset($context['exception']);
        }

        foreach ($context as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $this->newRelicAgent->addCustomParameter($key, $value);
        }

        $this->newRelicAgent->noticeError($message, $exception);
    }
}