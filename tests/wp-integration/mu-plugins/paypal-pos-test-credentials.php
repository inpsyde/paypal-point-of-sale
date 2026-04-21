<?php
/**
 * Bridges IZETTLE_API_KEY / IZETTLE_CLIENT_ID env vars into the plugin's
 * credentials service, which normally reads from WP options populated via the onboarding flow.
 */
add_action(
    // The "html" should be retreivable dynamically from the WPLITH container
    'inpsyde.modularity.html.init',
    static function ($package): void {
        $module = new class implements
            \Inpsyde\Modularity\Module\Module,
            \Inpsyde\Modularity\Module\ExtendingModule
        {
            public function id(): string
            {
                return 'paypal-pos-test-credentials';
            }

            public function extensions(): array
            {
                return [
                    'paypal-pos.oauth.credentials.parent' => static function () {
                        return new class implements \Psr\Container\ContainerInterface {
                            private array $envMap = [
                                'api_key' => 'IZETTLE_API_KEY',
                                'client_id' => 'IZETTLE_CLIENT_ID',
                            ];

                            public function get(string $id): string
                            {
                                if (!$this->has($id)) {
                                    throw new class extends \Exception implements
                                        \Psr\Container\NotFoundExceptionInterface {};
                                }
                                return (string) (getenv($this->envMap[$id]) ?: '');
                            }

                            public function has(string $id): bool
                            {
                                return isset($this->envMap[$id]);
                            }
                        };
                    },
                    // Add an error_log-backed PSR-3 logger to the CompoundLogger so every
                    // plugin log message appears in wp-content/paypal-pos.log.
                    'paypal-pos.logger' => static function ($compound) {
                        $compound->addLogger(new class extends \Psr\Log\AbstractLogger {
                            public function log($level, $message, array $context = []): void
                            {
                                $ctx = $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
                                file_put_contents(
                                    WP_CONTENT_DIR . '/paypal-pos.log',
                                    sprintf("[%s] [%s] %s%s\n", date('H:i:s'), $level, $message, $ctx),
                                    FILE_APPEND
                                );
                            }
                        });
                        return $compound;
                    },
                ];
            }
        };

        $package->addModule($module);
    }
);
