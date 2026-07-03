<?php

namespace NightshiftFoundry\AlertStream\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use NightshiftFoundry\AlertStream\AlertChannels\Contracts\AlertChannel;
use NightshiftFoundry\AlertStream\Services\AlertStreamService;
use RuntimeException;

class TestAlertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alertstream:test
                            {channel? : Restrict the test to one channel name (e.g. slack, teams, discord, mail)}
                            {--type=alert : The message type — "alert" tests the report() alert pipeline; any other value (debug, info, warning, error, critical, etc.) tests the log() pipeline at that level}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AlertStream by sending a test alert or log message, to all or one channel';

    /**
     * Execute the console command.
     *
     * @param AlertStreamService $alertStream
     *
     * @return int
     */
    public function handle(AlertStreamService $alertStream): int
    {
        try {
            $type = $this->option('type');
            $targetChannel = $this->argument('channel');
            $isAlert = $type === 'alert';
            $message = $isAlert ? 'AlertStream Test Alert' : 'AlertStream Test Log';

            $testData = [
                'environment' => app()->environment(),
                'php_version' => PHP_VERSION,
                'laravel_version' => Application::VERSION,
                'test_timestamp' => now(),
                'severity' => 'warning',
            ];

            if ($targetChannel && $isAlert) {
                $this->testSpecificAlertChannel($targetChannel, $message, $testData);
            } elseif ($targetChannel) {
                $this->testSpecificLogChannel($targetChannel, $type, $message, $testData);
            } elseif ($isAlert) {
                $alertStream->report($message, new RuntimeException('Test exception from alertstream:test'), $testData);
            } else {
                $alertStream->log($type, $message, $testData);
            }

            $this->info('✓ AlertStream test ' . ($isAlert ? 'alert' : 'log') . ' sent successfully!');
            $this->line('Pipeline: ' . ($isAlert ? 'report() — alert channels' : 'log() — log channels'));
            $this->line('Message: ' . $message);
            $this->line('Type: ' . $type);
            if ($targetChannel) {
                $this->line('Channel: ' . $targetChannel);
            }

            $this->printChannelStatus($isAlert, $targetChannel);

            return 0;
        } catch (Exception $e) {
            $this->error('✗ Failed to send test ' . (($this->option('type') === 'alert') ? 'alert' : 'log') . ': ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * Print a diagnostic summary of the ONE pipeline this run actually
     * exercised — a single command run only ever hits report() or log(),
     * never both, so only that pipeline's status is relevant here. Pass
     * $onlyChannel to further narrow the summary to the channel under test.
     */
    protected function printChannelStatus(bool $isAlert, ?string $onlyChannel = null): void
    {
        $this->newLine();

        if ($isAlert) {
            $this->line('<fg=yellow>Alert channels (report(), ALERTSTREAM_CHANNELS):</>');
            $this->printChannelGroup(
                active: config('alertstream.channels.active', []),
                destinations: config('alertstream.channels', []),
                envPrefix: 'ALERTSTREAM_',
                emptyHint: 'ALERTSTREAM_CHANNELS',
                onlyChannel: $onlyChannel
            );

            return;
        }

        $this->line('<fg=yellow>Log channels (log(), ALERTSTREAM_LOG_CHANNELS):</>');
        $this->printChannelGroup(
            active: config('alertstream.log_channels', []),
            destinations: config('alertstream.log_destinations', []),
            envPrefix: 'ALERTSTREAM_LOG_',
            emptyHint: 'ALERTSTREAM_LOG_CHANNELS',
            onlyChannel: $onlyChannel
        );
    }

    /**
     * Print the active/destination status for one channel group (either the
     * alert channels or the log channels). When $onlyChannel is set, only
     * that channel's status is printed.
     */
    protected function printChannelGroup(array $active, array $destinations, string $envPrefix, string $emptyHint, ?string $onlyChannel = null): void
    {
        if (empty($active)) {
            $this->warn("  ⚠  None active — set {$emptyHint} in your .env");

            return;
        }

        if ($onlyChannel !== null) {
            $active = array_intersect($active, [$onlyChannel]);
        }

        $webhookChannels = ['slack', 'teams', 'discord'];

        foreach ($active as $name) {
            $cfg = $destinations[$name] ?? [];
            $label = strtoupper($name);

            if (in_array($name, $webhookChannels, true)) {
                $webhook = $cfg['webhook'] ?? null;

                if ($webhook) {
                    $preview = substr($webhook, 0, 60) . (strlen($webhook) > 60 ? '...' : '');
                    $this->line("  ✓ {$label}  webhook set → {$preview}");
                } else {
                    $this->warn("  ✗ {$label}  webhook not set — add {$envPrefix}" . strtoupper($name) . '_WEBHOOK to your .env');
                }
            } elseif ($name === 'mail') {
                $to = $cfg['to'] ?? null;
                $this->line($to
                    ? "  ✓ MAIL  → {$to}"
                    : "  ✗ MAIL  to address not set — add {$envPrefix}MAIL_TO to your .env");
            } else {
                $this->line("  ✓ {$label}  (custom channel)");
            }
        }
    }

    /**
     * Send a test exception through a single tagged AlertChannel, bypassing
     * report()'s normal fan-out to every active alert channel.
     */
    protected function testSpecificAlertChannel(string $channelName, string $message, array $context): void
    {
        $active = config('alertstream.channels.active', []);

        if (! in_array($channelName, $active, true)) {
            throw new RuntimeException(
                "Alert channel '{$channelName}' is not in ALERTSTREAM_CHANNELS. Active channels: "
                . implode(', ', $active)
            );
        }

        $exception = new RuntimeException('Test exception for alert channel: ' . $channelName);

        /** @var AlertChannel $channel */
        foreach (app()->tagged('alertstream.channel') as $channel) {
            $className = class_basename($channel);
            if (str_contains(strtolower($className), $channelName)) {
                $channel->send($message, $exception, $context);

                return;
            }
        }

        throw new RuntimeException("Alert channel '{$channelName}' is active but could not be resolved from the container.");
    }

    /**
     * Send a test log message through a single `alertstream_<name>` log
     * channel, bypassing log()'s normal fan-out to every configured log
     * channel so you can verify one destination in isolation.
     */
    protected function testSpecificLogChannel(string $channelName, string $level, string $message, array $context): void
    {
        $configured = config('alertstream.log_channels', []);

        if (! in_array($channelName, $configured, true)) {
            throw new RuntimeException(
                "Log channel '{$channelName}' is not in ALERTSTREAM_LOG_CHANNELS. Configured log channels: "
                . implode(', ', $configured)
            );
        }

        Log::channel('alertstream_' . $channelName)->{$level}($message, $context);
    }
}
