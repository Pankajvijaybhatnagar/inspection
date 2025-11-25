<?php
require_once __DIR__ . '/config.php';

class Inquiries
{
    private $db;

    private $inquiriesTableFields = [
        'id','name','email','phone','subject','message','type','status','remark','resolved_by','resolved_at','created_at'
    ];

    private $creatableInquiryFields = [
        'name','email','phone','subject','message','type'
    ];

    private $updatableInquiryFields = [
        'status','resolved_by','remark','type'
    ];

    public function __construct()
    {
        $config = new Config();
        $this->db = $this->getDBFromConfig($config);
    }

    private function getDBFromConfig($config)
    {
        $reflection = new ReflectionClass($config);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        return $property->getValue($config);
    }

    public function createInquiry($data, $categoryIds = [])
    {
        $setParts = [];
        $params = [];

        if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
            http_response_code(400);
            return ['status'=>false,'message'=>'Name, email, and message are required.'];
        }

        foreach ($data as $field => $value) {
            if (in_array($field, $this->creatableInquiryFields)) {
                $setParts[] = "`$field`=:$field";
                $params[$field] = $value;
            }
        }

        if (empty($setParts)) {
            http_response_code(400);
            return ['status'=>false,'message'=>'No valid fields provided for creation.'];
        }

        try {
            $sql = "INSERT INTO inquiries SET " . implode(',', $setParts);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $inquiryId = $this->db->lastInsertId();

            http_response_code(201);
            return ['status'=>true,'message'=>'Inquiry submitted successfully.','inquiry_id'=>(int)$inquiryId];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status'=>false,'message'=>'Inquiry submission failed: '.$e->getMessage()];
        }
    }

    public function getInquiries($filters = [])
    {
        $page = isset($filters['page']) && is_numeric($filters['page']) ? (int)$filters['page'] : 1;
        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int)$filters['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $select = "SELECT i.*,u.name as resolver_name,u.avatar as resolver_avatar FROM inquiries i LEFT JOIN users u ON i.resolved_by=u.id";
        $countSql = "SELECT COUNT(i.id) FROM inquiries i LEFT JOIN users u ON i.resolved_by=u.id";
        $totalInquiriesSql = "SELECT COUNT(id) FROM inquiries";

        $whereParts = [];
        $params = [];

        foreach ($filters as $field => $value) {
            if (in_array($field, $this->inquiriesTableFields) && $value !== null) {
                $whereParts[] = "i.`$field`=:$field";
                $params[$field] = $value;
            }
        }

        if (!empty($filters['search'])) {
            $whereParts[] = "(i.name LIKE :search OR i.email LIKE :search OR i.subject LIKE :search OR i.message LIKE :search OR i.type LIKE :search OR i.remark LIKE :search OR u.name LIKE :search)";
            $params['search'] = '%'.$filters['search'].'%';
        }

        $whereClause = !empty($whereParts) ? " WHERE ".implode(' AND ', $whereParts) : "";
        $sql = $select.$whereClause." ORDER BY i.created_at DESC LIMIT :limit OFFSET :offset";
        $countSql .= $whereClause;

        try {
            $stmtTotal = $this->db->query($totalInquiriesSql);
            $totalInquiries = $stmtTotal->fetchColumn();

            $stmtFilteredCount = $this->db->prepare($countSql);
            $stmtFilteredCount->execute($params);
            $filteredCount = $stmtFilteredCount->fetchColumn();

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status'=>true,
                'total_inquiries'=>(int)$totalInquiries,
                'count'=>(int)$filteredCount,
                'fetched_count'=>count($inquiries),
                'page'=>$page,
                'limit'=>$limit,
                'total_pages'=>(int)ceil($filteredCount / $limit),
                'data'=>$inquiries
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status'=>false,'message'=>'Error fetching inquiries: '.$e->getMessage()];
        }
    }

    public function updateInquiry($inquiryId, $data)
    {
        $setParts = [];
        $params = [];

        if (!isset($inquiryId)) {
            http_response_code(400);
            return ['status'=>false,'message'=>'Inquiry ID is required for update.'];
        }

        $params['inquiry_id'] = $inquiryId;

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableInquiryFields)) {
                $setParts[] = "`$field`=:$field";
                $params[$field] = $value;
            }
        }

        if (isset($data['status']) && $data['status'] === 'resolved' && isset($data['resolved_by'])) {
            $setParts[] = "resolved_at=NOW()";
        }

        if (empty($setParts)) {
            http_response_code(400);
            return ['status'=>false,'message'=>'No valid fields provided for update.'];
        }

        try {
            $sql = "UPDATE inquiries SET ".implode(',', $setParts)." WHERE id=:inquiry_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return ['status'=>true,'message'=>'Inquiry updated successfully.'];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status'=>false,'message'=>'Update failed: '.$e->getMessage()];
        }
    }

    public function deleteInquiry($inquiryId)
    {
        if (!isset($inquiryId)) {
            http_response_code(400);
            return ['status'=>false,'message'=>'Inquiry ID is required for delete.'];
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM inquiries WHERE id=:inquiry_id");
            $stmt->execute(['inquiry_id'=>$inquiryId]);

            if ($stmt->rowCount()===0) {
                http_response_code(404);
                $this->db->rollBack();
                return ['status'=>false,'message'=>'Inquiry not found.'];
            }

            $this->db->commit();
            return ['status'=>true,'message'=>'Inquiry deleted successfully.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return ['status'=>false,'message'=>'Delete failed: '.$e->getMessage()];
        }
    }
}