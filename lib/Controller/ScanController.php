<?php
namespace OCA\GDataVaas\Controller;

use OCA\GDataVaas\Service\VerdictService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\EntityTooLargeException;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IRequest;
use OCP\AppFramework\Controller;
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
     * @param int $fileId
     * @return JSONResponse
     */
    public function scan(int $fileId): JSONResponse {
        try {
            $verdict = $this->verdictService->scanFileById($fileId);
            return new JSONResponse($verdict->value);
        } catch (EntityTooLargeException) {
            return new JSONResponse(['error' => 'File is too large'], 413);
        } catch (FileDoesNotExistException) {
            return new JSONResponse(['error' => 'File does not exist'], 404);
        } catch (InvalidPathException) {
            return new JSONResponse(['error' => 'Invalid path'], 400);
        } catch (InvalidSha256Exception) {
            return new JSONResponse(['error' => 'Invalid SHA256'], 400);
        } catch (NotFoundException) {
            return new JSONResponse(['error' => 'Not found'], 404);
        } catch (NotPermittedException) {
            return new JSONResponse(['error' => 'Not permitted'], 403);
        } catch (TimeoutException) {
            return new JSONResponse(['error' => 'Timeout'], 408);
        } catch (UploadFailedException) {
            return new JSONResponse(['error' => 'Upload failed'], 500);
        } catch (VaasAuthenticationException) {
            return new JSONResponse(['error' => 'VaaS authentication failed'], 401);
        }
    }
}
