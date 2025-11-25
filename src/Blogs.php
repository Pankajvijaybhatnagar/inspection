<?php
// src/Blogs.php

require_once __DIR__ . '/config.php';

class Blogs
{
    private $db;

    private $blogsTableFields = [
        'id', 'created_by', 'title', 'slug', 'content', 'excerpt',
        'featured_image_url', 'image_alt_text', 'meta_title', 'meta_description',
        'meta_keywords', 'canonical_url', 'status', 'created_at', 'updated_at'
    ];

    private $updatableBlogFields = [
        'title', 'slug', 'content', 'excerpt', 'featured_image_url',
        'image_alt_text', 'meta_title', 'meta_description',
        'meta_keywords', 'canonical_url', 'status', 'created_by'
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

    public function createBlog($data, $categoryIds = [])
    {
        $setParts = [];
        $params = [];

        if (empty($data['title']) || empty($data['slug']) || empty($data['created_by'])) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'title, slug, and created_by are required.'
            ];
        }

        $stmtSlug = $this->db->prepare("SELECT id FROM blogs WHERE slug = :slug");
        $stmtSlug->execute(['slug' => $data['slug']]);
        if ($stmtSlug->fetch()) {
            http_response_code(409);
            return [
                'status' => false,
                'message' => 'Slug already exists.'
            ];
        }

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableBlogFields)) {
                $setParts[] = "$field = :$field";
                $params[$field] = $value;
            }
        }

        if (empty($setParts)) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'No valid fields provided for creation.'
            ];
        }

        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO blogs SET " . implode(', ', $setParts);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $blogId = $this->db->lastInsertId();

            if (!empty($categoryIds)) {
                $stmtCat = $this->db->prepare("INSERT INTO blog_category_pivot (blog_id, category_id) VALUES (:blog_id, :category_id)");
                foreach ($categoryIds as $catId) {
                    $stmtCat->execute(['blog_id' => $blogId, 'category_id' => $catId]);
                }
            }

            $this->db->commit();
            http_response_code(201);
            return [
                'status' => true,
                'message' => 'Blog post created successfully.',
                'blog_id' => (int) $blogId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Create post failed: ' . $e->getMessage()
            ];
        }
    }

    public function getBlogs($filters = [])
    {
        $page = isset($filters['page']) && is_numeric($filters['page']) ? (int) $filters['page'] : 1;
        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int) $filters['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $select = "SELECT 
                        b.*, 
                        u.name as author_name,
                        GROUP_CONCAT(DISTINCT bc.id) as category_ids,
                        GROUP_CONCAT(DISTINCT bc.name) as category_names
                   FROM blogs b
                   LEFT JOIN users u ON b.created_by = u.id
                   LEFT JOIN blog_category_pivot bcp ON b.id = bcp.blog_id
                   LEFT JOIN blog_categories bc ON bcp.category_id = bc.id";

        $countSql = "SELECT COUNT(DISTINCT b.id)
                     FROM blogs b
                     LEFT JOIN users u ON b.created_by = u.id
                     LEFT JOIN blog_category_pivot bcp ON b.id = bcp.blog_id
                     LEFT JOIN blog_categories bc ON bcp.category_id = bc.id";
        
        $totalBlogsSql = "SELECT COUNT(id) FROM blogs";

        $whereParts = [];
        $params = [];

        foreach ($filters as $field => $value) {
            if (in_array($field, $this->blogsTableFields) && $value !== null) {
                $whereParts[] = "b.$field = :$field";
                $params[$field] = $value;
            }
        }

        if (!empty($filters['category_id'])) {
            $whereParts[] = "bcp.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }
        if (!empty($filters['category_slug'])) {
            $whereParts[] = "bc.slug = :category_slug";
            $params['category_slug'] = $filters['category_slug'];
        }

        if (!empty($filters['search'])) {
            $whereParts[] = "(b.title LIKE :search OR b.content LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($whereParts) ? " WHERE " . implode(' AND ', $whereParts) : "";
        $sql = $select . $whereClause . " GROUP BY b.id ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset";
        $countSql .= $whereClause;

        try {
            $stmtTotal = $this->db->query($totalBlogsSql);
            $totalBlogs = $stmtTotal->fetchColumn();

            $stmtFilteredCount = $this->db->prepare($countSql);
            $stmtFilteredCount->execute($params);
            $filteredCount = $stmtFilteredCount->fetchColumn();

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();
            $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'status' => true,
                'total_blogs' => (int) $totalBlogs,
                'count' => (int) $filteredCount,
                'fetched_count' => count($blogs),
                'page' => $page,
                'limit' => $limit,
                'total_pages' => (int) ceil($filteredCount / $limit),
                'data' => $blogs
            ];
        } catch (Exception $e) {
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Error fetching posts: ' . $e->getMessage()
            ];
        }
    }

    public function updateBlog($blogId, $data)
    {
        $blogSetParts = [];
        $blogParams = [];
        $categoryIds = null;

        foreach ($data as $field => $value) {
            if (in_array($field, $this->updatableBlogFields)) {
                $blogSetParts[] = "$field = :$field";
                $blogParams[$field] = $value;
            } elseif ($field === 'category_ids' && is_array($value)) {
                $categoryIds = $value;
            }
        }

        if (empty($blogSetParts) && $categoryIds === null) {
            http_response_code(400);
            return [
                'status' => false,
                'message' => 'No valid fields provided for update.'
            ];
        }

        if (isset($blogParams['slug'])) {
            $stmtSlug = $this->db->prepare("SELECT id FROM blogs WHERE slug = :slug AND id != :id");
            $stmtSlug->execute(['slug' => $blogParams['slug'], 'id' => $blogId]);
            if ($stmtSlug->fetch()) {
                http_response_code(409);
                return [
                    'status' => false,
                    'message' => 'Slug already exists.'
                ];
            }
        }
        
        $this->db->beginTransaction();
        try {
            if (!empty($blogSetParts)) {
                $sqlBlog = "UPDATE blogs SET " . implode(', ', $blogSetParts) . " WHERE id = :blog_id";
                $blogParams['blog_id'] = $blogId;
                $stmtBlog = $this->db->prepare($sqlBlog);
                $stmtBlog->execute($blogParams);
            }

            if ($categoryIds !== null) {
                $stmtDel = $this->db->prepare("DELETE FROM blog_category_pivot WHERE blog_id = :blog_id");
                $stmtDel->execute(['blog_id' => $blogId]);

                if (!empty($categoryIds)) {
                    $stmtCat = $this->db->prepare("INSERT INTO blog_category_pivot (blog_id, category_id) VALUES (:blog_id, :category_id)");
                    foreach ($categoryIds as $catId) {
                        $stmtCat->execute(['blog_id' => $blogId, 'category_id' => $catId]);
                    }
                }
            }

            $this->db->commit();
            return [
                'status' => true,
                'message' => 'Blog post updated successfully.'
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ];
        }
    }

    public function deleteBlog($blogId)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM blogs WHERE id = :blog_id");
            $stmt->execute(['blog_id' => $blogId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                $this->db->rollBack();
                return [
                    'status' => false,
                    'message' => 'Blog post not found.'
                ];
            }

            $this->db->commit();
            return [
                'status' => true,
                'message' => 'Blog post deleted successfully.'
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            return [
                'status' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }
}