<?php

namespace NightshiftFoundry\AlertStream\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use NightshiftFoundry\AlertStream\Channels\Contracts\AlertChannel;
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
                            {channel? : The alerting channel to test (e.g. slack, teams, discord, mail)}
                            {--type=alert : The message type — "alert" sends a report; any other value (debug, info, warning, error, critical, etc.) is passed as a log level}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AlertStream by sending a test alert to all or a specific channel';

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
            $message = 'AlertStream Test Alert';

            $testData = [
                'environment' => app()->environment(),
                'php_version' => PHP_VERSION,
                'laravel_version' => Application::VERSION,
                'test_timestamp' => now(),
                'severity' => 'warning',
            ];

            if ($targetChannel) {
                $this->testSpecificChannel($targetChannel, $message, $testData);
            } elseif ($type !== 'alert') {
                $alertStream->log($type, $message, $testData);
            } else {
                $alertStream->report($message, new RuntimeException('Test exception from alertstream:test'), $testData);
            }

            $this->info('✓ AlertStream test alert sent successfully!');
            $this->line('Message: ' . $message);
            $this->line('Type: ' . $type);
            if ($targetChannel) {
                $this->line('Channel: ' . $targetChannel);
            }

            $this->printChannelStatus();

            return 0;
        } catch (Exception $e) {
            $this->error('✗ Failed to send test alert: ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * Print a diagnostic summary of active notification channels and their configuration.
     */
    protected function printChannelStatus(): void
    {
        $channelsConfig = config('alertstream.channels', []);
        $active = $channelsConfig['active'] ?? [];

        $this->newLine();
        $this->line('<fg=yellow>Notification channels:</>');

        if (empty($active)) {
            $this->warn('  ⚠  None active — set ALERTSTREAM_CHANNELS in your .env');
            $this->line('     e.g. ALERTSTREAM_CHANNELS=teams');

            return;
        }

        $webhookChannels = ['slack', 'teams', 'discord'];

        foreach ($active as $name) {
            $cfg = $channelsConfig[$name] ?? [];
            $label = strtoupper($name);

            if (in_array($name, $webhookChannels, true)) {
                $webhook = $cfg['webhook'] ?? null;

                if ($webhook) {
                    $preview = substr($webhook, 0, 60) . (strlen($webhook) > 60 ? '...' : '');
                    $this->line("  ✓ {$label}  webhook set → {$preview}");
                } else {
                    $this->warn("  ✗ {$label}  webhook not set — add ALERTSTREAM_" . strtoupper($name) . '_WEBHOOK to your .env');
                }
            } elseif ($name === 'mail') {
                $to = $cfg['to'] ?? null;
                $this->line($to
                    ? "  ✓ MAIL  → {$to}"
                    : '  ✗ MAIL  to address not set — add ALERTSTREAM_MAIL_TO to your .env');
            } else {
                $this->line("  ✓ {$label}  (custom channel)");
            }
        }
    }

    protected function testSpecificChannel(string $channelName, string $message, array $context): void
    {
        $channelMap = config('alertstream.channels', []);

        if (! in_array($channelName, $channelMap['active'] ?? [], true)) {
            throw new RuntimeException(
                "Channel '{$channelName}' is not in ALERTSTREAM_CHANNELS. Active channels: "
                . implode(', ', $channelMap['active'] ?? [])
            );
        }

        $exception = new RuntimeException('Test exception for channel: ' . $channelName);

        /** @var AlertChannel $channel */
        foreach (app()->tagged('alertstream.channel') as $channel) {
            $className = class_basename($channel);
            if (str_contains(strtolower($className), $channelName)) {
                $channel->send($message, $exception, $context);

                return;
            }
        }

        throw new RuntimeException("Channel '{$channelName}' is active but could not be resolved from the container.");
    }
}
