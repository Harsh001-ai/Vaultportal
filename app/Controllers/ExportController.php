<?php
namespace App\Controllers;

use App\Database;
use App\Request;
use App\Response;
use App\Session;
use App\Services\AuthService;
use App\Services\ExportProcessorService;
use App\Services\DownloadService;
use Exception;

class ExportController {
    public function index(Request $request): void {
        $user = AuthService::currentUser();

        $jobs = Database::fetchAll(
            "SELECT e.*, u.name as admin_name, u.email as admin_email
             FROM export_jobs e
             JOIN users u ON e.admin_id = u.id
             ORDER BY e.created_at DESC"
        );

        // Fetch source user summaries for each job
        foreach ($jobs as &$job) {
            $job['source_users'] = Database::fetchAll(
                "SELECT DISTINCT u.name, u.email 
                 FROM export_items ei 
                 JOIN users u ON ei.source_user_id = u.id 
                 WHERE ei.export_job_id = :jid",
                ['jid' => $job['id']]
            );
        }

        Response::view('admin/export_center', [
            'title' => 'Export Center & Archive Packaging — VaultPortal',
            'user' => $user,
            'jobs' => $jobs,
            'success' => Session::flash('success'),
            'error' => Session::flash('error')
        ]);
    }

    public function reviewApi(Request $request): void {
        $user = AuthService::currentUser();
        $assetIds = $request->json('asset_ids') ?? $request->input('asset_ids') ?? [];

        if (!is_array($assetIds)) {
            Response::error('Invalid asset_ids array payload.', 400);
        }

        $review = ExportProcessorService::reviewSelection($assetIds, $user['id']);
        Response::success($review);
    }

    public function createApi(Request $request): void {
        $user = AuthService::currentUser();
        $assetIds = $request->json('asset_ids') ?? $request->input('asset_ids') ?? [];
        $format = $request->json('format') ?? $request->input('format', 'zip_by_user');
        $exportName = $request->json('export_name') ?? $request->input('export_name');
        $notes = $request->json('notes') ?? $request->input('notes');
        $includeManifest = $request->json('include_manifest') ?? $request->input('include_manifest');

        try {
            if (empty($assetIds) || !is_array($assetIds)) {
                throw new Exception("Please select one or more assets to export.");
            }

            $job = ExportProcessorService::createExport($assetIds, $format, [
                'export_name' => $exportName,
                'notes' => $notes,
                'include_manifest' => $includeManifest
            ], $user['id']);

            Response::success($job, 'Export generated successfully', 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function statusApi(Request $request, array $params): void {
        $jobId = (int)($params['id'] ?? 0);
        $job = Database::fetch("SELECT * FROM export_jobs WHERE id = :id", ['id' => $jobId]);

        if (!$job) {
            Response::error('Export job not found', 404);
        }

        Response::success($job);
    }

    public function deliverDownload(Request $request, array $params): void {
        $token = $params['token'] ?? '';
        DownloadService::deliverExport($token);
    }
}
