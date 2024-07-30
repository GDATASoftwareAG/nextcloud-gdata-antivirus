<?php

namespace OCA\GDataVaas\Controller;

use Coduo\PHPHumanizer\NumberHumanizer;
use Exception;
use GuzzleHttp\Exception\ServerException;
use OCA\GDataVaas\Service\VerdictService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\EntityTooLargeException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IRequest;
use VaasSdk\Exceptions\FileDoesNotExistException;
use VaasSdk\Exceptions\InvalidSha256Exception;
use VaasSdk\Exceptions\TimeoutException;
use VaasSdk\Exceptions\UploadFailedException;
use VaasSdk\Exceptions\VaasAuthenticationException;

class ScanController extends Controller {
	private VerdictService $verdictService;

	public function __construct($appName, IRequest $request, VerdictService $verdictService) {
		parent::__construct($appName, $request);

		$this->verdictService = $verdictService;
	}

	/**
	 * Scans a file for malicious content with G DATA Verdict-as-a-Service and handles the result.
	 * @NoAdminRequired
	 * @param int $fileId
	 * @return JSONResponse
	 */
	public function scan(int $fileId): JSONResponse {
		try {
			$verdict = $this->verdictService->scanFileById($fileId);
			return new JSONResponse(['verdict' => $verdict->Verdict->value], 200);
		} catch (EntityTooLargeException) {
			return new JSONResponse(['error' => 'File is larger than ' . NumberHumanizer::binarySuffix(VerdictService::MAX_FILE_SIZE, 'de')], 413);
		} catch (FileDoesNotExistException) {
			return new JSONResponse(['error' => 'File does not exist'], 404);
		} catch (InvalidSha256Exception) {
			return new JSONResponse(['error' => 'Invalid SHA256'], 400);
		} catch (NotFoundException) {
			return new JSONResponse(['error' => 'File not found'], 404);
		} catch (NotPermittedException) {
			return new JSONResponse(['error' => 'Current settings do not permit scanning this.'], 403);
		} catch (TimeoutException) {
			return new JSONResponse(['error' => 'Scanning timed out'], 408);
		} catch (UploadFailedException|ServerException) {
			return new JSONResponse(['error' => "File $fileId could not be scanned with GData VaaS because there was a temporary upstream server error"], 500);
		} catch (VaasAuthenticationException) {
			return new JSONResponse(['error' => 'Authentication failed. Please check your credentials.'], 401);
		} catch (Exception $e) {
            return new JSONResponse(['error' => "An unknown error occurred while scanning file $fileId with GData VaaS. Please check the logs for more information and contact your administrator."], 500);
        }
	}
}
