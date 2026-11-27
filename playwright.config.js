import { defineConfig, devices } from '@playwright/test';
import os from 'os';

const chrome = `${os.homedir()}/Library/Caches/ms-playwright/chromium-1243/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing`;

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 60_000,
    workers: 1,
    fullyParallel: false,
    retries: 0,
    reporter: [['list'], ['json', { outputFile: 'test-results/e2e.json' }]],
    use: {
        baseURL: 'http://localhost:4362',
        // Uses the Chromium already cached on the dev machine (override with PW_CHROME on CI where `playwright install` runs).
        launchOptions: { executablePath: process.env.PW_CHROME || (process.env.CI ? undefined : chrome) },
        trace: 'retain-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 900 } } }],
    webServer: {
        command: 'sh -c "./scripts/e2e-env.sh && PHP_CLI_SERVER_WORKERS=4 APP_ENV=e2e php artisan serve --host=127.0.0.1 --port=4362 --no-reload"',
        url: 'http://localhost:4362/health',
        reuseExistingServer: false,
        timeout: 120_000,
    },
});
