// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { defineConfig, devices } from '@playwright/test'

const baseURL = process.env.NEXTCLOUD_BASE_URL || 'http://127.0.0.1:8080'

export default defineConfig({
	testDir: './tests/e2e',
	fullyParallel: false,
	workers: 1,
	retries: process.env.CI ? 2 : 0,
	reporter: process.env.CI ? 'github' : 'list',
	use: {
		baseURL,
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
	},

	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
	],
})
