<!--
  SPDX-FileCopyrightText: 2025 G DATA CyberDefense AG <vaas@gdata.de>
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection :name="t('gdatavaas', 'Operator Settings')">
		<div class="gdatavaas-settings-form">
			<NcTextField
				v-model="formData.quarantineFolder"
				:label="t('gdatavaas', 'Quarantine folder')"
				:helper-text="quarantineFolderHint"
				type="text"
			/>

			<NcTextField
				v-model="formData.scanOnlyThis"
				:label="t('gdatavaas', 'Scan only this')"
				:helper-text="scanOnlyThisHint"
				type="text"
			/>

			<NcTextField
				v-model="formData.doNotScanThis"
				:label="t('gdatavaas', 'Do not scan this')"
				:helper-text="doNotScanThisHint"
				type="text"
			/>

			<NcTextField
				v-model="formData.notifyMails"
				:label="t('gdatavaas', 'Notify Mails')"
				:helper-text="notifyMailsHint"
				type="text"
			/>

			<NcNoteCard type="warning">
				{{ t('gdatavaas', 'Caution: The use of the "Scan only this" and "Do not scan this" settings should be approached with caution. Using these settings allows malicious users to upload and distribute malicious content via the Nextcloud instance. It is recommended that you carefully consider the implications of these settings and use them in a way that does not jeopardize the security of your system and data.') }}
			</NcNoteCard>

			<NcButton
				type="primary"
				:loading="isSaving"
				@click="saveOperatorSettings"
			>
				{{ t('gdatavaas', 'Save') }}
			</NcButton>
		</div>
	</NcSettingsSection>

	<NcSettingsSection :name="t('gdatavaas', 'Scan Settings')" class="gdatavaas-scan-section">
		<div class="gdatavaas-settings-form">
			<NcCheckboxRadioSwitch
				v-model="formData.autoScanFiles"
				type="switch"
				:loading="isAutoScanUpdating"
				@update:model-value="updateAutoScan"
			>
				{{ t('gdatavaas', 'Automatic file scanning') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch
				v-model="formData.prefixMalicious"
				type="switch"
				:loading="isPrefixUpdating"
				:description="prefixDescription"
				@update:model-value="updatePrefixMalicious"
			>
				{{ t('gdatavaas', 'Set prefix for malicious files') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch
				v-model="formData.disableUnscannedTag"
				type="switch"
				:loading="isDisableTagUpdating"
				:description="disableUnscannedDescription"
				@update:model-value="updateDisableUnscannedTag"
			>
				{{ t('gdatavaas', 'Disable Unscanned tag') }}
			</NcCheckboxRadioSwitch>

			<NcCheckboxRadioSwitch
				v-model="formData.sendMailOnVirusUpload"
				type="switch"
				:loading="isSendMailUpdating"
				:description="sendMailDescription"
				@update:model-value="updateSendMailOnVirusUpload"
			>
				{{ t('gdatavaas', 'Send mails on infected file upload') }}
			</NcCheckboxRadioSwitch>

			<NcProgressBar
				v-if="!scanCounter.loading"
				:value="scanCounter.scanned"
				:max="scanCounter.all"
			/>
			<NcLoadingIcon v-if="scanCounter.loading" :size="20" />
			<p v-if="!scanCounter.loading" class="scan-counter-text">
				{{ t('gdatavaas', 'Files scanned:') }} {{ scanCounter.text }}
			</p>
		</div>
	</NcSettingsSection>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'

const props = defineProps({
	initial: {
		type: Object,
		default: () => ({}),
	},
})

const API_BASE = generateUrl('/apps/gdatavaas')

const formData = ref({
	quarantineFolder: props.initial.quarantineFolder ?? '',
	scanOnlyThis: props.initial.scanOnlyThis ?? '',
	doNotScanThis: props.initial.doNotScanThis ?? '',
	notifyMails: props.initial.notifyMail ?? '',
	autoScanFiles: props.initial.autoScanFiles ?? false,
	prefixMalicious: props.initial.prefixMalicious ?? false,
	disableUnscannedTag: props.initial.disableUnscannedTag ?? false,
	sendMailOnVirusUpload: props.initial.sendMailOnVirusUpload ?? false,
})

const isSaving = ref(false)
const isAutoScanUpdating = ref(false)
const isPrefixUpdating = ref(false)
const isDisableTagUpdating = ref(false)
const isSendMailUpdating = ref(false)

const scanCounter = ref({
	loading: true,
	scanned: 0,
	all: 0,
	text: 'N/A',
})

const quarantineFolderHint = computed(() => t('gdatavaas', 'Files scanned as "Malicious" are moved to this folder. They can still be downloaded etc. there, but this helps to prevent accidental use.'))
const scanOnlyThisHint = computed(() => t('gdatavaas', 'Comma-separated allow list values. Can be paths, folders, file names or file types. Wildcards are not supported.'))
const doNotScanThisHint = computed(() => t('gdatavaas', 'Comma-separated block list values. Can be paths, folders, file names or file types. Wildcards are not supported.'))
const notifyMailsHint = computed(() => t('gdatavaas', 'Mail addresses for notifications when malicious files are found or a user tries to upload them. Must be comma-separated.'))
const prefixDescription = computed(() => t('gdatavaas', 'If the scan result is "Malicious", this is added to the front of the file name. Increases the visibility of malicious content.'))
const disableUnscannedDescription = computed(() => t('gdatavaas', 'Files that have not yet been scanned will no longer be tagged "Unscanned", but they will still be scanned if "Automatic file scanning" is switched on.'))
const sendMailDescription = computed(() => t('gdatavaas', "If a user tries to upload an infected file an email is send to all 'Notify Mails' receiver"))

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

const saveOperatorSettings = async () => {
	isSaving.value = true
	try {
		const response = await apiRequest('POST', '/operatorSettings', {
			quarantineFolder: formData.value.quarantineFolder,
			scanOnlyThis: formData.value.scanOnlyThis,
			doNotScanThis: formData.value.doNotScanThis,
			notifyMails: formData.value.notifyMails,
		})

		if (response.status === 'success') {
			showSuccess(t('gdatavaas', 'Data saved successfully.'))
		} else {
			showError(response.message || t('gdatavaas', 'An error occurred when saving the data.'))
		}
	} catch (error) {
		console.error('Error saving operator settings:', error)
		showError(t('gdatavaas', 'An error occurred when saving the data.'))
	} finally {
		isSaving.value = false
	}
}

const updateAutoScan = async () => {
	isAutoScanUpdating.value = true
	try {
		const response = await apiRequest('POST', '/setAutoScan', {
			autoScanFiles: formData.value.autoScanFiles,
		})

		if (response.status !== 'success') {
			formData.value.autoScanFiles = !formData.value.autoScanFiles
			showError(t('gdatavaas', 'An error occurred while updating the setting.'))
		}
	} catch (error) {
		console.error('Error updating auto scan:', error)
		formData.value.autoScanFiles = !formData.value.autoScanFiles
		showError(t('gdatavaas', 'An error occurred while updating the setting.'))
	} finally {
		isAutoScanUpdating.value = false
	}
}

const updatePrefixMalicious = async () => {
	isPrefixUpdating.value = true
	try {
		const response = await apiRequest('POST', '/setPrefixMalicious', {
			prefixMalicious: formData.value.prefixMalicious,
		})

		if (response.status !== 'success') {
			formData.value.prefixMalicious = !formData.value.prefixMalicious
			showError(t('gdatavaas', 'An error occurred while updating the setting.'))
		}
	} catch (error) {
		console.error('Error updating prefix malicious:', error)
		formData.value.prefixMalicious = !formData.value.prefixMalicious
		showError(t('gdatavaas', 'An error occurred while updating the setting.'))
	} finally {
		isPrefixUpdating.value = false
	}
}

const updateDisableUnscannedTag = async () => {
	isDisableTagUpdating.value = true
	try {
		const response = await apiRequest('POST', '/setDisableUnscannedTag', {
			disableUnscannedTag: formData.value.disableUnscannedTag,
		})

		if (response.status !== 'success') {
			formData.value.disableUnscannedTag = !formData.value.disableUnscannedTag
			showError(t('gdatavaas', 'An error occurred while updating the setting.'))
		}
	} catch (error) {
		console.error('Error updating disable tag:', error)
		formData.value.disableUnscannedTag = !formData.value.disableUnscannedTag
		showError(t('gdatavaas', 'An error occurred while updating the setting.'))
	} finally {
		isDisableTagUpdating.value = false
	}
}

const updateSendMailOnVirusUpload = async () => {
	isSendMailUpdating.value = true
	try {
		const response = await apiRequest('POST', '/setSendMailOnVirusUpload', {
			sendMailOnVirusUpload: formData.value.sendMailOnVirusUpload,
		})

		if (response.status !== 'success') {
			formData.value.sendMailOnVirusUpload = !formData.value.sendMailOnVirusUpload
			showError(t('gdatavaas', 'An error occurred while updating the setting.'))
		}
	} catch (error) {
		console.error('Error updating send mail:', error)
		formData.value.sendMailOnVirusUpload = !formData.value.sendMailOnVirusUpload
		showError(t('gdatavaas', 'An error occurred while updating the setting.'))
	} finally {
		isSendMailUpdating.value = false
	}
}

const loadCounters = async () => {
	try {
		const countersResponse = await apiRequest('GET', '/getCounters')

		if (countersResponse.status === 'success') {
			scanCounter.value.scanned = countersResponse.scanned
			scanCounter.value.all = countersResponse.all
			scanCounter.value.text = `${countersResponse.scanned} / ${countersResponse.all}`
		} else {
			scanCounter.value.text = 'N/A'
			if (countersResponse.message) {
				console.error('Error getting files counter:', countersResponse.message)
			}
		}
	} catch (error) {
		console.error('Error loading counters:', error)
	} finally {
		scanCounter.value.loading = false
	}
}

onMounted(() => {
	loadCounters()
})
</script>

<style scoped lang="scss">
.gdatavaas-settings-form {
	display: flex;
	flex-direction: column;
	gap: 1rem;
	max-width: 70%;
}

.gdatavaas-scan-section {
	margin-top: 50px;
}

.scan-counter-text {
	font-weight: 600;
	color: var(--color-primary-element);
}
</style>
