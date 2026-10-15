<?php
namespace App\Controllers;

use App\Database;
use App\Request;
use App\Response;
use App\Session;
use App\Services\AuthService;
use App\Services\FileValidationService;
use App\Services\StorageService;
use App\Services\DownloadService;
use Exception;

class AssetController {
    public function userAssets(Request $request): void {
        $user = AuthService::currentUser();
        $search = trim($request->query('search', ''));
        $collectionId = $request->query('collection');
        $status = $request->query('status');

        $where = ["a.uploader_id = :uid", "a.deleted_at IS NULL"];
        $params = ['uid' => $user['id']];

        if ($search !== '') {
            $where[] = "(a.original_filename LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if (!empty($collectionId)) {
            $where[] = "a.collection_id = :cid";
            $params['cid'] = (int)$collectionId;
        }

        if (!empty($status)) {
            $where[] = "a.status = :status";
            $params['status'] = $status;
        }

        $whereClause = implode(' AND ', $where);

        $assets = Database::fetchAll(
            "SELECT a.*, c.name as collection_name
             FROM assets a
             LEFT JOIN collections c ON a.collection_id = c.id
             WHERE $whereClause
             ORDER BY a.created_at DESC",
            $params
        );

        $collections = Database::fetchAll(
            "SELECT * FROM collections WHERE user_id = :uid OR is_global = 1 ORDER BY name ASC",
            ['uid' => $user['id']]
        );

        Response::view('user/assets', [
            'title' => 'My Assets — Workspace',
            'user' => $user,
            'assets' => $assets,
            'collections' => $collections,
            'search' => $search,
            'selectedCollection' => $collectionId,
            'selectedStatus' => $status,
            'success' => Session::flash('success'),
            'error' => Session::flash('error')
        ]);
    }

    public function adminAssets(Request $request): void {
        $user = AuthService::currentUser();
        $search = trim($request->query('search', ''));
        $uploaderId = $request->query('uploader');
        $collectionId = $request->query('collection');
        $status = $request->query('status');
        $extension = $request->query('extension');

        $where = ["a.deleted_at IS NULL"];
        $params = [];

        if ($search !== '') {
            $where[] = "(a.original_filename LIKE :search OR u.name LIKE :search2 OR u.email LIKE :search3)";
            $params['search'] = '%' . $search . '%';
            $params['search2'] = '%' . $search . '%';
            $params['search3'] = '%' . $search . '%';
        }

        if (!empty($uploaderId)) {
            $where[] = "a.uploader_id = :uid";
            $params['uid'] = (int)$uploaderId;
        }

        if (!empty($collectionId)) {
            $where[] = "a.collection_id = :cid";
            $params['cid'] = (int)$collectionId;
        }

        if (!empty($status)) {
            $where[] = "a.status = :status";
            $params['status'] = $status;
        }

        if (!empty($extension)) {
            $where[] = "a.file_extension = :ext";
            $params['ext'] = strtolower($extension);
        }

        $whereClause = implode(' AND ', $where);

        $assets = Database::fetchAll(
            "SELECT a.*, u.name as uploader_name, u.email as uploader_email, c.name as collection_name
             FROM assets a
             JOIN users u ON a.uploader_id = u.id
             LEFT JOIN collections c ON a.collection_id = c.id
             WHERE $whereClause
             ORDER BY a.created_at DESC",
            $params
        );

        $users = Database::fetchAll("SELECT id, name, email FROM users ORDER BY name ASC");
        $collections = Database::fetchAll("SELECT id, name FROM collections ORDER BY name ASC");

        Response::view('admin/assets', [
            'title' => 'All Assets & Selection Manager — Workspace',
            'user' => $user,
            'assets' => $assets,
            'users' => $users,
            'collections' => $collections,
            'search' => $search,
            'selectedUploader' => $uploaderId,
            'selectedCollection' => $collectionId,
            'selectedStatus' => $status,
            'selectedExtension' => $extension,
            'success' => Session::flash('success'),
            'error' => Session::flash('error')
        ]);
    }

    public function upload(Request $request): void {
        $user = AuthService::currentUser();
        $file = $request->file('file');
        $collectionId = $request->input('collection_id');
        $collectionId = !empty($collectionId) ? (int)$collectionId : null;

        try {
            if (!$file) {
                throw new Exception("No file payload was transmitted.");
            }

            // 1. Server-side validation
            $validation = FileValidationService::validateUpload($file, $user);

            // 2. Storage & Database entry
            $asset = StorageService::storeAsset($validation, $user['id'], $collectionId);

            if ($request->isAjax()) {
                Response::success($asset, 'File uploaded successfully', 201);
            }

            Session::flash('success', "Asset '{$asset['original_filename']}' uploaded successfully.");
            Response::redirect('/assets');
        } catch (Exception $e) {
            if ($request->isAjax()) {
                Response::error($e->getMessage(), 400);
            }
            Session::flash('error', $e->getMessage());
            Response::redirect('/assets/upload');
        }
    }

    public function download(Request $request, array $params): void {
        $user = AuthService::currentUser();
        $assetId = (int)($params['id'] ?? 0);
        DownloadService::deliverAsset($assetId, $user);
    }

    public function delete(Request $request, array $params): void {
        $user = AuthService::currentUser();
        $assetId = (int)($params['id'] ?? 0);
        $isAdmin = ($user['role'] === 'admin');

        try {
            StorageService::deleteAsset($assetId, $user['id'], $isAdmin);

            if ($request->isAjax()) {
                Response::success(null, 'Asset removed successfully.');
            }

            Session::flash('success', 'Asset removed successfully.');
            Response::redirect($isAdmin ? '/admin/assets' : '/assets');
        } catch (Exception $e) {
            if ($request->isAjax()) {
                Response::error($e->getMessage(), 400);
            }
            Session::flash('error', $e->getMessage());
            Response::redirect($isAdmin ? '/admin/assets' : '/assets');
        }
    }

    public function assetDetails(Request $request, array $params): void {
        $user = AuthService::currentUser();
        $assetId = (int)($params['id'] ?? 0);

        $asset = Database::fetch(
            "SELECT a.*, u.name as uploader_name, u.email as uploader_email, c.name as collection_name
             FROM assets a
             JOIN users u ON a.uploader_id = u.id
             LEFT JOIN collections c ON a.collection_id = c.id
             WHERE a.id = :id AND a.deleted_at IS NULL",
            ['id' => $assetId]
        );

        if (!$asset) {
            Response::error('Asset not found', 404);
        }

        // Authorization check
        if ($user['role'] !== 'admin' && (int)$asset['uploader_id'] !== (int)$user['id']) {
            Response::error('Unauthorized to view this asset', 403);
        }

        Response::success($asset);
    }
}
