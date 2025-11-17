<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\CmsPage;

/**
 * Page controller for CMS pages
 */
class PageController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['admin'],
                'rules' => [
                    [
                        'actions' => ['admin'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Display homepage
     * 
     * @return string
     */
    public function actionIndex()
    {
        // Try to get CMS homepage first
        $page = CmsPage::getHomepage();
        
        if ($page) {
            return $this->render('view', [
                'page' => $page,
                'sections' => $page->sections,
            ]);
        }

        // Fallback to default homepage if no CMS homepage is set
        return $this->render('index');
    }

    /**
     * Display a CMS page by slug
     * 
     * @param string $slug
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($slug)
    {
        $page = CmsPage::findBySlug($slug);
        
        if (!$page) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        // Set page title and meta tags
        $this->view->title = $page->seo_title ?: $page->title;
        
        if ($page->seo_description) {
            $this->view->registerMetaTag([
                'name' => 'description',
                'content' => $page->seo_description
            ]);
        }
        
        if ($page->seo_keywords) {
            $this->view->registerMetaTag([
                'name' => 'keywords',
                'content' => $page->seo_keywords
            ]);
        }

        return $this->render('view', [
            'page' => $page,
            'sections' => $page->sections,
        ]);
    }

    /**
     * Preview a page (for admin users)
     * 
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionPreview($id)
    {
        // This action could be used for previewing draft pages
        $page = CmsPage::findOne($id);
        
        if (!$page) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        // You might want to add permission checks here
        // to ensure only authorized users can preview draft pages

        return $this->render('view', [
            'page' => $page,
            'sections' => $page->allSections, // Include all sections, even inactive ones for preview
            'isPreview' => true,
        ]);
    }

    /**
     * Get navigation pages for menu rendering
     * This can be called from layouts or widgets
     * 
     * @return array
     */
    public static function getNavigationPages()
    {
        return CmsPage::getNavigation()->all();
    }
}
