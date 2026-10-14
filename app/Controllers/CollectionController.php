<?php
namespace App\Controllers;

use App\Database;
use App\Request;
use App\Response;
use App\Session;
use App\Services\AuthService;
use App\Services\AuditService;
use Exception;

class CollectionController {
    public function index(Request $request): void {
        $user = AuthService::currentUser();
        $isAdmin = ($user['role'] === 'admin');

        $sql = "SELECT c.*, u.name as owner_name, COUNT(a.id) as asset_count, COALESCE(SUM(a.file_size_bytes), 0) as total_bytes
                FROM collections c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN assets a ON c.id = a.collection_id AND a.deleted_at IS NULL
                WHERE " . ($isAdmin ? "1=1" : "(c.user_id = :uid OR c.is_global = 1)") . "
                GROUP BY c.id
                ORDER BY c.created_at DESC";

        $params = $isAdmin ? [] : ['uid' => $user['id']];
        $collections = Database::fetchAll($sql, $params);

        Response::view('user/collections', [
            'title' => 'Asset Collections — VaultPortal',
            'user' => $user,
            'collections' => $collections,
            'isAdmin' => $isAdmin,
            'success' => Session::flash('success'),
            'error' => Session::flash('error')
        ]);
    }

    public function store(Request $request): void {
        $user = AuthService::currentUser();
        $name = trim($request->input('name', ''));
        $description = trim($request->input('description', ''));
        $isGlobal = ($user['role'] === 'admin') && $request->input('is_global') ? 1 : 0;

        try {
            if (empty($name)) {
                throw new Exception("Collection name cannot be empty.");
            }

            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $id = Database::insert('collections', [
                'uuid' => $uuid,
                'user_id' => $user['id'],
                'name' => $name,
                'description' => $description ?: null,
                'is_global' => $isGlobal,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            AuditService::log('collection.create', 'collection', (string)$id, [
                'name' => $name,
                'is_global' => $isGlobal
            ]);

            Session::flash('success', "Collection '{$name}' created.");
            Response::redirect('/collections');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/collections');
        }
    }

    public function delete(Request $request, array $params): void {
        $user = AuthService::currentUser();
        $cid = (int)($params['id'] ?? 0);
        $isAdmin = ($user['role'] === 'admin');

        $coll = Database::fetch("SELECT * FROM collections WHERE id = :id", ['id' => $cid]);
        if (!$coll) {
            Response::redirect('/collections');
        }

        if (!$isAdmin && (int)$coll['user_id'] !== (int)$user['id']) {
            Session::flash('error', 'Unauthorized to delete this collection.');
            Response::redirect('/collections');
        }

        Database::query("UPDATE assets SET collection_id = NULL WHERE collection_id = :cid", ['cid' => $cid]);
        Database::query("DELETE FROM collections WHERE id = :id", ['id' => $cid]);

        AuditService::log('collection.delete', 'collection', (string)$cid, ['name' => $coll['name']]);
        Session::flash('success', "Collection '{$coll['name']}' deleted. Associated assets preserved in unorganized storage.");
        Response::redirect('/collections');
    }
}
