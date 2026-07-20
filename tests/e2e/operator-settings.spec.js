// SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { test, expect } from '@playwright/test'
import { login } from './fixtures/auth.js'

test.beforeEach(async ({ page }) => {
	await login(page, 'admin', 'admin')
	await page.goto('/settings/admin/vaas')
})

test('operator settings page renders', async ({ page }) => {
	await expect(page.getByRole('heading', { name: /Operator Settings/i })).toBeVisible()
	await expect(page.getByLabel(/Quarantine folder/i)).toBeVisible()
	await expect(page.getByLabel(/Scan only this/i)).toBeVisible()
	await expect(page.getByLabel(/Do not scan this/i)).toBeVisible()
	await expect(page.getByLabel(/Notify Mails/i)).toBeVisible()
})

test('operator scan settings render', async ({ page }) => {
	await expect(page.getByRole('heading', { name: /Scan Settings/i })).toBeVisible()
	await expect(page.getByText(/Automatic file scanning/i)).toBeVisible()
	await expect(page.getByText(/Set prefix for malicious files/i)).toBeVisible()
	await expect(page.getByText(/Disable Unscanned tag/i)).toBeVisible()
	await expect(page.getByText(/Send mails on infected file upload/i)).toBeVisible()
})

test('operator can change quarantine folder', async ({ page }) => {
	const field = page.getByLabel(/Quarantine folder/i)
	await field.fill('TestQuarantine')
	await page.getByRole('button', { name: /Save/i }).click()
	await expect(page.getByText(/Data saved successfully/i)).toBeVisible()
})
