<!--
  SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection :name="t('gdatavaas', 'Administrator Settings')">
		<div class="gdatavaas-settings-form">
			<p class="settings-hint">
				{{ t('gdatavaas', 'You may use self registration and create a new username and password by yourself') }}
				<a href="https://vaas.gdata.de/login" target="_blank" rel="noopener noreferrer">
					{{ t('gdatavaas', 'here') }}
				</a>
				{{ t('gdatavaas', 'for free.') }}
			</p>

			<NcSelect
				v-model="formData.authMethod"
				:options="authMethods"
				:reduce="option => option.value"
				:clearable="false"
				:input-label="t('gdatavaas', 'Authentication Method')"
			/>
			<p class="settings-hint">
				{{ t('gdatavaas', 'If you have registered yourself with your e-mail address and a password, select "Resource Owner Password Flow" here, if you have received a client id and a client secret from G DATA CyberDefense AG, use "Client Credentials Flow". You can ignore the other fields.') }}
			</p>

			<template v-if="showBasicAuth">
				<NcTextField
					v-model="formData.username"
					:label="t('gdatavaas', 'Username')"
					type="text"
				/>
				<NcTextField
					v-model="formData.password"
					:label="t('gdatavaas', 'Password')"
					type="password"
				/>
			</template>

			<template v-if="showClientCredentials">
				<NcTextField
					v-model="formData.clientId"
					:label="t('gdatavaas', 'Client ID')"
					type="text"
				/>
				<NcTextField
					v-model="formData.clientSecret"
					:label="t('gdatavaas', 'Client Secret')"
					type="password"
				/>
			</template>

			<NcTextField
				v-model.number="formData.maxScanSize"
				:label="t('gdatavaas', 'Maximum scan size (MB)')"
				:helper-text="maxScanSizeHint"
				type="number"
				min="0"
			/>

			<NcTextField
				v-model.number="formData.timeout"
				:label="t('gdatavaas', 'Timeout (seconds)')"
				:helper-text="timeoutHint"
				type="number"
				min="0"
			/>

			<NcCheckboxRadioSwitch
				v-model="formData.cache"
				type="switch"
				:description="cacheDescription"
			>
				{{ t('gdatavaas', 'Cache') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch
				v-model="formData.hashlookup"
				type="switch"
				:description="hashlookupDescription"
			>
				{{ t('gdatavaas', 'Hash lookup') }}
			</NcCheckboxRadioSwitch>

			<NcButton
				type="primary"
				:loading="isSaving"
				@click="saveBasicSettings"
			>
				{{ t('gdatavaas', 'Save') }}
			</NcButton>
		</div>
	</NcSettingsSection>

	<NcSettingsSection
		class="gdatavaas-advanced-section"
		:name="t('gdatavaas', 'Advanced Settings')"
		:description="t('gdatavaas', 'If you are not sure about this, you can just leave it blank.')"
	>
		<div class="gdatavaas-settings-form">
			<NcTextField
				v-model="formData.tokenEndpoint"
				:label="t('gdatavaas', 'Token Endpoint')"
				type="text"
			/>

			<NcTextField
				v-model="formData.vaasUrl"
				:label="t('gdatavaas', 'Vaas URL')"
				type="text"
			/>

			<div class="gdatavaas-button-group">
				<NcButton
					type="secondary"
					:loading="isTesting"
					@click="testSettings"
				>
					{{ t('gdatavaas', 'Test') }}
				</NcButton>
				<NcButton
					type="primary"
					:loading="isAdvancedSaving"
					@click="saveAdvancedSettings"
				>
					{{ t('gdatavaas', 'Save') }}
				</NcButton>
				<NcButton
					type="error"
					:loading="isResettingTags"
					@click="resetAllTags"
				>
					{{ t('gdatavaas', 'Reset all tags') }}
				</NcButton>
			</div>
		</div>
	</NcSettingsSection>
</template>

<script setup>
import { ref, computed } from 'vue'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'

const props = defineProps({
	initial: {
		type: Object,
		default: () => ({}),
	},
})

const API_BASE = generateUrl('/apps/gdatavaas')

const authMethods = [
	{ label: 'Client Credentials Flow', value: 'ClientCredentials' },
	{ label: 'Resource Owner Password Flow', value: 'ResourceOwnerPassword' },
]

const formData = ref({
	authMethod: props.initial.authMethod ?? 'ClientCredentials',
	username: props.initial.username ?? '',
	password: props.initial.password ?? '',
	clientId: props.initial.clientId ?? '',
	clientSecret: props.initial.clientSecret ?? '',
	maxScanSize: props.initial.maxScanSizeInMB ?? 256,
	timeout: props.initial.timeout ?? 300,
	cache: props.initial.cache ?? true,
	hashlookup: props.initial.hashlookup ?? true,
	tokenEndpoint: props.initial.tokenEndpoint ?? '',
	vaasUrl: props.initial.vaasUrl ?? '',
})

const isSaving = ref(false)
const isAdvancedSaving = ref(false)
const isTesting = ref(false)
const isResettingTags = ref(false)

const showBasicAuth = computed(() => formData.value.authMethod === 'ResourceOwnerPassword')
const showClientCredentials = computed(() => formData.value.authMethod === 'ClientCredentials')

const maxScanSizeHint = computed(() => t('gdatavaas', "The maximum scan size for files to be scanned in MB. Files above this limit are tagged as 'Won\\'t Scan'."))
const timeoutHint = computed(() => t('gdatavaas', 'The timeout determines how long a file scan may take in seconds before it is canceled. Please note: If the timeout is set too short, it will restrict the scanning of large files, which take a little longer.'))
const cacheDescription = computed(() => t('gdatavaas', 'If this option is disabled, each file is always scanned again and no results are cached.'))
const hashlookupDescription = computed(() => t('gdatavaas', 'During a hash lookup, the SHA256 checksum is transmitted to the G DATA Cloud before the scan to check whether a result is already available, thereby saving unnecessary network traffic, resource load, and time.'))

const apiRequest = async (method = 'GET', endpoint = '', data = {}) => {
	const options = {
		method,
		headers: {
			'Content-Type': 'application/json',
			requesttoken: getRequestToken(),
		},
	}

	if (method !== 'GET') {
		options.body = JSON.stringify(data)
	}

	const response = await fetch(`${API_BASE}${endpoint}`, options)
	return response.json()
}

const saveBasicSettings = async () => {
	isSaving.value = true
	try {
		const response = await apiRequest('POST', '/adminSettings', {
			username: formData.value.username,
			password: formData.value.password,
			clientId: formData.value.clientId,
			clientSecret: formData.value.clientSecret,
			authMethod: formData.value.authMethod,
			maxScanSize: formData.value.maxScanSize,
			timeout: formData.value.timeout,
			cache: formData.value.cache,
			hashlookup: formData.value.hashlookup,
		})

		if (response.status === 'success') {
			showSuccess(t('gdatavaas', 'Data saved successfully.'))
		} else {
			showError(response.message || t('gdatavaas', 'An error occurred when saving the data.'))
		}
	} catch (error) {
		console.error('Error saving settings:', error)
		showError(t('gdatavaas', 'An error occurred when saving the data.'))
	} finally {
		isSaving.value = false
	}
}

const testSettings = async () => {
	isTesting.value = true
	try {
		const response = await apiRequest('POST', '/testsettings', {
			tokenEndpoint: formData.value.tokenEndpoint,
			vaasUrl: formData.value.vaasUrl,
		})

		if (response.status === 'success') {
			showSuccess(t('gdatavaas', 'Authentication successful and VaaS backend reachable.'))
		} else {
			showError(response.message || t('gdatavaas', 'An error occurred during the test.'))
		}
	} catch (error) {
		console.error('Error testing settings:', error)
		showError(t('gdatavaas', 'An error occurred during the test.'))
	} finally {
		isTesting.value = false
	}
}

const saveAdvancedSettings = async () => {
	isAdvancedSaving.value = true
	try {
		const response = await apiRequest('POST', '/setAdvancedConfig', {
			tokenEndpoint: formData.value.tokenEndpoint,
			vaasUrl: formData.value.vaasUrl,
		})

		if (response.status === 'success') {
			showSuccess(t('gdatavaas', 'Data saved successfully.'))
		} else {
			showError(t('gdatavaas', 'An error occurred when saving the data.'))
		}
	} catch (error) {
		console.error('Error saving advanced settings:', error)
		showError(t('gdatavaas', 'An error occurred when saving the data.'))
	} finally {
		isAdvancedSaving.value = false
	}
}

const resetAllTags = async () => {
	isResettingTags.value = true
	try {
		const response = await apiRequest('POST', '/resetalltags', {})

		if (response.status === 'success') {
			showSuccess(t('gdatavaas', 'All tags have been reset successfully.'))
		} else {
			showError(t('gdatavaas', 'An error occurred when resetting the tags.'))
		}
	} catch (error) {
		console.error('Error resetting tags:', error)
		showError(t('gdatavaas', 'An error occurred when resetting the tags.'))
	} finally {
		isResettingTags.value = false
	}
}
</script>

<style scoped lang="scss">
.gdatavaas-settings-form {
	display: flex;
	flex-direction: column;
	gap: 1rem;
	max-width: 70%;
}

.gdatavaas-advanced-section {
	margin-top: 50px;
}

.gdatavaas-button-group {
	display: flex;
	flex-wrap: wrap;
	gap: 0.5rem;

	&:hover {
		background-color: inherit;
	}
}

.settings-hint {
	color: var(--color-text-maxcontrast);

	a {
		color: blue;

		&:hover {
			text-decoration: underline;
		}
	}
}
</style>
