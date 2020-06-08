<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends AbstractController
{
    public function index(ProductRepository $productRepository, Request $request, PaginatorInterface $paginator)
    {
        $articles = $productRepository->findAll();
        $latestProducts = $productRepository->findLatest(3);

        $allProducts = $paginator->paginate(
            $articles,
            $request->query->getInt('page', 1),
            6 // numero de page
        );

        return $this->render('shop/index.html.twig', [
            'all_products' => $allProducts,
            'latest_products' => $latestProducts,
        ]);
    }

    public function show($slug)
    {
        $product = $this->getDoctrine()
            ->getRepository(Product::class)
            ->findOneBySlug($slug);

        if (!$product) {
            throw $this->createNotFoundException();
        }

        return $this->render('shop/product_single.html.twig', [
            'product' => $product,
        ]);
    }
}
