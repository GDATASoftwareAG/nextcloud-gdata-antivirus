<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\Controller;

use Exception;
use OCA\GDataVaas\AppInfo\Application;
use OCA\GDataVaas\Service\VerdictService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\EntityTooLargeException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use OCP\IRequest;
use VaasSdk\Exceptions\VaasAuthenticationException;

class ScanController extends Controller {
	private IAppConfig $config;
	private VerdictService $verdictService;

	public function __construct($appName, IRequest $request, VerdictService $verdictService, IAppConfig $config) {
		parent::__construct($appName, $request);

		$this->config = $config;
		$this->verdictService = $verdictService;
	}

	/**
	 * Scans a file for malicious content with G DATA Verdict-as-a-Service and handles the result.
	 * @param int $fileId
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function scan(int $fileId): JSONResponse {
		try {
			$verdict = $this->verdictService->scanFileById($fileId);
			return new JSONResponse(['verdict' => $verdict->verdict->value], 200);
		} catch (EntityTooLargeException) {
			return new JSONResponse(
				['error' => 'File is larger than '
					. $this->config->getValueInt(Application::APP_ID, 'maxScanSizeInMB', 256) . 'MB.'], 413);
		} catch (NotFoundException) {
			return new JSONResponse(['error' => "File $fileId not found"], 404);
		} catch (NotPermittedException) {
			return new JSONResponse(
				['error' => "Current settings do not permit scanning file with ID $fileId"], 403);
		} catch (VaasAuthenticationException) {
			return new JSONResponse(
				['error' => 'Authentication failed. Please check your credentials.'], 401);
		} catch (Exception) {
			return new JSONResponse(
				['error' => "An unexpected error occurred while scanning file $fileId with GData VaaS. Please
				check the logs for more information and contact your administrator."], 500);
		}
	}
}
