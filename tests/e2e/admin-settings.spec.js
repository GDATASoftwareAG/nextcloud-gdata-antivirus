// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { test, expect } from '@playwright/test'
import { login } from './fixtures/auth.js'

test.beforeEach(async ({ page }) => {
	await login(page, 'admin', 'admin')
	await page.goto('/settings/admin/vaas')
})

test('admin settings page renders', async ({ page }) => {
	await expect(page.getByRole('heading', { name: /Administrator Settings|VaaS/ })).toBeVisible()
	await expect(page.getByLabel(/Authentication Method/i)).toBeVisible()
	await expect(page.getByRole('button', { name: /Save/i })).toBeVisible()
})

test('auth method switch shows correct fields', async ({ page }) => {
	await expect(page.getByLabel(/Client ID/i)).toBeVisible()
	await expect(page.getByLabel(/Client Secret/i)).toBeVisible()

	const authSelect = page.getByLabel(/Authentication Method/i)
	await authSelect.click()
	await page.getByRole('option', { name: /Resource Owner Password Flow/i }).click()

	await expect(page.getByLabel(/Username/i)).toBeVisible()
	await expect(page.getByLabel(/Password/i)).toBeVisible()
})

test('advanced settings section renders', async ({ page }) => {
	await expect(page.getByRole('heading', { name: /Advanced Settings/i })).toBeVisible()
	await expect(page.getByRole('button', { name: /Test/i })).toBeVisible()
	await expect(page.getByRole('button', { name: /Reset all tags/i })).toBeVisible()
})
