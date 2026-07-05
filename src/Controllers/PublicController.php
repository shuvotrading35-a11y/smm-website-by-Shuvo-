<?php

declare(strict_types=1);

namespace SMMPanel\Controllers;

use SMMPanel\Core\Database;

/**
 * PublicController — public-facing pages (no auth required).
 */
final class PublicController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    // ── Home Page ─────────────────────────────────────────────

    public function home(array $params): void
    {
        $this->view('public/home', [
            'title'   => 'The #1 Trusted SMM Panel',
            'seoDesc' => 'Buy Instagram followers, YouTube views, TikTok likes and more at the lowest prices.',
        ], 'main');
    }

    // ── Services Page ─────────────────────────────────────────

    public function services(array $params): void
    {
        // All active categories with service count
        $categories = $this->db->fetchAll(
            'SELECT sc.id, sc.name, sc.slug, sc.icon, sc.color, COUNT(s.id) AS service_count
             FROM smmPanel_service_categories sc
             JOIN smmPanel_services s ON s.category_id = sc.id AND s.is_active = 1
             GROUP BY sc.id
             ORDER BY sc.sort_order'
        );

        // For AJAX: return JSON
        if ($this->isAjax() && isset($_GET['category_id'])) {
            $catId    = (int)$_GET['category_id'];
            $services = $this->db->fetchAll(
                'SELECT s.id, s.api_service_id,
                        COALESCE(s.custom_name, s.name) AS name,
                        s.rate, s.markup_type, s.markup_value,
                        s.min_quantity, s.max_quantity, s.refill, s.cancel
                 FROM smmPanel_services s
                 WHERE s.category_id = ? AND s.is_active = 1
                 ORDER BY s.sort_order, s.name',
                [$catId]
            );

            foreach ($services as &$s) {
                $s['user_rate'] = $this->computeRate(
                    (float)$s['rate'], $s['markup_type'], (float)$s['markup_value']
                );
            }
            unset($s);

            $this->json(['success' => true, 'data' => $services]);
        }

        // Active category from URL
        $activeCatSlug = $_GET['platform'] ?? ($categories[0]['slug'] ?? '');
        $activeCat     = array_values(array_filter($categories, fn($c) => $c['slug'] === $activeCatSlug));
        $activeCatId   = $activeCat[0]['id'] ?? ($categories[0]['id'] ?? 0);

        $services = [];
        if ($activeCatId) {
            $services = $this->db->fetchAll(
                'SELECT s.id, s.api_service_id,
                        COALESCE(s.custom_name, s.name) AS name,
                        COALESCE(s.custom_desc, s.description) AS description,
                        s.rate, s.markup_type, s.markup_value,
                        s.min_quantity, s.max_quantity, s.refill, s.cancel
                 FROM smmPanel_services s
                 WHERE s.category_id = ? AND s.is_active = 1
                 ORDER BY s.sort_order, s.name',
                [$activeCatId]
            );

            foreach ($services as &$s) {
                $s['user_rate'] = $this->computeRate(
                    (float)$s['rate'], $s['markup_type'], (float)$s['markup_value']
                );
            }
            unset($s);
        }

        $this->view('public/services', [
            'title'       => 'All SMM Services',
            'categories'  => $categories,
            'services'    => $services,
            'activeCatId' => $activeCatId,
        ], 'main');
    }

    // ── API Docs ──────────────────────────────────────────────

    public function apiDocs(array $params): void
    {
        $apiUrl = $this->getSetting('site_url', 'https://shuvosmm.com') . '/api/v2';

        $this->view('public/api-docs', [
            'title'  => 'API Documentation',
            'apiUrl' => $apiUrl,
        ], 'main');
    }

    // ── Blog ──────────────────────────────────────────────────

    public function blog(array $params): void
    {
        $pagination = $this->paginate(
            'SELECT p.id, p.title, p.slug, p.excerpt, p.featured_img, p.tags,
                    p.views, p.published_at, u.full_name AS author,
                    bc.name AS category_name
             FROM smmPanel_blog_posts p
             JOIN smmPanel_users u ON u.id = p.author_id
             LEFT JOIN smmPanel_blog_categories bc ON bc.id = p.category_id
             WHERE p.status = "published"
             ORDER BY p.published_at DESC',
            [], 9
        );

        $this->view('public/blog', [
            'title'      => 'Blog',
            'pagination' => $pagination,
        ], 'main');
    }

    public function blogPost(array $params): void
    {
        $slug = $params['slug'] ?? '';

        $post = $this->db->fetchOne(
            'SELECT p.*, u.full_name AS author, u.avatar AS author_avatar,
                    bc.name AS category_name
             FROM smmPanel_blog_posts p
             JOIN smmPanel_users u ON u.id = p.author_id
             LEFT JOIN smmPanel_blog_categories bc ON bc.id = p.category_id
             WHERE p.slug = ? AND p.status = "published"',
            [$slug]
        );

        if (!$post) {
            $this->redirect('/blog');
        }

        // Increment views
        $this->db->query('UPDATE smmPanel_blog_posts SET views = views + 1 WHERE id = ?', [$post['id']]);

        // Related posts
        $related = $this->db->fetchAll(
            'SELECT id, title, slug, excerpt, published_at, featured_img
             FROM smmPanel_blog_posts
             WHERE status = "published" AND id != ?
             ORDER BY published_at DESC LIMIT 3',
            [$post['id']]
        );

        $this->view('public/blog-post', [
            'title'   => $post['title'],
            'seoDesc' => $post['seo_desc'] ?? $post['excerpt'],
            'post'    => $post,
            'related' => $related,
        ], 'main');
    }

    // ── Referral Landing ──────────────────────────────────────

    public function referral(array $params): void
    {
        $code = strtoupper($params['code'] ?? '');

        if ($code) {
            $_SESSION['ref_code'] = $code;
        }

        $this->redirect('/register?ref=' . urlencode($code));
    }

    // ── Static Pages ──────────────────────────────────────────

    public function terms(array $params): void
    {
        $this->view('public/terms', ['title' => 'Terms of Service'], 'main');
    }

    public function privacy(array $params): void
    {
        $this->view('public/privacy', ['title' => 'Privacy Policy'], 'main');
    }

    // ── AJAX: Public API endpoint for service list (home page preview) ──

    public function getServices(array $params): void
    {
        $limit    = min((int)($_GET['limit'] ?? 10), 50);
        $catId    = (int)($_GET['category_id'] ?? 0);
        $where    = ['s.is_active = 1'];
        $binds    = [];

        if ($catId) {
            $where[] = 's.category_id = ?';
            $binds[] = $catId;
        }

        $services = $this->db->fetchAll(
            'SELECT s.id, s.api_service_id,
                    COALESCE(s.custom_name, s.name) AS name,
                    s.rate, s.markup_type, s.markup_value,
                    s.min_quantity, s.max_quantity, s.refill, s.cancel,
                    sc.name AS category, sc.icon
             FROM smmPanel_services s
             JOIN smmPanel_service_categories sc ON sc.id = s.category_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY s.sort_order, s.rate ASC
             LIMIT ' . $limit,
            $binds
        );

        foreach ($services as &$s) {
            $s['user_rate'] = $this->computeRate(
                (float)$s['rate'], $s['markup_type'], (float)$s['markup_value']
            );
        }
        unset($s);

        $this->json(['success' => true, 'data' => $services]);
    }

    // ── Helper ────────────────────────────────────────────────

    private function computeRate(float $rate, string $type, float $value): float
    {
        if ($value <= 0) return $rate;

        return match ($type) {
            'percent' => $rate * (1 + $value / 100),
            'fixed'   => $rate + $value,
            default   => $rate,
        };
    }
}
