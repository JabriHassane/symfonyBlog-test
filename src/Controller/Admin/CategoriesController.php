<?php

namespace App\Controller\Admin;

use App\Entity\Categories;
use App\Form\AddCategoriesFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/categories', name: 'app_admin_categories_')]
class CategoriesController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/categories/index.html.twig', [
            'controller_name' => 'CategoriesController',
        ]);
    }

    #[Route('/ajouter', name: 'add')]
    public function addCategory(
        Request $request,
        SluggerInterface $slugger,
        EntityManagerInterface $em
    ): Response
    {
        $category = new Categories();

        $categoryForm = $this->createForm(AddCategoriesFormType::class, $category);

        $categoryForm->handleRequest($request);

        if($categoryForm->isSubmitted() && $categoryForm->isValid()){
            $slug = strtolower($slugger->slug($category->getName()));

            $category->setSlug($slug);

            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'The category has been added successfully!');
            return $this->redirectToRoute('app_admin_categories_index');
        }

        // On affiche la vue
        return $this->render('admin/categories/index.html.twig', [
            'categoryForm' => $categoryForm->createView(),
        ]);
    }
}