// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Log in to Nextcloud as the given user.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} user
 * @param {string} password
 */
export async function login(page, user, password) {
	await page.goto('/login')
	await page.getByLabel(/User name|Benutzername/i).first().fill(user)
	await page.getByLabel(/Password|Passwort/i).first().fill(password)
	await page.getByRole('button', { name: /Log in|Anmelden/i }).click()
	await page.waitForURL(/apps\/dashboard|\/files/)
}
